<?php

use App\Enums\ExamType;
use App\Models\Chapter;
use App\Models\Standard;
use App\Services\Exam\ExamCreationService;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

new class extends Component
{
    public $standards;

    public Collection $chapters;


    public ?int $standard_id = null;

    public array $chapter_ids = [];

    public string $title = '';

    public ?string $description = null;

    public int $question_count = 20;

    public int $duration_minutes = 30;

    public ?float $passing_score = null;

    public ?string $available_from = null;

    public ?string $available_until = null;

    public function mount(): void
    {
        $this->chapters = collect();

        $this->standards = Standard::query()
            ->where('is_active', true)
            ->orderBy('name_fa')
            ->get(['id', 'name_fa']);
    }


    public function updatedStandardId(): void
    {
        $this->chapter_ids = [];
        $this->chapters = collect();

        if (! $this->standard_id) {
            return;
        }

        $this->chapters = Chapter::query()
            ->where('standard_id', $this->standard_id)
            ->orderBy('number')
            ->get(['id', 'number', 'title']);
    }

    public function create(ExamCreationService $service): void
    {
        $validated = $this->validate([
            'standard_id' => ['required', 'exists:standards,id'],
            'chapter_ids' => ['required', 'array', 'min:1'],
            'chapter_ids.*' => ['required', 'integer', 'distinct', 'exists:chapters,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'question_count' => ['required', 'integer', 'min:1'],
            'duration_minutes' => ['required', 'integer', 'min:1'],
            'passing_score' => ['nullable', 'numeric', 'between:0,100'],
            'available_from' => ['nullable', 'date'],
            'available_until' => ['required', 'date'],
        ]);

        // اطمینان از تعلق تمام فصل‌ها به Standard انتخاب‌شده
        $chapterCount = Chapter::query()
            ->where('standard_id', $this->standard_id)
            ->whereIn('id', $this->chapter_ids)
            ->count();

        if ($chapterCount !== count($this->chapter_ids)) {
            throw ValidationException::withMessages([
                'chapter_ids' => 'فصل‌های انتخاب‌شده معتبر نیستند.',
            ]);
        }
        $validated['type'] = ExamType::QUIZ;

        $service->create(
            $validated,
            Auth::user()
        );

        session()->flash('success', 'آزمون با موفقیت ایجاد شد.');

        $this->reset([
            'title',
            'description',
            'chapter_ids',
            'question_count',
            'duration_minutes',
            'passing_score',
            'available_from',
            'available_until',
        ]);

        $this->question_count = 20;
        $this->duration_minutes = 30;

        $this->chapters = collect();
    }
};
