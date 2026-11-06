<?php

declare(strict_types=1);

use App\Models\ExamAttempt;
use App\Models\User;
use App\Services\Exam\ExamResultService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

new class extends Component
{
    public array $result = [];

    public function mount(
        ExamAttempt $attempt,
        ExamResultService $resultService,
    ): void {
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        $this->result = $resultService->getResult(
            $attempt,
            $user,
        );
    }
};
