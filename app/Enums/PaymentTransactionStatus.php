<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentTransactionStatus: string
{
    case PENDING = 'pending';
    case SUCCESSFUL = 'successful';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'در انتظار پرداخت',
            self::SUCCESSFUL => 'موفق',
            self::FAILED => 'ناموفق',
            self::CANCELLED => 'لغو شده',
        };
    }
}
