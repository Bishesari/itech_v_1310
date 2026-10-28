<?php

declare(strict_types=1);

namespace App\Enums;

enum ContactType: string
{
    case Mobile = 'mobile';
    case Landline = 'phone';
    case Email = 'email';
    case Telegram = 'telegram';
    case WhatsApp = 'whatsapp';

    public function label(): string
    {
        return match ($this) {
            self::Mobile => 'موبایل',
            self::Landline => 'تلفن ثابت',
            self::Email => 'ایمیل',
            self::Telegram => 'تلگرام',
            self::WhatsApp => 'واتساپ',
        };
    }
}
