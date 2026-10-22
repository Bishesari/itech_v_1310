<?php

declare(strict_types=1);

namespace App\Support;

final class MobileNumber
{
    /**
     * شماره موبایل ایران را به قالب 09xxxxxxxxx تبدیل می‌کند.
     */
    public static function normalize(?string $value): ?string
    {
        $number = Digits::toEnglish($value);
        $number = preg_replace('/[\s\-\(\)]+/u', '', $number) ?? '';

        if (str_starts_with($number, '+98')) {
            $number = '0'.substr($number, 3);
        } elseif (str_starts_with($number, '0098')) {
            $number = '0'.substr($number, 4);
        } elseif (preg_match('/^9\d{9}$/', $number)) {
            $number = '0'.$number;
        }

        return self::isValid($number) ? $number : null;
    }

    /**
     * اعتبارسنجی قالب استاندارد موبایل ایران.
     */
    public static function isValid(?string $number): bool
    {
        return $number !== null
            && preg_match('/^09\d{9}$/', $number) === 1;
    }
}
