<?php

declare(strict_types=1);

namespace App\Enums;

enum RefundStatus: string
{
    case PENDING = 'pending';
    case SUCCESSFUL = 'successful';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'در انتظار بازپرداخت',
            self::SUCCESSFUL => 'بازپرداخت موفق',
            self::FAILED => 'بازپرداخت ناموفق',
            self::CANCELLED => 'لغو شده',
        };
    }
}
