<?php

declare(strict_types=1);

namespace App\Services\Exam;

use App\Enums\ExamAttemptStatus;
use App\Models\ExamAttempt;
use DomainException;
use Illuminate\Support\Facades\DB;

class ExamGradingService
{
    public function submit(ExamAttempt $attempt): ExamAttempt
    {
        return $this->grade($attempt, false);
    }

    public function finalize(ExamAttempt $attempt): ExamAttempt
    {
        return $this->grade($attempt, true);
    }

    private function grade(
        ExamAttempt $attempt,
        bool $requireDeadline
    ): ExamAttempt {
        return DB::transaction(function () use ($attempt, $requireDeadline) {
            $lockedAttempt = ExamAttempt::query()
                ->lockForUpdate()
                ->findOrFail($attempt->id);

            if ($lockedAttempt->status === ExamAttemptStatus::SUBMITTED) {
                return $lockedAttempt;
            }

            $now = now();

            if (
                $requireDeadline &&
                $now->lessThan($lockedAttempt->deadline_at)
            ) {
                throw new DomainException(
                    'مهلت آزمون هنوز به پایان نرسیده است.'
                );
            }

            $lockedAttempt->load([
                'questions.options',
                'questions.answer',
            ]);

            $questions = $lockedAttempt->questions;

            if (
                $questions->count() !== $lockedAttempt->question_count ||
                $lockedAttempt->question_count === 0
            ) {
                throw new DomainException(
                    'تعداد سؤال‌های تلاش با تعداد ثبت‌شده مطابقت ندارد.'
                );
            }

            $correctCount = 0;

            foreach ($questions as $question) {
                $correctOptions = $question->options
                    ->where('is_correct', true);

                if ($question->options->count() !== 4) {
                    throw new DomainException(
                        "سؤال شماره {$question->id} باید دقیقاً چهار گزینه داشته باشد."
                    );
                }

                if ($correctOptions->count() !== 1) {
                    throw new DomainException(
                        "سؤال شماره {$question->id} باید دقیقاً یک گزینه صحیح داشته باشد."
                    );
                }

                $selectedOptionId = $question->answer?->selected_option_id;

                if ($selectedOptionId === null) {
                    continue;
                }

                if ((int) $correctOptions->first()->id === (int) $selectedOptionId) {
                    $correctCount++;
                }
            }

            $score = round(
                ($correctCount / $lockedAttempt->question_count) * 100,
                2
            );

            $lockedAttempt->update([
                'status' => ExamAttemptStatus::SUBMITTED,
                'submitted_at' => $now,
                'correct_count' => $correctCount,
                'score' => $score,
            ]);

            return $lockedAttempt->refresh();
        });
    }
}
