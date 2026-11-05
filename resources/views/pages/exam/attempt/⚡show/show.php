<?php

declare(strict_types=1);

use App\Enums\ExamAttemptStatus;
use App\Models\ExamAttempt;
use App\Services\Exam\ExamAnswerService;
use App\Services\Exam\ExamAttemptStatusService;
use App\Services\Exam\ExamGradingService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

new class extends Component
{
    public ExamAttempt $attempt;

    public int $currentQuestionIndex = 0;

    public function mount(
        ExamAttempt $attempt,
        ExamAttemptStatusService $statusService
    ): void {
        $user = Auth::user();

        abort_unless(
            (int) $attempt->user_id === (int) $user->id,
            403
        );

        $this->attempt = $statusService->refresh($attempt);

        $this->loadAttemptData();
    }

    private function loadAttemptData(): void
    {
        $this->attempt->load([
            'exam',
            'questions' => fn ($query) => $query
                ->orderBy('position'),

            'questions.options' => fn ($query) => $query
                ->orderBy('position'),

            'questions.answer',
        ]);
    }

    public function refreshAttempt(): void
    {
        $this->authorizeAttempt();

        $statusService = app(ExamAttemptStatusService::class);

        $this->attempt = $statusService->refresh($this->attempt);

        $this->loadAttemptData();
    }

    private function authorizeAttempt(): void
    {
        abort_unless(
            (int) $this->attempt->user_id === (int) Auth::id(),
            403
        );
    }

    public function saveAnswer(
        int $questionId,
        ?int $optionId
    ): void {
        $this->authorizeAttempt();

        abort_unless(
            $this->attempt->status === ExamAttemptStatus::IN_PROGRESS,
            403
        );

        $question = $this->attempt->questions()
            ->whereKey($questionId)
            ->firstOrFail();

        app(ExamAnswerService::class)
            ->save($question, Auth::user(), $optionId);

        $this->refreshAttempt();
    }

    public function toggleReview(int $questionId): void
    {
        $this->authorizeAttempt();

        abort_unless(
            $this->attempt->status === ExamAttemptStatus::IN_PROGRESS,
            403
        );

        $question = $this->attempt->questions()
            ->whereKey($questionId)
            ->firstOrFail();

        $answer = $question->answer;

        if (! $answer) {
            throw new \DomainException(
                'Answer record not found for this question.'
            );
        }

        app(ExamAnswerService::class)
            ->markForReview(
                $question,
                Auth::user(),
                ! $answer->is_marked_for_review
            );

        $this->refreshAttempt();
    }

    public function submitAttempt(): void
    {
        $this->authorizeAttempt();

        abort_unless(
            $this->attempt->status === ExamAttemptStatus::IN_PROGRESS,
            403
        );

        app(ExamGradingService::class)
            ->submit($this->attempt);

        $this->refreshAttempt();
    }

    public function nextQuestion(): void
    {
        $this->authorizeAttempt();

        $this->currentQuestionIndex = min(
            $this->currentQuestionIndex + 1,
            $this->attempt->questions->count() - 1
        );
    }

    public function previousQuestion(): void
    {
        $this->authorizeAttempt();

        $this->currentQuestionIndex = max(
            $this->currentQuestionIndex - 1,
            0
        );
    }

    public function goToQuestion(int $index): void
    {
        $this->authorizeAttempt();

        abort_unless(
            $index >= 0 && $index < $this->attempt->questions->count(),
            404
        );

        $this->currentQuestionIndex = $index;
    }
};
