<?php

use App\Enums\ExamType;
use App\Models\Standard;
use App\Services\Exam\ExamCreationService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

new class extends Component
{
    public $standards;

    public ?int $standard_id = null;

    public string $type = 'midterm';

    public string $title = '';

    public ?string $description = null;

    public int $question_count = 20;

    public int $duration_minutes = 30;

    public ?float $passing_score = null;

    public ?string $available_from = null;

    public ?string $available_until = null;

    public function mount(): void
    {
        $this->standards = Standard::query()
            ->where('is_active', true)
            ->orderBy('name_fa')
            ->get(['id', 'name_fa']);
    }

    public function create(ExamCreationService $service): void
    {
        $validated = $this->validate([
            'standard_id' => ['required', 'exists:standards,id'],
            'type' => ['required', 'in:midterm,final'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'question_count' => ['required', 'integer', 'min:1'],
            'duration_minutes' => ['required', 'integer', 'min:1'],
            'passing_score' => ['nullable', 'numeric', 'between:0,100'],
            'available_from' => ['nullable', 'date'],
            'available_until' => ['required', 'date'],
        ]);

        $validated['type'] = ExamType::from($validated['type']);

        $service->create(
            $validated,
            Auth::user()
        );

        session()->flash('success', 'آزمون با موفقیت ایجاد شد.');

        $this->reset([
            'standard_id',
            'title',
            'description',
            'passing_score',
            'available_from',
            'available_until',
        ]);

        $this->type = 'midterm';
        $this->question_count = 20;
        $this->duration_minutes = 30;
    }
};
