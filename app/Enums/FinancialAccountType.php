<?php

declare(strict_types=1);

namespace App\Enums;

enum FinancialAccountType: string
{
    case CASH = 'cash';
    case BANK = 'bank';
    case GATEWAY = 'gateway';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::CASH => 'صندوق نقدی',
            self::BANK => 'حساب بانکی',
            self::GATEWAY => 'درگاه پرداخت',
            self::OTHER => 'سایر',
        };
    }
}
