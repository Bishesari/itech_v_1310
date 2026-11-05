<?php

declare(strict_types=1);

namespace App\Enums;

enum OrderStatus: string
{
    case PENDING = 'pending';
    case PAID = 'paid';
    case CANCELLED = 'cancelled';
    case EXPIRED = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'در انتظار پرداخت',
            self::PAID => 'پرداخت شده',
            self::CANCELLED => 'لغو شده',
            self::EXPIRED => 'منقضی شده',
        };
    }
}
