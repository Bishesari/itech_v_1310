<?php

namespace App\Enums;

enum QuestionType: string
{
    case MultipleChoice = 'multiple_choice';
    case Practical = 'practical';

    public function label(): string
    {
        return match ($this) {
            self::MultipleChoice => 'چهارگزینه‌ای',
            self::Practical => 'عملی',
        };
    }
}
