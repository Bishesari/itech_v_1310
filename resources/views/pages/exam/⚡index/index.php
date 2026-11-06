<?php

declare(strict_types=1);

use App\Enums\ExamAttemptStatus;
use App\Enums\ExamType;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\User;
use App\Services\Exam\ExamAttemptService;
use App\Services\Exam\ExamAttemptStatusService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

new class extends Component
{
    public array $availableExams = [];

    public array $inProgressExams = [];

    public array $completedExams = [];

    public function mount(): void
    {
        $this->loadExams();
    }

    public function loadExams(): void
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        $now = now();

        $exams = Exam::query()
            ->with([
                'attempts' => fn ($query) => $query
                    ->where('user_id', $user->id),
            ])
            ->orderByDesc('created_at')
            ->get();

        // شناسه محصولات آزمون آزمایشی
        $mockProductIds = $exams
            ->where('type', ExamType::MOCK)
            ->pluck('product_id')
            ->filter()
            ->unique();

        // دسترسی‌های خریداری‌شده کاربر
        $productAccesses = ProductAccess::query()
            ->where('user_id', $user->id)
            ->whereIn('product_id', $mockProductIds)
            ->get()
            ->keyBy('id');

        // تلاش‌های مربوط به دسترسی‌های آزمون آزمایشی
        $mockAttempts = ExamAttempt::query()
            ->where('user_id', $user->id)
            ->whereIn('product_access_id', $productAccesses->keys())
            ->get()
            ->keyBy('product_access_id');

        $statusService = app(ExamAttemptStatusService::class);

        $available = [];
        $inProgress = [];
        $completed = [];

        foreach ($exams as $exam) {

            $isAvailable = (
                    $exam->available_from === null ||
                    $exam->available_from->lessThanOrEqualTo($now)
                ) && $exam->available_until->greaterThanOrEqualTo($now);

            // آزمون آزمایشی
            if ($exam->type === ExamType::MOCK) {

                $accesses = $productAccesses->where(
                    'product_id',
                    $exam->product_id
                );

                foreach ($accesses as $access) {

                    $attempt = $mockAttempts->get($access->id);

                    if ($attempt) {

                        $attempt = $statusService->refresh($attempt);

                        $item = $this->formatExam(
                            $exam,
                            $attempt,
                            $access->id
                        );

                        if ($attempt->status === ExamAttemptStatus::IN_PROGRESS) {
                            $inProgress[] = $item;
                        } elseif ($attempt->status === ExamAttemptStatus::SUBMITTED) {
                            $completed[] = $item;
                        }

                        continue;
                    }

                    if ($isAvailable) {
                        $available[] = $this->formatExam(
                            $exam,
                            null,
                            $access->id
                        );
                    }
                }

                continue;
            }

            // آزمون‌های داخلی: منطق قبلی حفظ می‌شود.
            /** @var ExamAttempt|null $attempt */
            $attempt = $exam->attempts->first();

            if ($attempt) {

                $attempt = $statusService->refresh($attempt);

                $item = $this->formatExam($exam, $attempt);

                if ($attempt->status === ExamAttemptStatus::IN_PROGRESS) {
                    $inProgress[] = $item;
                } elseif ($attempt->status === ExamAttemptStatus::SUBMITTED) {
                    $completed[] = $item;
                }

                continue;
            }

            if ($isAvailable) {
                $available[] = $this->formatExam($exam);
            }
        }

        $this->availableExams = $available;
        $this->inProgressExams = $inProgress;
        $this->completedExams = $completed;
    }

    private function formatExam(
        Exam $exam,
        ?ExamAttempt $attempt = null,
        ?int $productAccessId = null,
    ): array {
        return [
            'id' => $exam->id,
            'title' => $exam->title,
            'type' => $exam->type->value,
            'description' => $exam->description,
            'question_count' => $exam->question_count,
            'duration_minutes' => $exam->duration_minutes,
            'passing_score' => $exam->passing_score !== null
                ? (float) $exam->passing_score
                : null,
            'available_from' => $exam->available_from?->format('Y/m/d H:i'),
            'available_until' => $exam->available_until->format('Y/m/d H:i'),
            'attempt_id' => $attempt?->id,
            'product_access_id' => $productAccessId,
            'score' => $attempt?->score !== null
                ? (float) $attempt->score
                : null,
            'status' => $attempt?->status->value,
        ];
    }

    public function startExam(
        int $examId,
        ?int $productAccessId = null
    ): void {
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        $exam = Exam::query()
            ->whereKey($examId)
            ->firstOrFail();

        $attempt = app(ExamAttemptService::class)
            ->start($exam, $user, $productAccessId);

        $this->redirectRoute(
            'exam.attempts.show',
            ['attempt' => $attempt->id]
        );
    }

    public function continueExam(int $attemptId): void
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        $attempt = ExamAttempt::query()
            ->whereKey($attemptId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $attempt = app(ExamAttemptStatusService::class)
            ->refresh($attempt);

        if ($attempt->status === ExamAttemptStatus::SUBMITTED) {
            $this->redirectRoute(
                'exam.results.show',
                ['attempt' => $attempt->id]
            );

            return;
        }

        abort_unless(
            $attempt->status === ExamAttemptStatus::IN_PROGRESS,
            403
        );

        $this->redirectRoute(
            'exam.attempts.show',
            ['attempt' => $attempt->id]
        );
    }

    public function viewResult(int $attemptId): void
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        $attempt = ExamAttempt::query()
            ->whereKey($attemptId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $attempt = app(ExamAttemptStatusService::class)
            ->refresh($attempt);

        abort_unless(
            $attempt->status === ExamAttemptStatus::SUBMITTED,
            403
        );

        $this->redirectRoute(
            'exam.results.show',
            ['attempt' => $attempt->id]
        );
    }
};
