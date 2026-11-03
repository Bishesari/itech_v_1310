<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AccountTransferStatus;
use App\Enums\FinancialTransactionType;
use App\Models\AccountTransfer;
use App\Models\FinancialAccount;
use App\Models\FinancialTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AccountTransferService
{
    public function createTransfer(array $data): AccountTransfer
    {
        validator($data, [
            'from_account_id' => ['required', 'integer', 'different:to_account_id'],
            'to_account_id' => ['required', 'integer'],
            'amount' => ['required', 'integer', 'min:1'],
            'transferred_at' => ['required', 'date'],
            'description' => ['nullable', 'string'],
        ])->validate();

        return DB::transaction(function () use ($data) {
            $accountIds = collect([
                $data['from_account_id'],
                $data['to_account_id'],
            ])->unique()->sort()->values();

            $accounts = FinancialAccount::query()
                ->whereIn('id', $accountIds)
                ->where('is_active', true)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($accounts->count() !== 2) {
                throw ValidationException::withMessages([
                    'from_account_id' => 'حساب مبدأ و مقصد باید معتبر و فعال باشند.',
                ]);
            }

            $fromAccount = $accounts->get($data['from_account_id']);
            $toAccount = $accounts->get($data['to_account_id']);

            $amount = (int) $data['amount'];
            $transferredAt = $data['transferred_at'];
            $creatorId = auth()->id();

            $transfer = AccountTransfer::query()->create([
                'from_account_id' => $fromAccount->id,
                'to_account_id' => $toAccount->id,
                'amount' => $amount,
                'transferred_at' => $transferredAt,
                'status' => AccountTransferStatus::SUCCESSFUL,
                'description' => $data['description'] ?? null,
                'created_by' => $creatorId,
            ]);

            FinancialTransaction::query()->create([
                'financial_account_id' => $fromAccount->id,
                'type' => FinancialTransactionType::EXPENSE,
                'amount' => $amount,
                'occurred_at' => $transferredAt,
                'reference_type' => AccountTransfer::class,
                'reference_id' => $transfer->id,
                'event_key' => "account-transfer:{$transfer->id}:out",
                'description' => 'انتقال وجه به حساب دیگر',
                'created_by' => $creatorId,
            ]);

            FinancialTransaction::query()->create([
                'financial_account_id' => $toAccount->id,
                'type' => FinancialTransactionType::INCOME,
                'amount' => $amount,
                'occurred_at' => $transferredAt,
                'reference_type' => AccountTransfer::class,
                'reference_id' => $transfer->id,
                'event_key' => "account-transfer:{$transfer->id}:in",
                'description' => 'دریافت وجه از حساب دیگر',
                'created_by' => $creatorId,
            ]);

            return $transfer->load([
                'fromAccount',
                'toAccount',
                'creator',
            ]);
        });
    }
}
