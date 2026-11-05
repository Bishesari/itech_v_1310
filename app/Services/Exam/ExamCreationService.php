<?php

declare(strict_types=1);

namespace App\Services\Exam;

use App\Enums\ExamType;
use App\Models\Chapter;
use App\Models\Exam;
use App\Models\ProductAccess;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

class ExamCreationService
{
    public function __construct(
        private readonly ExamQuestionSelectionService $questionSelectionService,
        private readonly ExamSnapshotService $snapshotService,
    ) {}

    public function create(array $data, User $creator): Exam
    {
        return DB::transaction(function () use ($data, $creator) {
            $type = $this->resolveType($data['type']);

            $standardId = $data['standard_id'] ?? null;

            if ($type === ExamType::MOCK) {
                $productAccess = ProductAccess::query()
                    ->with('product')
                    ->lockForUpdate()
                    ->findOrFail($data['product_access_id']);

                if ($productAccess->user_id !== $creator->id) {
                    throw new DomainException(
                        'این دسترسی متعلق به کاربر جاری نیست.'
                    );
                }

                if ($productAccess->exam()->exists()) {
                    throw new DomainException(
                        'برای این خرید قبلاً آزمون ایجاد شده است.'
                    );
                }

                $standardId = $productAccess->product->standard_id;
            }

            $exam = Exam::query()->create([
                'standard_id' => $standardId,
                'product_access_id' => $type === ExamType::MOCK
                    ? $productAccess->id
                    : null,
                'created_by' => $creator->id,
                'title' => $data['title'],
                'type' => $type,
                'description' => $data['description'] ?? null,
                'question_count' => $data['question_count'],
                'duration_minutes' => $data['duration_minutes'],
                'passing_score' => $data['passing_score'] ?? null,
                'available_from' => $data['available_from'] ?? null,
                'available_until' => $data['available_until'],
            ]);

            if ($type === ExamType::QUIZ) {
                $this->attachChapters(
                    $exam,
                    $data['chapter_ids'],
                    $standardId
                );
            }

            if (in_array($type, [
                ExamType::MIDTERM,
                ExamType::FINAL,
                ExamType::MOCK,
            ], true)) {
                $questions = $this->questionSelectionService->select($exam);

                $this->snapshotService->create($exam, $questions);
            }

            return $exam;
        });
    }

    private function resolveType(ExamType|string $type): ExamType
    {
        if ($type instanceof ExamType) {
            return $type;
        }

        $resolvedType = ExamType::tryFrom($type);

        if ($resolvedType === null) {
            throw new DomainException('نوع آزمون معتبر نیست.');
        }

        return $resolvedType;
    }

    private function attachChapters(
        Exam $exam,
        array $chapterIds,
        int $standardId
    ): void {
        $uniqueChapterIds = array_unique($chapterIds);

        if (count($uniqueChapterIds) !== count($chapterIds)) {
            throw new DomainException('فصل تکراری در فهرست انتخاب‌شده وجود دارد.');
        }

        $validChapterCount = Chapter::query()
            ->where('standard_id', $standardId)
            ->whereIn('id', $uniqueChapterIds)
            ->count();

        if ($validChapterCount !== count($uniqueChapterIds)) {
            throw new DomainException(
                'یک یا چند فصل انتخاب‌شده متعلق به استاندارد آزمون نیستند.'
            );
        }

        $exam->chapters()->attach($uniqueChapterIds);
    }
}
