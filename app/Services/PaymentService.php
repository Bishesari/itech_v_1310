<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\FinancialTransactionType;
use App\Enums\PaymentStatus;
use App\Enums\PaymentTransactionStatus;
use App\Enums\ReceivableStatus;
use App\Models\FinancialAccount;
use App\Models\FinancialTransaction;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\PaymentMethod;
use App\Models\PaymentTransaction;
use App\Models\Receivable;
use App\Services\Payments\SepPaymentGateway;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

final class PaymentService
{
    public function createGatewayPayment(array $data): Payment
    {
        return DB::transaction(function () use ($data) {
            $paymentMethod = PaymentMethod::query()
                ->whereKey($data['payment_method_id'])
                ->where('is_active', true)
                ->firstOrFail();

            $financialAccount = FinancialAccount::query()
                ->whereKey($data['financial_account_id'])
                ->where('is_active', true)
                ->firstOrFail();

            if ($financialAccount->type->value !== 'gateway') {
                throw ValidationException::withMessages([
                    'financial_account_id' => 'حساب مالی انتخاب‌شده از نوع درگاه پرداخت نیست.',
                ]);
            }

            if (! $financialAccount->paymentMethods()
                ->whereKey($paymentMethod->id)
                ->exists()) {
                throw ValidationException::withMessages([
                    'payment_method_id' => 'روش پرداخت انتخاب‌شده برای این حساب مالی مجاز نیست.',
                ]);
            }

            if (empty($data['allocations'])) {
                throw ValidationException::withMessages([
                    'allocations' => 'حداقل یک بدهی باید انتخاب شود.',
                ]);
            }

            $receivableIds = collect($data['allocations'])
                ->pluck('receivable_id');

            if ($receivableIds->count() !== $receivableIds->unique()->count()) {
                throw ValidationException::withMessages([
                    'allocations' => 'یک بدهی را نمی‌توان بیش از یک بار انتخاب کرد.',
                ]);
            }

            $receivables = Receivable::query()
                ->whereIn('id', $receivableIds)
                ->where('user_id', $data['user_id'])
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($receivables->count() !== $receivableIds->count()) {
                throw ValidationException::withMessages([
                    'allocations' => 'بعضی از بدهی‌های انتخاب‌شده معتبر نیستند.',
                ]);
            }

            $allocations = collect($data['allocations']);
            $totalAmount = 0;

            foreach ($receivables as $receivable) {
                if (! in_array($receivable->status, [
                    ReceivableStatus::UNPAID,
                    ReceivableStatus::PARTIALLY_PAID,
                ], true)) {
                    throw ValidationException::withMessages([
                        'allocations' => "بدهی {$receivable->id} قابل پرداخت نیست.",
                    ]);
                }

                $allocation = $allocations->firstWhere(
                    'receivable_id',
                    $receivable->id
                );

                $amount = (int) $allocation['amount'];

                if ($amount <= 0) {
                    throw ValidationException::withMessages([
                        'allocations' => 'مبلغ پرداختی باید بیشتر از صفر باشد.',
                    ]);
                }

                $hasPendingPayment = PaymentAllocation::query()
                    ->where('receivable_id', $receivable->id)
                    ->whereHas('payment', function ($query) {
                        $query
                            ->where('status', PaymentStatus::PENDING)
                            ->where('expires_at', '>', now());
                    })
                    ->whereHas('payment.transactions', function ($query) {
                        $query->where(
                            'status',
                            PaymentTransactionStatus::PENDING
                        );
                    })
                    ->exists();

                if ($hasPendingPayment) {
                    throw ValidationException::withMessages([
                        'allocations' => "بدهی {$receivable->id} در انتظار تعیین تکلیف یک پرداخت دیگر است.",
                    ]);
                }

                $paidAmount = PaymentAllocation::query()
                    ->where('receivable_id', $receivable->id)
                    ->whereHas('payment.transactions', function ($query) {
                        $query->where(
                            'status',
                            PaymentTransactionStatus::SUCCESSFUL
                        );
                    })
                    ->sum('amount');

                $paidAmount = min($paidAmount, $receivable->amount);

                $remainingAmount = max(0, $receivable->amount - $paidAmount);

                if ($amount > $remainingAmount) {
                    throw ValidationException::withMessages([
                        'allocations' => "مبلغ انتخاب‌شده برای بدهی {$receivable->id} بیشتر از مانده بدهی است.",
                    ]);
                }

                $totalAmount += $amount;
            }

            $payment = Payment::query()->create([
                'user_id' => $data['user_id'],
                'amount' => $totalAmount,
                'status' => PaymentStatus::PENDING,
                'description' => $data['description'] ?? null,
                'created_by' => null,
                'expires_at' => now()->addMinutes(15),
            ]);

            $payment->transactions()->create([
                'payment_method_id' => $paymentMethod->id,
                'financial_account_id' => $financialAccount->id,
                'amount' => $totalAmount,
                'status' => PaymentTransactionStatus::PENDING,
                'gateway' => 'sep',
                'gateway_request_id' => 'SEP'
                    .now()->format('ymdHis')
                    .Str::upper(Str::random(8)),
            ]);

            foreach ($allocations as $allocation) {
                $payment->allocations()->create([
                    'receivable_id' => $allocation['receivable_id'],
                    'amount' => $allocation['amount'],
                ]);
            }

            return $payment->load('transactions', 'allocations');
        });
    }

    public function requestGatewayToken(
        int $paymentTransactionId,
        string $redirectUrl,
        ?string $cellNumber = null,
    ): string {
        $transaction = DB::transaction(function () use ($paymentTransactionId) {
            $transaction = PaymentTransaction::query()
                ->lockForUpdate()
                ->findOrFail($paymentTransactionId);

            if ($transaction->status !== PaymentTransactionStatus::PENDING) {
                throw new RuntimeException(
                    'Payment transaction is not pending.'
                );
            }

            if ($transaction->gateway !== 'sep') {
                throw new RuntimeException(
                    'Payment transaction gateway is not SEP.'
                );
            }

            if (blank($transaction->gateway_request_id)) {
                throw new RuntimeException(
                    'Gateway request ID is missing.'
                );
            }

            if (filled($transaction->gateway_token)) {
                return $transaction;
            }

            if ($transaction->gateway_token_requested_at !== null) {
                throw new RuntimeException(
                    'A gateway token request has already been initiated.'
                );
            }

            $transaction->update([
                'gateway_token_requested_at' => now(),
            ]);

            return $transaction;
        });

        if (filled($transaction->gateway_token)) {
            return $transaction->gateway_token;
        }

        $gateway = app(SepPaymentGateway::class);

        $token = $gateway->requestToken(
            amount: $transaction->amount,
            resNum: $transaction->gateway_request_id,
            redirectUrl: $redirectUrl,
            cellNumber: $cellNumber,
        );

        $transaction->update([
            'gateway_token' => $token,
        ]);

        return $token;
    }

    public function startGatewayPayment(
        int $paymentTransactionId,
        string $redirectUrl,
        ?string $cellNumber = null,
    ): string {
        $token = $this->requestGatewayToken(
            paymentTransactionId: $paymentTransactionId,
            redirectUrl: $redirectUrl,
            cellNumber: $cellNumber,
        );

        return app(SepPaymentGateway::class)->getPaymentUrl($token);
    }

    public function completeGatewayPayment(
        int $paymentTransactionId,
        string $refNum,
    ): PaymentStatus {
        if (blank($refNum)) {
            throw new RuntimeException('Gateway reference is required.');
        }

        return DB::transaction(function () use (
            $paymentTransactionId,
            $refNum,
        ) {
            // ابتدا تراکنش را برای پیدا کردن Payment شناسایی می‌کنیم.
            $initialTransaction = PaymentTransaction::query()
                ->findOrFail($paymentTransactionId);

            // قفل Payment برای جلوگیری از پردازش هم‌زمان تراکنش‌های آن
            $payment = Payment::query()
                ->lockForUpdate()
                ->findOrFail($initialTransaction->payment_id);

            $transaction = PaymentTransaction::query()
                ->lockForUpdate()
                ->findOrFail($paymentTransactionId);

            // Callback تکراری برای تراکنش موفق
            if ($transaction->status === PaymentTransactionStatus::SUCCESSFUL) {
                if ($transaction->gateway_reference !== $refNum) {
                    throw new RuntimeException(
                        'Gateway reference does not match.'
                    );
                }

                return $payment->status;
            }

            if ($transaction->status !== PaymentTransactionStatus::PENDING) {
                throw new RuntimeException(
                    'Payment transaction is not pending.'
                );
            }

            if ($transaction->gateway !== 'sep') {
                throw new RuntimeException(
                    'Payment transaction gateway is not SEP.'
                );
            }

            // ثبت موفقیت تراکنش
            $transaction->update([
                'status' => PaymentTransactionStatus::SUCCESSFUL,
                'gateway_reference' => $refNum,
                'paid_at' => now(),
            ]);

            // محاسبه مجموع تراکنش‌های موفق این Payment
            $successfulAmount = $payment->transactions()
                ->where('status', PaymentTransactionStatus::SUCCESSFUL)
                ->sum('amount');

            if ($successfulAmount > $payment->amount) {
                throw new RuntimeException(
                    'Successful payment transactions exceed payment amount.'
                );
            }

            $paymentStatus = $successfulAmount >= $payment->amount
                ? PaymentStatus::SUCCESSFUL
                : PaymentStatus::PARTIALLY_PAID;

            $payment->update([
                'status' => $paymentStatus,
                'paid_at' => $paymentStatus === PaymentStatus::SUCCESSFUL
                    ? now()
                    : null,
            ]);

            // ثبت گردش درآمد حساب مالی
            FinancialTransaction::query()->create([
                'financial_account_id' => $transaction->financial_account_id,
                'type' => FinancialTransactionType::INCOME,
                'amount' => $transaction->amount,
                'occurred_at' => $transaction->paid_at,
                'reference_type' => PaymentTransaction::class,
                'reference_id' => $transaction->id,
                'event_key' => "payment-transaction:{$transaction->id}:income",
                'description' => 'دریافت وجه از درگاه SEP',
                'created_by' => null,
            ]);

            // به‌روزرسانی وضعیت بدهی‌های تخصیص‌یافته
            $receivableIds = $payment->allocations()
                ->pluck('receivable_id');

            $receivables = Receivable::query()
                ->whereIn('id', $receivableIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            foreach ($receivables as $receivable) {
                $paidAmount = PaymentAllocation::query()
                    ->where('receivable_id', $receivable->id)
                    ->whereHas('payment.transactions', function ($query) {
                        $query->where(
                            'status',
                            PaymentTransactionStatus::SUCCESSFUL
                        );
                    })
                    ->sum('amount');

                $paidAmount = min($paidAmount, $receivable->amount);

                $status = match (true) {
                    $paidAmount <= 0 => ReceivableStatus::UNPAID,
                    $paidAmount >= $receivable->amount => ReceivableStatus::PAID,
                    default => ReceivableStatus::PARTIALLY_PAID,
                };

                if ($receivable->status !== ReceivableStatus::CANCELLED) {
                    $receivable->update(['status' => $status]);
                }
            }

            return $paymentStatus;
        });
    }

    public function createManualPayment(array $data): Payment
    {
        return DB::transaction(function () use ($data) {
            $paymentMethod = PaymentMethod::query()
                ->whereKey($data['payment_method_id'])
                ->where('is_active', true)
                ->firstOrFail();

            $financialAccount = FinancialAccount::query()
                ->whereKey($data['financial_account_id'])
                ->where('is_active', true)
                ->firstOrFail();

            if ($financialAccount->type->value === 'gateway') {
                throw ValidationException::withMessages([
                    'financial_account_id' => 'برای پرداخت دستی نمی‌توان از حساب درگاه استفاده کرد.',
                ]);
            }

            if (! $financialAccount->paymentMethods()
                ->whereKey($paymentMethod->id)
                ->exists()) {
                throw ValidationException::withMessages([
                    'payment_method_id' => 'روش پرداخت انتخاب‌شده برای این حساب مالی مجاز نیست.',
                ]);
            }

            if (empty($data['allocations'])) {
                throw ValidationException::withMessages([
                    'allocations' => 'حداقل یک بدهی باید انتخاب شود.',
                ]);
            }

            $allocations = collect($data['allocations']);

            $receivableIds = $allocations->pluck('receivable_id');

            if ($receivableIds->count() !== $receivableIds->unique()->count()) {
                throw ValidationException::withMessages([
                    'allocations' => 'یک بدهی را نمی‌توان بیش از یک بار انتخاب کرد.',
                ]);
            }

            $receivables = Receivable::query()
                ->whereIn('id', $receivableIds)
                ->where('user_id', $data['user_id'])
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($receivables->count() !== $receivableIds->count()) {
                throw ValidationException::withMessages([
                    'allocations' => 'بعضی از بدهی‌های انتخاب‌شده معتبر نیستند.',
                ]);
            }

            $totalAmount = 0;

            foreach ($receivables as $receivable) {
                if ($receivable->status === ReceivableStatus::CANCELLED) {
                    throw ValidationException::withMessages([
                        'allocations' => "بدهی {$receivable->id} لغو شده است.",
                    ]);
                }

                $allocation = $allocations->firstWhere(
                    'receivable_id',
                    $receivable->id
                );

                $amount = (int) $allocation['amount'];

                if ($amount <= 0) {
                    throw ValidationException::withMessages([
                        'allocations' => 'مبلغ پرداختی باید بیشتر از صفر باشد.',
                    ]);
                }

                $paidAmount = PaymentAllocation::query()
                    ->where('receivable_id', $receivable->id)
                    ->whereHas('payment.transactions', function ($query) {
                        $query->where(
                            'status',
                            PaymentTransactionStatus::SUCCESSFUL
                        );
                    })
                    ->sum('amount');

                $paidAmount = min($paidAmount, $receivable->amount);

                $remainingAmount = max(
                    0,
                    $receivable->amount - $paidAmount
                );

                if ($amount > $remainingAmount) {
                    throw ValidationException::withMessages([
                        'allocations' => "مبلغ انتخاب‌شده برای بدهی {$receivable->id} بیشتر از مانده بدهی است.",
                    ]);
                }

                $totalAmount += $amount;
            }

            $paidAt = now();
            $creatorId = auth()->id();

            // ایجاد پرداخت
            $payment = Payment::query()->create([
                'user_id' => $data['user_id'],
                'amount' => $totalAmount,
                'status' => PaymentStatus::SUCCESSFUL,
                'paid_at' => $paidAt,
                'description' => $data['description'] ?? null,
                'created_by' => $creatorId,
            ]);

            // ایجاد تراکنش موفق
            $transaction = $payment->transactions()->create([
                'payment_method_id' => $paymentMethod->id,
                'financial_account_id' => $financialAccount->id,
                'amount' => $totalAmount,
                'status' => PaymentTransactionStatus::SUCCESSFUL,
                'tracking_code' => $data['tracking_code'] ?? null,
                'paid_at' => $paidAt,
                'description' => $data['description'] ?? null,
            ]);

            // ثبت تخصیص‌ها
            foreach ($allocations as $allocation) {
                $payment->allocations()->create([
                    'receivable_id' => $allocation['receivable_id'],
                    'amount' => $allocation['amount'],
                ]);
            }

            // ثبت گردش درآمد
            FinancialTransaction::query()->create([
                'financial_account_id' => $financialAccount->id,
                'type' => FinancialTransactionType::INCOME,
                'amount' => $totalAmount,
                'occurred_at' => $paidAt,
                'reference_type' => PaymentTransaction::class,
                'reference_id' => $transaction->id,
                'event_key' => "payment-transaction:{$transaction->id}:income",
                'description' => 'دریافت وجه توسط حسابدار',
                'created_by' => $creatorId,
            ]);

            // به‌روزرسانی وضعیت مطالبات
            foreach ($receivables as $receivable) {
                $paidAmount = PaymentAllocation::query()
                    ->where('receivable_id', $receivable->id)
                    ->whereHas('payment.transactions', function ($query) {
                        $query->where(
                            'status',
                            PaymentTransactionStatus::SUCCESSFUL
                        );
                    })
                    ->sum('amount');

                $paidAmount = min($paidAmount, $receivable->amount);

                $status = match (true) {
                    $paidAmount <= 0 => ReceivableStatus::UNPAID,
                    $paidAmount >= $receivable->amount => ReceivableStatus::PAID,
                    default => ReceivableStatus::PARTIALLY_PAID,
                };

                if ($receivable->status !== ReceivableStatus::CANCELLED) {
                    $receivable->update(['status' => $status]);
                }
            }

            return $payment->load('transactions', 'allocations');
        });
    }

    public function getPayableReceivables(int $userId)
    {
        return Receivable::query()
            ->where('user_id', $userId)
            ->whereIn('status', [
                ReceivableStatus::UNPAID,
                ReceivableStatus::PARTIALLY_PAID,
            ])
            ->orderBy('due_at')
            ->orderBy('id')
            ->get();
    }

    public function getManualPaymentAccounts()
    {
        return FinancialAccount::query()
            ->where('is_active', true)
            ->where('type', '!=', 'gateway')
            ->with([
                'paymentMethods' => function ($query) {
                    $query
                        ->where('is_active', true)
                        ->orderBy('sort_order')
                        ->orderBy('name');
                },
            ])
            ->orderBy('name')
            ->get();
    }

    public function getActivePaymentMethods()
    {
        return PaymentMethod::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }
}
