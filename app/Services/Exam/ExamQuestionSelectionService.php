<?php

namespace App\Services\Exam;

use App\Enums\ExamType;
use App\Models\Chapter;
use App\Models\Exam;
use App\Models\Question;
use DomainException;
use Illuminate\Database\Eloquent\Collection;

class ExamQuestionSelectionService
{
    public function select(Exam $exam): Collection
    {
        $isQuiz = $exam->type === ExamType::QUIZ;

        if (! $isQuiz && ! in_array($exam->type, [
                ExamType::MIDTERM,
                ExamType::FINAL,
                ExamType::MOCK,
            ], true)) {
            throw new DomainException('نوع آزمون معتبر نیست.');
        }

        $questionCount = (int) $exam->question_count;

        if ($questionCount < 1) {
            throw new DomainException('تعداد سؤال‌های آزمون معتبر نیست.');
        }

        // تعیین فصل‌های مجاز
        $chapters = $isQuiz
            ? $exam->chapters()->get()
            : $exam->standard()->firstOrFail()->chapters()->get();

        if ($chapters->isEmpty()) {
            throw new DomainException('هیچ فصلی برای انتخاب سؤال وجود ندارد.');
        }

        $frequentlyFinal = ! $isQuiz;

        // شمارش سؤال‌های واجد شرایط هر فصل
        $chapterIds = $chapters->modelKeys();

        $counts = Question::query()
            ->whereIn('chapter_id', $chapterIds)
            ->where('is_active', true)
            ->when(
                $frequentlyFinal,
                fn ($query) => $query->where('is_frequently_final', true)
            )
            ->selectRaw('chapter_id, COUNT(*) as aggregate')
            ->groupBy('chapter_id')
            ->pluck('aggregate', 'chapter_id');

        $capacities = [];

        foreach ($chapterIds as $chapterId) {
            $capacities[$chapterId] = (int) ($counts[$chapterId] ?? 0);
        }

        $totalAvailable = array_sum($capacities);

        if ($totalAvailable < $questionCount) {
            throw new DomainException(
                "تعداد سؤال‌های واجد شرایط کافی نیست. تعداد موجود: {$totalAvailable}"
            );
        }

        // محاسبه سهم هر فصل
        $allocations = $isQuiz
            ? $this->allocateProportionally($capacities, $questionCount)
            : $this->allocateEqually($capacities, $questionCount);

        // انتخاب تصادفی سؤال‌ها
        $selected = new Collection();

        foreach ($chapters as $chapter) {
            $count = $allocations[$chapter->id] ?? 0;

            if ($count === 0) {
                continue;
            }

            $questions = $this->eligibleQuestionsQuery(
                $chapter,
                $frequentlyFinal
            )
                ->inRandomOrder()
                ->limit($count)
                ->get();

            if ($questions->count() !== $count) {
                throw new DomainException(
                    'تعداد سؤال‌های انتخاب‌شده با سهمیه فصل مطابقت ندارد.'
                );
            }

            foreach ($questions as $question) {
                $selected->push($question);
            }
        }

        if ($selected->count() !== $questionCount) {
            throw new DomainException(
                'تعداد سؤال‌های انتخاب‌شده با تعداد درخواستی مطابقت ندارد.'
            );
        }

        // تصادفی‌سازی ترتیب نهایی سؤال‌ها
        return $selected->shuffle()->values();
    }

    /**
     * Query سؤال‌های واجد شرایط یک فصل
     */
    private function eligibleQuestionsQuery(
        Chapter $chapter,
        bool $frequentlyFinal
    ) {
        return $chapter->questions()
            ->where('is_active', true)
            ->when(
                $frequentlyFinal,
                fn ($query) => $query->where('is_frequently_final', true)
            );
    }

    /**
     * توزیع متناسب با روش Largest Remainder (Hamilton)
     */
    private function allocateProportionally(
        array $capacities,
        int $total
    ): array {
        $totalCapacity = array_sum($capacities);

        if ($totalCapacity < $total) {
            throw new DomainException('ظرفیت سؤال‌های موجود کافی نیست.');
        }

        $allocations = [];
        $remainders = [];
        $allocated = 0;

        foreach ($capacities as $chapterId => $capacity) {
            $allocations[$chapterId] = 0;

            if ($capacity === 0) {
                continue;
            }

            $numerator = $total * $capacity;

            $quota = intdiv($numerator, $totalCapacity);
            $remainder = $numerator % $totalCapacity;

            $allocations[$chapterId] = $quota;
            $remainders[$chapterId] = $remainder;

            $allocated += $quota;
        }

        // مرتب‌سازی باقیمانده‌ها به‌صورت نزولی
        // در صورت تساوی، ترتیب اولیه فصل‌ها حفظ می‌شود.
        $chapterOrder = array_flip(array_keys($capacities));

        uksort(
            $remainders,
            fn ($a, $b) =>
            ($remainders[$b] <=> $remainders[$a])
                ?: ($chapterOrder[$a] <=> $chapterOrder[$b])
        );

        $remaining = $total - $allocated;

        foreach (array_keys($remainders) as $chapterId) {
            if ($remaining === 0) {
                break;
            }

            $allocations[$chapterId]++;
            $remaining--;
        }

        return $allocations;
    }

    /**
     * توزیع مساوی با درنظرگرفتن ظرفیت فصل‌ها
     */
    private function allocateEqually(
        array $capacities,
        int $total
    ): array {
        $allocations = array_fill_keys(array_keys($capacities), 0);

        if (array_sum($capacities) < $total) {
            throw new DomainException('ظرفیت سؤال‌های موجود کافی نیست.');
        }

        $remaining = $total;

        while ($remaining > 0) {
            $active = [];

            foreach ($capacities as $chapterId => $capacity) {
                if ($allocations[$chapterId] < $capacity) {
                    $active[] = $chapterId;
                }
            }

            if (empty($active)) {
                throw new DomainException('ظرفیت فصل‌ها برای توزیع کافی نیست.');
            }

            $chapterCount = count($active);

            $base = intdiv($remaining, $chapterCount);
            $extra = $remaining % $chapterCount;

            $proposed = [];
            $overflow = [];

            foreach ($active as $index => $chapterId) {
                $share = $base + ($index < $extra ? 1 : 0);

                $available = $capacities[$chapterId]
                    - $allocations[$chapterId];

                $proposed[$chapterId] = $share;

                if ($share > $available) {
                    $overflow[] = $chapterId;
                }
            }

            // فصل‌های کم‌ظرفیت تکمیل می‌شوند
            // و سهم باقی‌مانده مجدداً توزیع می‌شود.
            if (! empty($overflow)) {
                foreach ($overflow as $chapterId) {
                    $available = $capacities[$chapterId]
                        - $allocations[$chapterId];

                    $allocations[$chapterId] += $available;
                    $remaining -= $available;
                }

                continue;
            }

            // تمام سهم‌ها قابل تخصیص هستند.
            foreach ($proposed as $chapterId => $share) {
                $allocations[$chapterId] += $share;
                $remaining -= $share;
            }
        }

        return $allocations;
    }
}
