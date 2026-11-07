<?php

use App\Enums\PaymentStatus;
use App\Enums\PaymentTransactionStatus;
use App\Enums\ProductType;
use App\Models\ExamAttempt;
use App\Models\FinancialAccount;
use App\Models\PaymentMethod;
use App\Models\PaymentTransaction;
use App\Models\Product;
use App\Models\ProductAccess;
use App\Services\Exam\ExamAttemptStatusService;
use App\Services\Orders\OrderService;
use App\Services\PaymentService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts.public')]
#[Title('جزئیات محصول | فروشگاه آی‌تک')]
class extends Component
{
    #[Locked]
    public Product $product;

    public function mount(Product $product): void
    {
        abort_unless(
            $product->is_active && $product->is_publicly_listed,
            404
        );

        $this->product = $product;
    }

    public function purchase(
        OrderService $orderService,
        PaymentService $paymentService,
    ) {
        $user = Auth::user();

        if (! $user) {
            return redirect()->guest(route('login'));
        }

        $order = $orderService->createForProduct(
            user: $user,
            productId: $this->product->getKey(),
        );

        $receivable = $order->receivable;

        if (! $receivable) {
            throw ValidationException::withMessages([
                'product' => 'مطالبه مربوط به این سفارش پیدا نشد.',
            ]);
        }

        // اگر پرداخت SEP معتبر و در انتظار وجود داشته باشد،
        // همان تراکنش را مجدداً استفاده می‌کنیم.
        $existingTransaction = PaymentTransaction::query()
            ->where('status', PaymentTransactionStatus::PENDING)
            ->where('gateway', 'sep')
            ->where('amount', $receivable->amount)
            ->whereHas('payment', function ($query) use (
                $user,
                $receivable,
            ) {
                $query
                    ->where('user_id', $user->id)
                    ->where('status', PaymentStatus::PENDING)
                    ->where('amount', $receivable->amount)
                    ->where('expires_at', '>', now())
                    ->whereHas('allocations', function ($query) use (
                        $receivable,
                    ) {
                        $query
                            ->where('receivable_id', $receivable->id)
                            ->where('amount', $receivable->amount);
                    });
            })
            ->latest('id')
            ->first();

        if ($existingTransaction) {
            if (
                $existingTransaction->gateway_token_requested_at !== null
                && blank($existingTransaction->gateway_token)
            ) {
                throw ValidationException::withMessages([
                    'product' => 'درخواست پرداخت قبلی هنوز در حال تعیین تکلیف است. لطفاً پس از چند دقیقه دوباره تلاش کنید.',
                ]);
            }

            return redirect()->route(
                'payments.sep.start',
                $existingTransaction
            );
        }

        // دریافت روش پرداخت SEP
        $paymentMethod = PaymentMethod::query()
            ->where('code', 'sep')
            ->where('is_active', true)
            ->first();

        if (! $paymentMethod) {
            throw ValidationException::withMessages([
                'product' => 'درگاه پرداخت در حال حاضر فعال نیست.',
            ]);
        }

        // دریافت حساب مالی متصل به SEP
        $financialAccount = FinancialAccount::query()
            ->where('code', 'sep_gateway')
            ->where('is_active', true)
            ->where('type', 'gateway')
            ->whereHas('paymentMethods', function ($query) use (
                $paymentMethod,
            ) {
                $query->whereKey($paymentMethod->id);
            })
            ->first();

        if (! $financialAccount) {
            throw ValidationException::withMessages([
                'product' => 'حساب مالی درگاه پرداخت به‌درستی تنظیم نشده است.',
            ]);
        }

        // ایجاد پرداخت جدید
        $payment = $paymentService->createGatewayPayment([
            'payment_method_id' => $paymentMethod->id,
            'financial_account_id' => $financialAccount->id,
            'user_id' => $user->id,
            'allocations' => [
                [
                    'receivable_id' => $receivable->id,
                    'amount' => $receivable->amount,
                ],
            ],
            'description' => 'خرید محصول: '.$this->product->title,
        ]);

        $transaction = $payment->transactions
            ->firstWhere('status', PaymentTransactionStatus::PENDING);

        if (! $transaction) {
            throw ValidationException::withMessages([
                'product' => 'تراکنش پرداخت ایجاد نشد. لطفاً دوباره تلاش کنید.',
            ]);
        }

        return redirect()->route(
            'payments.sep.start',
            $transaction
        );
    }

    public function with(ExamAttemptStatusService $statusService): array
    {
        $this->product->load([
            'standard.field',
            'prices' => fn ($query) => $query
                ->where(function ($query) {
                    $query->whereNull('starts_at')
                        ->orWhere('starts_at', '<=', now());
                })
                ->where(function ($query) {
                    $query->whereNull('ends_at')
                        ->orWhere('ends_at', '>=', now());
                }),
        ]);
        $productAccess = Auth::check()
            ? Auth::user()
                ->productAccesses()
                ->where('product_id', $this->product->id)
                ->first()
            : null;

        $hasAccess = $productAccess !== null;

        $price = $this->product->prices
            ->sortByDesc('starts_at')
            ->first();

        $details = match ($this->product->type) {
            ProductType::WRITTEN_FREQUENT_QUESTIONS =>
            $this->writtenQuestionsDetails($hasAccess),

            ProductType::MOCK_EXAM => $this->mockExamDetails($productAccess, $statusService),
        };

        return [
            'price' => $price,
            'details' => $details,
            'hasAccess' => $hasAccess,
        ];
    }

    private function writtenQuestionsDetails(bool $hasAccess): array
    {
        $questions = $this->product->standard
            ->questions()
            ->where('is_active', true)
            ->where('is_frequently_final', true)
            ->with(['options:id,question_id,text,image_path,dir'])
            ->get([
                'id',
                'chapter_id',
                'type',
                'text',
                'difficulty',
                'image_path',
                'code',
                'code_language',
            ]);

        return [
            'type' => 'written_questions',
            'question_count' => $questions->count(),
            'questions' => $hasAccess ? $questions : collect(),
        ];
    }

    private function mockExamDetails(?ProductAccess $productAccess, ExamAttemptStatusService $statusService): array
    {
        $exam = $this->product->exam;

        abort_unless($exam, 404);

        $attempt = $productAccess
            ? ExamAttempt::query()
                ->where('product_access_id', $productAccess->id)
                ->first()
            : null;

        if ($attempt) {
            $attempt = $statusService->refresh($attempt);
        }

        return [
            'type' => 'mock_exam',
            'exam' => $exam,
            'attempt' => $attempt,
        ];
    }
};
