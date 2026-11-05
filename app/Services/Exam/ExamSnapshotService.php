<?php

namespace App\Services\Exam;

use App\Models\Exam;
use App\Models\ExamQuestion;
use App\Models\ExamQuestionOption;
use App\Models\Question;
use DomainException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ExamSnapshotService
{
    public function create(Exam $exam, Collection $questions): void
    {
        $this->validateSnapshot($exam, $questions);

        if ($exam->questions()->exists()) {
            throw new DomainException('Snapshot این آزمون قبلاً ایجاد شده است.');
        }

        DB::transaction(function () use ($exam, $questions) {
            $this->store($exam, $questions);
        });
    }

    public function rebuild(Exam $exam, Collection $questions): void
    {
        $this->validateSnapshot($exam, $questions);

        DB::transaction(function () use ($exam, $questions) {
            $lockedExam = Exam::query()
                ->lockForUpdate()
                ->findOrFail($exam->id);

            if (
                $lockedExam->available_from === null ||
                now()->greaterThanOrEqualTo($lockedExam->available_from)
            ) {
                throw new DomainException(
                    'پس از شروع بازه آزمون یا در صورت شروع فوری، بازسازی Snapshot مجاز نیست.'
                );
            }

            $lockedExam->questions()->delete();

            $this->store($lockedExam, $questions);
        });
    }

    private function validateSnapshot(
        Exam $exam,
        Collection $questions
    ): void {
        if ($questions->count() !== $exam->question_count) {
            throw new DomainException(
                'تعداد سؤال‌ها با تعداد سؤال‌های آزمون مطابقت ندارد.'
            );
        }

        if ($questions->pluck('id')->unique()->count() !== $questions->count()) {
            throw new DomainException('سؤال تکراری در مجموعه وجود دارد.');
        }

        foreach ($questions as $question) {
            if (! $question instanceof Question) {
                throw new DomainException('مجموعه باید شامل مدل Question باشد.');
            }

            $options = $question->options;

            if ($options->count() !== 4) {
                throw new DomainException(
                    "سؤال شماره {$question->id} باید دقیقاً چهار گزینه داشته باشد."
                );
            }

            if ($options->where('is_correct', true)->count() !== 1) {
                throw new DomainException(
                    "سؤال شماره {$question->id} باید دقیقاً یک گزینه صحیح داشته باشد."
                );
            }
        }
    }

    private function store(Exam $exam, Collection $questions): void
    {
        foreach ($questions as $index => $question) {
            $examQuestion = $exam->questions()->create([
                'question_id' => $question->id,
                'position' => $index + 1,
                'question_snapshot' => [
                    'type' => $question->type?->value,
                    'text' => $question->text,
                    'explanation' => $question->explanation,
                    'difficulty' => $question->difficulty?->value,
                    'image_path' => $question->image_path,
                    'code' => $question->code,
                    'code_language' => $question->code_language?->value,
                ],
            ]);

            foreach ($question->options as $optionIndex => $option) {
                $examQuestion->options()->create([
                    'option_id' => $option->id,
                    'position' => $optionIndex + 1,
                    'option_snapshot' => [
                        'text' => $option->text,
                        'image_path' => $option->image_path,
                        'dir' => $option->dir,
                    ],
                    'is_correct' => $option->is_correct,
                ]);
            }
        }
    }
}
