<?php

declare(strict_types=1);

namespace App\Enums;

enum AccountTransferStatus: string
{
    case PENDING = 'pending';
    case SUCCESSFUL = 'successful';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'در انتظار انتقال',
            self::SUCCESSFUL => 'انتقال موفق',
            self::FAILED => 'انتقال ناموفق',
            self::CANCELLED => 'لغو شده',
        };
    }
}
