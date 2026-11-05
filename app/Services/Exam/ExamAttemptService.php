<?php

declare(strict_types=1);

namespace App\Services\Exam;

use App\Enums\ExamAttemptStatus;
use App\Enums\ExamType;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamAttemptQuestion;
use App\Models\ExamAttemptQuestionOption;
use App\Models\ExamQuestion;
use App\Models\ExamQuestionOption;
use App\Models\Option;
use App\Models\Question;
use App\Models\User;
use BackedEnum;
use DomainException;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ExamAttemptService
{
    public function __construct(
        private readonly ExamQuestionSelectionService $questionSelectionService,
    ) {}

    public function start(Exam $exam, User $user): ExamAttempt
    {
        return DB::transaction(function () use ($exam, $user) {

            $lockedExam = Exam::query()
                ->lockForUpdate()
                ->findOrFail($exam->id);

            $startedAt = now();

            // Check exam availability.

            if (
                $lockedExam->available_from !== null &&
                $startedAt->lessThan($lockedExam->available_from)
            ) {
                throw new DomainException(
                    'آزمون هنوز در دسترس نیست.'
                );
            }

            if ($startedAt->greaterThan($lockedExam->available_until)) {
                throw new DomainException(
                    'مهلت شروع این آزمون به پایان رسیده است.'
                );
            }

            // Check previous attempt.

            $attemptExists = ExamAttempt::query()
                ->where('exam_id', $lockedExam->id)
                ->where('user_id', $user->id)
                ->exists();

            if ($attemptExists) {
                throw new DomainException(
                    'شما قبلاً در این آزمون شرکت کرده‌اید.'
                );
            }

            // Check Mock exam ownership.

            if ($lockedExam->type === ExamType::MOCK) {
                $productAccess = $lockedExam->productAccess()
                    ->first();

                if (
                    $productAccess === null ||
                    $productAccess->user_id !== $user->id
                ) {
                    throw new DomainException(
                        'شما مجاز به شرکت در این آزمون نیستید.'
                    );
                }
            }

            // Create attempt.

            $attempt = ExamAttempt::query()->create([
                'exam_id' => $lockedExam->id,
                'user_id' => $user->id,
                'status' => ExamAttemptStatus::IN_PROGRESS,
                'started_at' => $startedAt,
                'deadline_at' => $startedAt->copy()->addMinutes(
                    $lockedExam->duration_minutes
                ),
                'question_count' => $lockedExam->question_count,
                'duration_minutes' => $lockedExam->duration_minutes,
            ]);

            // Create attempt questions.

            if ($lockedExam->type === ExamType::QUIZ) {
                $this->createQuizQuestions($attempt, $lockedExam);
            } else {
                $this->createSnapshotQuestions($attempt, $lockedExam);
            }

            return $attempt->load('questions.options', 'questions.answer');
        });
    }

    /**
     * Create questions for a Quiz exam.
     */
    private function createQuizQuestions(
        ExamAttempt $attempt,
        Exam $exam,
    ): void {
        $questions = $this->questionSelectionService->select($exam);

        $this->validateQuestionCount(
            $questions,
            $exam->question_count
        );

        foreach ($questions->values() as $index => $question) {
            $question->loadMissing('options');

            $options = $question->options;

            $this->validateOptions($options);

            $attemptQuestion = $attempt->questions()->create([
                'question_id' => $question->id,
                'position' => $index + 1,
                'question_snapshot' => $this->makeQuestionSnapshot($question),
            ]);

            foreach ($options->values() as $optionIndex => $option) {
                $attemptQuestion->options()->create([
                    'option_id' => $option->id,
                    'position' => $optionIndex + 1,
                    'option_snapshot' => $this->makeOptionSnapshot($option),
                    'is_correct' => $option->is_correct,
                ]);
            }

            $this->createInitialAnswer($attemptQuestion);
        }
    }

    /**
     * Copy questions and options from the exam snapshots.
     */
    private function createSnapshotQuestions(
        ExamAttempt $attempt,
        Exam $exam,
    ): void {
        $examQuestions = ExamQuestion::query()
            ->where('exam_id', $exam->id)
            ->with([
                'options' => fn ($query) => $query->orderBy('position'),
            ])
            ->orderBy('position')
            ->get();

        $this->validateQuestionCount(
            $examQuestions,
            $exam->question_count
        );

        foreach ($examQuestions as $examQuestion) {
            $options = $examQuestion->options;

            $this->validateOptions($options);

            $attemptQuestion = $attempt->questions()->create([
                'question_id' => $examQuestion->question_id,
                'position' => $examQuestion->position,
                'question_snapshot' => $examQuestion->question_snapshot,
            ]);

            foreach ($options as $examOption) {
                $attemptQuestion->options()->create([
                    'option_id' => $examOption->option_id,
                    'position' => $examOption->position,
                    'option_snapshot' => $examOption->option_snapshot,
                    'is_correct' => $examOption->is_correct,
                ]);
            }

            $this->createInitialAnswer($attemptQuestion);
        }
    }

    /**
     * Create an empty answer record for each question.
     */
    private function createInitialAnswer(
        ExamAttemptQuestion $attemptQuestion,
    ): void {
        $attemptQuestion->answer()->create([
            'selected_option_id' => null,
            'is_marked_for_review' => false,
            'answered_at' => null,
        ]);
    }

    /**
     * Build question snapshot.
     */
    private function makeQuestionSnapshot(Question $question): array
    {
        return [
            'type' => $this->enumValue($question->type),
            'text' => $question->text,
            'explanation' => $question->explanation,
            'difficulty' => $this->enumValue($question->difficulty),
            'image_path' => $question->image_path,
            'code' => $question->code,
            'code_language' => $this->enumValue($question->code_language),
        ];
    }

    /**
     * Build option snapshot.
     */
    private function makeOptionSnapshot(Option $option): array
    {
        return [
            'text' => $option->text,
            'image_path' => $option->image_path,
            'dir' => $option->dir,
        ];
    }

    /**
     * Validate question count.
     */
    private function validateQuestionCount(
        Collection $questions,
        int $expectedCount,
    ): void {
        if (
            $expectedCount <= 0 ||
            $questions->count() !== $expectedCount
        ) {
            throw new DomainException(
                'تعداد سؤال‌های آزمون با تعداد مورد انتظار مطابقت ندارد.'
            );
        }
    }

    /**
     * Validate question options.
     */
    private function validateOptions(Collection $options): void
    {
        if ($options->count() !== 4) {
            throw new DomainException(
                'هر سؤال آزمون باید دقیقاً چهار گزینه داشته باشد.'
            );
        }

        $correctCount = $options->where('is_correct', true)->count();

        if ($correctCount !== 1) {
            throw new DomainException(
                'هر سؤال باید دقیقاً یک گزینه صحیح داشته باشد.'
            );
        }
    }

    /**
     * Resolve backed enum values.
     */
    private function enumValue(mixed $value): mixed
    {
        return $value instanceof BackedEnum
            ? $value->value
            : $value;
    }
}
