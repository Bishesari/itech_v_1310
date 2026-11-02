<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentStatus: string
{
    case PENDING = 'pending';
    case PARTIALLY_PAID = 'partially_paid';
    case SUCCESSFUL = 'successful';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'در انتظار پرداخت',
            self::PARTIALLY_PAID => 'پرداخت جزئی',
            self::SUCCESSFUL => 'پرداخت موفق',
            self::FAILED => 'پرداخت ناموفق',
            self::CANCELLED => 'لغو شده',
        };
    }
}
