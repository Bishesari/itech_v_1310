<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PaymentTransactionStatus;
use App\Enums\RefundStatus;
use App\Enums\FinancialTransactionType;
use App\Models\FinancialAccount;
use App\Models\FinancialTransaction;
use App\Models\PaymentMethod;
use App\Models\PaymentTransaction;
use App\Models\Refund;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RefundService
{
    public function createRefund(array $data): Refund
    {
        validator($data, [
            'payment_transaction_id' => ['required', 'integer'],
            'payment_method_id' => ['required', 'integer'],
            'financial_account_id' => ['required', 'integer'],
            'amount' => ['required', 'integer', 'min:1'],
            'refunded_at' => ['required', 'date'],
            'tracking_code' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
        ])->validate();

        return DB::transaction(function () use ($data) {
            $paymentTransaction = PaymentTransaction::query()
                ->lockForUpdate()
                ->findOrFail($data['payment_transaction_id']);

            if ($paymentTransaction->status !== PaymentTransactionStatus::SUCCESSFUL) {
                throw ValidationException::withMessages([
                    'payment_transaction_id' => 'فقط پرداخت موفق قابل بازپرداخت است.',
                ]);
            }

            $paymentMethod = PaymentMethod::query()
                ->whereKey($data['payment_method_id'])
                ->where('is_active', true)
                ->firstOrFail();

            $financialAccount = FinancialAccount::query()
                ->whereKey($data['financial_account_id'])
                ->where('is_active', true)
                ->firstOrFail();

            if (! $financialAccount->paymentMethods()
                ->whereKey($paymentMethod->id)
                ->exists()) {
                throw ValidationException::withMessages([
                    'payment_method_id' => 'روش پرداخت انتخاب‌شده برای این حساب مالی مجاز نیست.',
                ]);
            }

            $amount = (int) $data['amount'];

            if ($amount <= 0) {
                throw ValidationException::withMessages([
                    'amount' => 'مبلغ بازپرداخت باید بیشتر از صفر باشد.',
                ]);
            }

            $refundedAmount = Refund::query()
                ->where('payment_transaction_id', $paymentTransaction->id)
                ->where('status', RefundStatus::SUCCESSFUL)
                ->sum('amount');

            if ($refundedAmount + $amount > $paymentTransaction->amount) {
                throw ValidationException::withMessages([
                    'amount' => 'مبلغ بازپرداخت از مبلغ پرداخت اولیه بیشتر است.',
                ]);
            }

            $refundedAt = $data['refunded_at'];
            $creatorId = auth()->id();

            $refund = Refund::query()->create([
                'payment_transaction_id' => $paymentTransaction->id,
                'financial_account_id' => $financialAccount->id,
                'payment_method_id' => $paymentMethod->id,
                'amount' => $amount,
                'status' => RefundStatus::SUCCESSFUL,
                'refunded_at' => $refundedAt,
                'tracking_code' => $data['tracking_code'] ?? null,
                'description' => $data['description'] ?? null,
                'created_by' => $creatorId,
            ]);

            FinancialTransaction::query()->create([
                'financial_account_id' => $financialAccount->id,
                'type' => FinancialTransactionType::EXPENSE,
                'amount' => $amount,
                'occurred_at' => $refundedAt,
                'reference_type' => Refund::class,
                'reference_id' => $refund->id,
                'event_key' => "refund:{$refund->id}:expense",
                'description' => 'بازپرداخت وجه',
                'created_by' => $creatorId,
            ]);

            return $refund->load([
                'paymentTransaction.payment',
                'financialAccount',
                'paymentMethod',
                'creator',
            ]);
        });
    }
}
