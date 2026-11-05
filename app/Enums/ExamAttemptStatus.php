<?php

declare(strict_types=1);

namespace App\Enums;

enum ExamAttemptStatus: string
{
    case IN_PROGRESS = 'in_progress';
    case SUBMITTED = 'submitted';

    public function label(): string
    {
        return match ($this) {
            self::IN_PROGRESS => 'در حال برگزاری',
            self::SUBMITTED => 'پایان یافته',
        };
    }
}
