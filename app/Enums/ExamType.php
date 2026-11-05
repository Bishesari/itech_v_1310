<?php

declare(strict_types=1);

namespace App\Enums;

enum ExamType: string
{
    case QUIZ = 'quiz';
    case MIDTERM = 'midterm';
    case FINAL = 'final';
    case MOCK = 'mock';

    public function label(): string
    {
        return match ($this) {
            self::QUIZ => 'آزمون کلاسی',
            self::MIDTERM => 'آزمون میان‌ترم',
            self::FINAL => 'آزمون پایان‌ترم',
            self::MOCK => 'آزمون آزمایشی',
        };
    }
}
