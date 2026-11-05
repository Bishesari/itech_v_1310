<?php

declare(strict_types=1);

namespace App\Services\Exam;

use App\Enums\ExamAttemptStatus;
use App\Enums\ExamType;
use App\Models\ExamAttempt;
use App\Models\ExamAttemptQuestion;
use App\Models\User;
use DomainException;

readonly class ExamResultService
{
    public function __construct(
        private ExamAttemptStatusService $attemptStatusService,
    ) {}

    /**
     * Get exam attempt result.
     */
    public function getResult(
        ExamAttempt $attempt,
        User $user,
    ): array {
        // Check ownership.

        if ($attempt->user_id !== $user->id) {
            throw new DomainException(
                'شما مجاز به مشاهده نتیجه این آزمون نیستید.'
            );
        }

        // Refresh attempt status and finalize if expired.

        $attempt = $this->attemptStatusService->refresh($attempt);

        if ($attempt->status !== ExamAttemptStatus::SUBMITTED) {
            throw new DomainException(
                'آزمون هنوز به پایان نرسیده است.'
            );
        }

        $attempt->load([
            'exam',
            'questions' => fn ($query) => $query->orderBy('position'),
            'questions.options' => fn ($query) => $query->orderBy('position'),
            'questions.answer',
        ]);

        $canReviewAnswers = $this->canReviewAnswers($attempt);

        return [
            'summary' => [
                'score' => $attempt->score !== null
                    ? (float) $attempt->score
                    : null,

                'correct_count' => $attempt->correct_count,

                'question_count' => $attempt->question_count,

                'passing_score' => $attempt->exam->passing_score !== null
                    ? (float) $attempt->exam->passing_score
                    : null,

                'passed' => $attempt->exam->passing_score !== null
                    ? (float) $attempt->score >= (float) $attempt->exam->passing_score
                    : null,

                'status' => $attempt->status->value,
            ],

            'can_review_answers' => $canReviewAnswers,

            'questions' => $attempt->questions->map(
                fn (ExamAttemptQuestion $question) => $this->formatQuestion(
                    $question,
                    $canReviewAnswers
                )
            )->values()->all(),
        ];
    }

    /**
     * Determine whether correct answers and explanations can be shown.
     */
    private function canReviewAnswers(ExamAttempt $attempt): bool
    {
        // Mock answers are available immediately after completion.

        if ($attempt->exam->type === ExamType::MOCK) {
            return true;
        }

        // Internal exam answers are available after
        // available_until + duration_minutes.

        $reviewAvailableAt = $attempt->exam->available_until
            ->copy()
            ->addMinutes($attempt->exam->duration_minutes);

        return now()->greaterThanOrEqualTo($reviewAvailableAt);
    }

    /**
     * Format an attempt question.
     */
    private function formatQuestion(
        ExamAttemptQuestion $question,
        bool $canReviewAnswers,
    ): array {
        $snapshot = $question->question_snapshot;

        $answer = $question->answer;

        $selectedOptionId = $answer?->selected_option_id;

        $result = [
            'id' => $question->id,
            'position' => $question->position,

            'question' => [
                'type' => $snapshot['type'] ?? null,
                'text' => $snapshot['text'] ?? null,
                'difficulty' => $snapshot['difficulty'] ?? null,
                'image_path' => $snapshot['image_path'] ?? null,
                'code' => $snapshot['code'] ?? null,
                'code_language' => $snapshot['code_language'] ?? null,
            ],

            'selected_option_id' => $selectedOptionId,

            'is_marked_for_review' => $answer?->is_marked_for_review ?? false,

            'is_answered' => $selectedOptionId !== null,

            'is_correct' => $canReviewAnswers
                ? $question->options->contains(
                    fn ($option) =>
                        (int) $option->id === (int) $selectedOptionId &&
                        $option->is_correct
                )
                : null,

            'options' => $question->options->map(
                function ($option) use (
                    $selectedOptionId,
                    $canReviewAnswers
                ) {
                    $item = [
                        'id' => $option->id,
                        'position' => $option->position,
                        'snapshot' => $option->option_snapshot,
                        'is_selected' => $selectedOptionId !== null && (int) $option->id === (int) $selectedOptionId,
                    ];

                    if ($canReviewAnswers) {
                        $item['is_correct'] = $option->is_correct;
                    }

                    return $item;
                }
            )->values()->all(),
        ];

        if ($canReviewAnswers) {
            $result['question']['explanation'] =
                $snapshot['explanation'] ?? null;
        }

        return $result;
    }
}
