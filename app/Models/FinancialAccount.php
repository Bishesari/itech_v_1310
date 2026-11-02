<?php

namespace App\Models;

use App\Enums\FinancialAccountType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinancialAccount extends Model
{
    protected function casts(): array
    {
        return [
            'type' => FinancialAccountType::class,
            'is_active' => 'boolean',
        ];
    }

    public function paymentMethods(): BelongsToMany
    {
        return $this->belongsToMany(
            PaymentMethod::class,
            'financial_account_payment_method'
        )->withTimestamps();
    }
    public function paymentTransactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    public function financialTransactions(): HasMany
    {
        return $this->hasMany(FinancialTransaction::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    public function outgoingTransfers(): HasMany
    {
        return $this->hasMany(AccountTransfer::class, 'from_account_id');
    }

    public function incomingTransfers(): HasMany
    {
        return $this->hasMany(AccountTransfer::class, 'to_account_id');
    }
}
