<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\FinancialTransactionType;
use App\Enums\PaymentTransactionStatus;
use App\Enums\RefundStatus;
use App\Models\Expense;
use App\Models\FinancialAccount;
use App\Models\PaymentAllocation;
use App\Models\PaymentTransaction;
use App\Models\Refund;
use App\Support\JalaliDate;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use InvalidArgumentException;

final class FinancialReportService
{
    public function getBalanceBefore(
        FinancialAccount $account,
        CarbonInterface $date,
    ): int {
        return (int) $account->financialTransactions()
            ->where('occurred_at', '<', $date)
            ->selectRaw('
                COALESCE(SUM(
                    CASE
                        WHEN type = ? THEN amount
                        WHEN type = ? THEN -amount
                        ELSE 0
                    END
                ), 0) as balance
            ', [
                FinancialTransactionType::INCOME->value,
                FinancialTransactionType::EXPENSE->value,
            ])
            ->value('balance');
    }

    public function getAccountStatement(
        FinancialAccount $account,
        CarbonInterface $start,
        CarbonInterface $end,
    ): array {
        $transactions = $account->financialTransactions()
            ->where('occurred_at', '>=', $start)
            ->where('occurred_at', '<', $end)
            ->orderBy('occurred_at')
            ->orderBy('id')
            ->get();

        $openingBalance = $this->getBalanceBefore($account, $start);
        $runningBalance = $openingBalance;

        $income = 0;
        $expense = 0;

        foreach ($transactions as $transaction) {
            if ($transaction->type === FinancialTransactionType::INCOME) {
                $income += $transaction->amount;
                $runningBalance += $transaction->amount;
            } else {
                $expense += $transaction->amount;
                $runningBalance -= $transaction->amount;
            }

            $transaction->running_balance = $runningBalance;
        }

        return [
            'opening_balance' => $openingBalance,
            'income' => $income,
            'expense' => $expense,
            'closing_balance' => $runningBalance,
            'transactions' => $transactions,
        ];
    }

    public function getJalaliAccountStatement(
        FinancialAccount $account,
        string $period,
        int $year,
        ?int $month = null,
        ?int $day = null,
    ): array {
        $range = match ($period) {
            'day' => JalaliDate::dayRange(
                $year,
                $month ?? throw new InvalidArgumentException('Month is required.'),
                $day ?? throw new InvalidArgumentException('Day is required.'),
            ),

            'week' => JalaliDate::weekRange(
                $year,
                $month ?? throw new InvalidArgumentException('Month is required.'),
                $day ?? throw new InvalidArgumentException('Day is required.'),
            ),

            'month' => JalaliDate::monthRange(
                $year,
                $month ?? throw new InvalidArgumentException('Month is required.'),
            ),

            'year' => JalaliDate::yearRange($year),

            default => throw new InvalidArgumentException('Invalid report period.'),
        };

        return $this->getAccountStatement(
            $account,
            $range['start'],
            $range['end'],
        );
    }

    public function getAccountsSummary(): Collection
    {
        return FinancialAccount::query()
            ->withSum([
                'financialTransactions as total_income' => fn ($query) => $query
                    ->where('type', FinancialTransactionType::INCOME),
            ], 'amount')
            ->withSum([
                'financialTransactions as total_expense' => fn ($query) => $query
                    ->where('type', FinancialTransactionType::EXPENSE),
            ], 'amount')
            ->orderBy('name')
            ->get()
            ->each(function (FinancialAccount $account) {
                $account->total_income = (int) $account->total_income;
                $account->total_expense = (int) $account->total_expense;
                $account->balance = $account->total_income - $account->total_expense;
            });
    }

    public function getPaymentsSummary(
        CarbonInterface $start,
        CarbonInterface $end,
    ): array {
        $query = PaymentTransaction::query()
            ->where('status', PaymentTransactionStatus::SUCCESSFUL)
            ->whereNotNull('paid_at')
            ->where('paid_at', '>=', $start)
            ->where('paid_at', '<', $end);

        return [
            'count' => (clone $query)->count(),
            'amount' => (int) (clone $query)->sum('amount'),
        ];
    }

    public function getPaymentsByMethod(
        CarbonInterface $start,
        CarbonInterface $end,
    ) {
        return PaymentTransaction::query()
            ->selectRaw('
            payment_method_id,
            COUNT(*) as count,
            SUM(amount) as amount
        ')
            ->with('paymentMethod:id,name,code')
            ->where('status', PaymentTransactionStatus::SUCCESSFUL)
            ->whereNotNull('paid_at')
            ->where('paid_at', '>=', $start)
            ->where('paid_at', '<', $end)
            ->groupBy('payment_method_id')
            ->orderBy('payment_method_id')
            ->get();
    }

    public function getPaymentsByAccount(
        CarbonInterface $start,
        CarbonInterface $end,
    ) {
        return PaymentTransaction::query()
            ->selectRaw('
            financial_account_id,
            COUNT(*) as count,
            SUM(amount) as amount
        ')
            ->with('financialAccount:id,name,code')
            ->where('status', PaymentTransactionStatus::SUCCESSFUL)
            ->whereNotNull('paid_at')
            ->where('paid_at', '>=', $start)
            ->where('paid_at', '<', $end)
            ->groupBy('financial_account_id')
            ->orderBy('financial_account_id')
            ->get();
    }

    public function getPaymentDetails(
        CarbonInterface $start,
        CarbonInterface $end,
    ) {
        return PaymentTransaction::query()
            ->with([
                'payment.user',
                'payment.allocations.receivable',
                'paymentMethod',
                'financialAccount',
            ])
            ->where('status', PaymentTransactionStatus::SUCCESSFUL)
            ->whereNotNull('paid_at')
            ->where('paid_at', '>=', $start)
            ->where('paid_at', '<', $end)
            ->orderByDesc('paid_at')
            ->orderByDesc('id')
            ->get();
    }

    public function getPaymentsByReceivable(
        CarbonInterface $start,
        CarbonInterface $end,
    ) {
        return PaymentAllocation::query()
            ->selectRaw('
            receivable_id,
            COUNT(*) as count,
            SUM(amount) as amount
        ')
            ->with('receivable:id,title,user_id')
            ->whereHas('payment.transactions', function ($query) use ($start, $end) {
                $query
                    ->where('status', PaymentTransactionStatus::SUCCESSFUL)
                    ->whereNotNull('paid_at')
                    ->where('paid_at', '>=', $start)
                    ->where('paid_at', '<', $end);
            })
            ->groupBy('receivable_id')
            ->orderBy('receivable_id')
            ->get();
    }

    public function getPaymentsReport(
        CarbonInterface $start,
        CarbonInterface $end,
    ): array {
        return [
            'summary' => $this->getPaymentsSummary($start, $end),

            'by_method' => $this->getPaymentsByMethod($start, $end),

            'by_account' => $this->getPaymentsByAccount($start, $end),

            'by_receivable' => $this->getPaymentsByReceivable($start, $end),

            'details' => $this->getPaymentDetails($start, $end),
        ];
    }

    public function getJalaliPaymentsReport(
        string $period,
        int $year,
        ?int $month = null,
        ?int $day = null,
    ): array {
        $range = match ($period) {
            'day' => JalaliDate::dayRange(
                $year,
                $month ?? throw new InvalidArgumentException('Month is required.'),
                $day ?? throw new InvalidArgumentException('Day is required.'),
            ),

            'week' => JalaliDate::weekRange(
                $year,
                $month ?? throw new InvalidArgumentException('Month is required.'),
                $day ?? throw new InvalidArgumentException('Day is required.'),
            ),

            'month' => JalaliDate::monthRange(
                $year,
                $month ?? throw new InvalidArgumentException('Month is required.'),
            ),

            'year' => JalaliDate::yearRange($year),

            default => throw new InvalidArgumentException('Invalid report period.'),
        };

        return $this->getPaymentsReport(
            $range['start'],
            $range['end'],
        );
    }

    public function getRefundsSummary(
        CarbonInterface $start,
        CarbonInterface $end,
    ): array {
        $query = Refund::query()
            ->where('status', RefundStatus::SUCCESSFUL)
            ->whereNotNull('refunded_at')
            ->where('refunded_at', '>=', $start)
            ->where('refunded_at', '<', $end);

        return [
            'count' => (clone $query)->count(),
            'amount' => (int) (clone $query)->sum('amount'),
        ];
    }

    public function getRefundsByMethod(
        CarbonInterface $start,
        CarbonInterface $end,
    ) {
        return Refund::query()
            ->selectRaw('
            payment_method_id,
            COUNT(*) as count,
            SUM(amount) as amount
        ')
            ->with('paymentMethod:id,name,code')
            ->where('status', RefundStatus::SUCCESSFUL)
            ->whereNotNull('refunded_at')
            ->where('refunded_at', '>=', $start)
            ->where('refunded_at', '<', $end)
            ->groupBy('payment_method_id')
            ->orderBy('payment_method_id')
            ->get();
    }

    public function getRefundsByAccount(
        CarbonInterface $start,
        CarbonInterface $end,
    ) {
        return Refund::query()
            ->selectRaw('
            financial_account_id,
            COUNT(*) as count,
            SUM(amount) as amount
        ')
            ->with('financialAccount:id,name,code')
            ->where('status', RefundStatus::SUCCESSFUL)
            ->whereNotNull('refunded_at')
            ->where('refunded_at', '>=', $start)
            ->where('refunded_at', '<', $end)
            ->groupBy('financial_account_id')
            ->orderBy('financial_account_id')
            ->get();
    }

    public function getRefundDetails(
        CarbonInterface $start,
        CarbonInterface $end,
    ) {
        return Refund::query()
            ->with([
                'paymentTransaction.payment.user',
                'paymentTransaction.paymentMethod',
                'paymentTransaction.financialAccount',
                'paymentMethod',
                'financialAccount',
                'creator',
            ])
            ->where('status', RefundStatus::SUCCESSFUL)
            ->whereNotNull('refunded_at')
            ->where('refunded_at', '>=', $start)
            ->where('refunded_at', '<', $end)
            ->orderByDesc('refunded_at')
            ->orderByDesc('id')
            ->get();
    }

    public function getRefundsReport(
        CarbonInterface $start,
        CarbonInterface $end,
    ): array {
        return [
            'summary' => $this->getRefundsSummary($start, $end),

            'by_method' => $this->getRefundsByMethod($start, $end),

            'by_account' => $this->getRefundsByAccount($start, $end),

            'details' => $this->getRefundDetails($start, $end),
        ];
    }

    public function getJalaliRefundsReport(
        string $period,
        int $year,
        ?int $month = null,
        ?int $day = null,
    ): array {
        $range = match ($period) {
            'day' => JalaliDate::dayRange(
                $year,
                $month ?? throw new InvalidArgumentException('Month is required.'),
                $day ?? throw new InvalidArgumentException('Day is required.'),
            ),

            'week' => JalaliDate::weekRange(
                $year,
                $month ?? throw new InvalidArgumentException('Month is required.'),
                $day ?? throw new InvalidArgumentException('Day is required.'),
            ),

            'month' => JalaliDate::monthRange(
                $year,
                $month ?? throw new InvalidArgumentException('Month is required.'),
            ),

            'year' => JalaliDate::yearRange($year),

            default => throw new InvalidArgumentException('Invalid report period.'),
        };

        return $this->getRefundsReport(
            $range['start'],
            $range['end'],
        );
    }

    public function getExpensesSummary(
        CarbonInterface $start,
        CarbonInterface $end,
    ): array {
        $query = Expense::query()
            ->where('paid_at', '>=', $start)
            ->where('paid_at', '<', $end);

        return [
            'count' => (clone $query)->count(),
            'amount' => (int) (clone $query)->sum('amount'),
        ];
    }

    public function getExpensesByCategory(
        CarbonInterface $start,
        CarbonInterface $end,
    ) {
        return Expense::query()
            ->selectRaw('
            expense_category_id,
            COUNT(*) as count,
            SUM(amount) as amount
        ')
            ->with('category:id,name,code')
            ->where('paid_at', '>=', $start)
            ->where('paid_at', '<', $end)
            ->groupBy('expense_category_id')
            ->orderBy('expense_category_id')
            ->get();
    }

    public function getExpensesByAccount(
        CarbonInterface $start,
        CarbonInterface $end,
    ) {
        return Expense::query()
            ->selectRaw('
            financial_account_id,
            COUNT(*) as count,
            SUM(amount) as amount
        ')
            ->with('financialAccount:id,name,code')
            ->where('paid_at', '>=', $start)
            ->where('paid_at', '<', $end)
            ->groupBy('financial_account_id')
            ->orderBy('financial_account_id')
            ->get();
    }

    public function getExpenseDetails(
        CarbonInterface $start,
        CarbonInterface $end,
    ) {
        return Expense::query()
            ->with([
                'category',
                'financialAccount',
                'creator',
            ])
            ->where('paid_at', '>=', $start)
            ->where('paid_at', '<', $end)
            ->orderByDesc('paid_at')
            ->orderByDesc('id')
            ->get();
    }

    public function getExpensesReport(
        CarbonInterface $start,
        CarbonInterface $end,
    ): array {
        return [
            'summary' => $this->getExpensesSummary($start, $end),

            'by_category' => $this->getExpensesByCategory($start, $end),

            'by_account' => $this->getExpensesByAccount($start, $end),

            'details' => $this->getExpenseDetails($start, $end),
        ];
    }

    public function getJalaliExpensesReport(
        string $period,
        int $year,
        ?int $month = null,
        ?int $day = null,
    ): array {
        $range = match ($period) {
            'day' => JalaliDate::dayRange(
                $year,
                $month ?? throw new InvalidArgumentException('Month is required.'),
                $day ?? throw new InvalidArgumentException('Day is required.'),
            ),

            'week' => JalaliDate::weekRange(
                $year,
                $month ?? throw new InvalidArgumentException('Month is required.'),
                $day ?? throw new InvalidArgumentException('Day is required.'),
            ),

            'month' => JalaliDate::monthRange(
                $year,
                $month ?? throw new InvalidArgumentException('Month is required.'),
            ),

            'year' => JalaliDate::yearRange($year),

            default => throw new InvalidArgumentException('Invalid report period.'),
        };

        return $this->getExpensesReport(
            $range['start'],
            $range['end'],
        );
    }

    public function getFinancialOverview(
        CarbonInterface $start,
        CarbonInterface $end,
    ): array {
        $payments = $this->getPaymentsSummary($start, $end);
        $refunds = $this->getRefundsSummary($start, $end);
        $expenses = $this->getExpensesSummary($start, $end);

        $netIncome = $payments['amount']
            - $refunds['amount']
            - $expenses['amount'];

        return [
            'income' => $payments['amount'],
            'refunds' => $refunds['amount'],
            'expenses' => $expenses['amount'],
            'net_income' => $netIncome,

            'payments_count' => $payments['count'],
            'refunds_count' => $refunds['count'],
            'expenses_count' => $expenses['count'],
        ];
    }

    public function getJalaliFinancialOverview(
        string $period,
        int $year,
        ?int $month = null,
        ?int $day = null,
    ): array {
        $range = match ($period) {
            'day' => JalaliDate::dayRange(
                $year,
                $month ?? throw new InvalidArgumentException('Month is required.'),
                $day ?? throw new InvalidArgumentException('Day is required.'),
            ),

            'week' => JalaliDate::weekRange(
                $year,
                $month ?? throw new InvalidArgumentException('Month is required.'),
                $day ?? throw new InvalidArgumentException('Day is required.'),
            ),

            'month' => JalaliDate::monthRange(
                $year,
                $month ?? throw new InvalidArgumentException('Month is required.'),
            ),

            'year' => JalaliDate::yearRange($year),

            default => throw new InvalidArgumentException('Invalid report period.'),
        };

        return $this->getFinancialOverview(
            $range['start'],
            $range['end'],
        );
    }

    public function getFinancialDetails(
        CarbonInterface $start,
        CarbonInterface $end,
    ): array {
        return [
            'payments' => $this->getPaymentDetails($start, $end),

            'refunds' => $this->getRefundDetails($start, $end),

            'expenses' => $this->getExpenseDetails($start, $end),
        ];
    }

    public function getJalaliFinancialReport(
        string $period,
        int $year,
        ?int $month = null,
        ?int $day = null,
    ): array {
        $range = match ($period) {
            'day' => JalaliDate::dayRange(
                $year,
                $month ?? throw new InvalidArgumentException('Month is required.'),
                $day ?? throw new InvalidArgumentException('Day is required.'),
            ),

            'week' => JalaliDate::weekRange(
                $year,
                $month ?? throw new InvalidArgumentException('Month is required.'),
                $day ?? throw new InvalidArgumentException('Day is required.'),
            ),

            'month' => JalaliDate::monthRange(
                $year,
                $month ?? throw new InvalidArgumentException('Month is required.'),
            ),

            'year' => JalaliDate::yearRange($year),

            default => throw new InvalidArgumentException('Invalid report period.'),
        };

        return [
            'overview' => $this->getFinancialOverview(
                $range['start'],
                $range['end'],
            ),

            'details' => $this->getFinancialDetails(
                $range['start'],
                $range['end'],
            ),

            'range' => $range,
        ];
    }
}
