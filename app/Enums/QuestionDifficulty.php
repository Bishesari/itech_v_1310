<?php

namespace App\Enums;

enum QuestionDifficulty: string
{
    case Easy = 'easy';
    case Medium = 'medium';
    case Hard = 'hard';

    public function label(): string
    {
        return match ($this) {
            self::Easy => 'آسان',
            self::Medium => 'متوسط',
            self::Hard => 'سخت',
        };
    }
}
