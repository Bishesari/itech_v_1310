<?php

declare(strict_types=1);

namespace App\Services\Exam;

use App\Enums\ExamAttemptStatus;
use App\Models\ExamAttempt;

class ExamAttemptStatusService
{
    public function __construct(
        private readonly ExamGradingService $gradingService,
    ) {}

    /**
     * Refresh attempt status and finalize it if expired.
     */
    public function refresh(ExamAttempt $attempt): ExamAttempt
    {
        $attempt->refresh();

        if ($attempt->status === ExamAttemptStatus::SUBMITTED) {
            return $attempt;
        }

        if (now()->greaterThanOrEqualTo($attempt->deadline_at)) {
            return $this->gradingService->finalize($attempt);
        }

        return $attempt;
    }
}
