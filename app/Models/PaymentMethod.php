<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentMethod extends Model
{
    public function financialAccounts(): BelongsToMany
    {
        return $this->belongsToMany(
            FinancialAccount::class,
            'financial_account_payment_method'
        )->withTimestamps();
    }

    public function paymentTransactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class);
    }
}
