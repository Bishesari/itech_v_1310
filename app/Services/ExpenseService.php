<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\FinancialTransactionType;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\FinancialAccount;
use App\Models\FinancialTransaction;
use Illuminate\Support\Facades\DB;

class ExpenseService
{
    public function createExpense(array $data): Expense
    {
        validator($data, [
            'expense_category_id' => ['required', 'integer'],
            'financial_account_id' => ['required', 'integer'],
            'amount' => ['required', 'integer', 'min:1'],
            'paid_at' => ['required', 'date'],
            'tracking_code' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
        ])->validate();

        return DB::transaction(function () use ($data) {
            $category = ExpenseCategory::query()
                ->whereKey($data['expense_category_id'])
                ->where('is_active', true)
                ->firstOrFail();

            $financialAccount = FinancialAccount::query()
                ->whereKey($data['financial_account_id'])
                ->where('is_active', true)
                ->firstOrFail();

            $amount = (int) $data['amount'];
            $paidAt = $data['paid_at'];
            $creatorId = auth()->id();

            $expense = Expense::query()->create([
                'expense_category_id' => $category->id,
                'financial_account_id' => $financialAccount->id,
                'amount' => $amount,
                'paid_at' => $paidAt,
                'tracking_code' => $data['tracking_code'] ?? null,
                'description' => $data['description'] ?? null,
                'created_by' => $creatorId,
            ]);

            FinancialTransaction::query()->create([
                'financial_account_id' => $financialAccount->id,
                'type' => FinancialTransactionType::EXPENSE,
                'amount' => $amount,
                'occurred_at' => $paidAt,
                'reference_type' => Expense::class,
                'reference_id' => $expense->id,
                'event_key' => "expense:{$expense->id}:expense",
                'description' => $data['description'] ?? 'ثبت هزینه آموزشگاه',
                'created_by' => $creatorId,
            ]);

            return $expense->load([
                'category',
                'financialAccount',
                'creator',
            ]);
        });
    }
}
