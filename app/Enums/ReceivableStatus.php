<?php

declare(strict_types=1);

namespace App\Enums;

enum ReceivableStatus: string
{
    case UNPAID = 'unpaid';
    case PARTIALLY_PAID = 'partially_paid';
    case PAID = 'paid';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::UNPAID => 'پرداخت نشده',
            self::PARTIALLY_PAID => 'پرداخت جزئی',
            self::PAID => 'پرداخت شده',
            self::CANCELLED => 'لغو شده',
        };
    }
}
