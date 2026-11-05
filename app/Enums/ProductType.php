<?php

declare(strict_types=1);

namespace App\Enums;

enum ProductType: string
{
    case WRITTEN_FREQUENT_QUESTIONS = 'written_frequent_questions';
    case MOCK_EXAM = 'mock_exam';

    public function label(): string
    {
        return match ($this) {
            self::WRITTEN_FREQUENT_QUESTIONS => 'نمونه سؤالات پرتکرار کتبی',
            self::MOCK_EXAM => 'آزمون آزمایشی',
        };
    }
}
