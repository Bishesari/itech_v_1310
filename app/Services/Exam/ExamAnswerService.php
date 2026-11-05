<?php

declare(strict_types=1);

namespace App\Services\Exam;

use App\Enums\ExamAttemptStatus;
use App\Models\ExamAttempt;
use App\Models\ExamAttemptAnswer;
use App\Models\ExamAttemptQuestion;
use App\Models\ExamAttemptQuestionOption;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

class ExamAnswerService
{
    public function __construct(
        private readonly ExamAttemptStatusService $attemptStatusService,
    ) {}

    /**
     * Save or clear an answer.
     */
    public function save(
        ExamAttemptQuestion $question,
        User $user,
        ?int $selectedOptionId,
    ): ExamAttemptAnswer {
        $answer = DB::transaction(function () use (
            $question,
            $user,
            $selectedOptionId
        ): ?ExamAttemptAnswer {
            $attemptQuestion = $this->getWritableQuestion(
                $question,
                $user
            );

            if ($attemptQuestion === null) {
                return null;
            }

            if ($selectedOptionId !== null) {
                $optionExists = ExamAttemptQuestionOption::query()
                    ->where('exam_attempt_question_id', $attemptQuestion->id)
                    ->where('id', $selectedOptionId)
                    ->exists();

                if (! $optionExists) {
                    throw new DomainException(
                        'گزینه انتخاب‌شده متعلق به این سؤال نیست.'
                    );
                }
            }

            $answer = $attemptQuestion->answer()->firstOrCreate([]);

            $answer->update([
                'selected_option_id' => $selectedOptionId,
                'answered_at' => $selectedOptionId !== null
                    ? now()
                    : null,
            ]);

            return $answer->refresh();
        });

        if ($answer === null) {
            $this->finalizeExpiredAttempt($question);

            throw new DomainException(
                'مهلت پاسخ‌گویی به پایان رسیده است.'
            );
        }

        return $answer;
    }

    /**
     * Mark or unmark a question for review.
     */
    public function markForReview(
        ExamAttemptQuestion $question,
        User $user,
        bool $marked,
    ): ExamAttemptAnswer {
        $answer = DB::transaction(function () use (
            $question,
            $user,
            $marked
        ): ?ExamAttemptAnswer {
            $attemptQuestion = $this->getWritableQuestion(
                $question,
                $user
            );

            if ($attemptQuestion === null) {
                return null;
            }

            $answer = $attemptQuestion->answer()->firstOrCreate([]);

            $answer->update([
                'is_marked_for_review' => $marked,
            ]);

            return $answer->refresh();
        });

        if ($answer === null) {
            $this->finalizeExpiredAttempt($question);

            throw new DomainException(
                'مهلت پاسخ‌گویی به پایان رسیده است.'
            );
        }

        return $answer;
    }

    /**
     * Get a writable question.
     *
     * Returns null when the attempt deadline has expired.
     */
    private function getWritableQuestion(
        ExamAttemptQuestion $question,
        User $user,
    ): ?ExamAttemptQuestion {
        $attempt = ExamAttempt::query()
            ->where('id', $question->exam_attempt_id)
            ->where('user_id', $user->id)
            ->lockForUpdate()
            ->first();

        if ($attempt === null) {
            throw new DomainException(
                'آزمون موردنظر متعلق به شما نیست.'
            );
        }

        if ($attempt->status !== ExamAttemptStatus::IN_PROGRESS) {
            throw new DomainException(
                'این آزمون قبلاً به پایان رسیده است.'
            );
        }

        if (now()->greaterThanOrEqualTo($attempt->deadline_at)) {
            return null;
        }

        return ExamAttemptQuestion::query()
            ->where('id', $question->id)
            ->where('exam_attempt_id', $attempt->id)
            ->lockForUpdate()
            ->firstOrFail();
    }

    /**
     * Finalize an expired attempt outside the current transaction.
     */
    private function finalizeExpiredAttempt(
        ExamAttemptQuestion $question,
    ): void {
        $attempt = ExamAttempt::query()
            ->findOrFail($question->exam_attempt_id);

        $this->attemptStatusService->refresh($attempt);
    }
}
