<?php

use App\Enums\ExamType;
use App\Enums\ProductType;
use App\Models\Product;
use App\Services\Exam\ExamCreationService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

new class extends Component
{
    public $products;

    public string $title = '';

    public ?string $description = null;

    public int $question_count = 20;

    public int $duration_minutes = 60;

    public ?float $passing_score = null;

    public ?string $available_from = null;

    public ?string $available_until = null;

    public ?int $product_id = null;

    public function mount(): void
    {
        $this->products = Product::query()
            ->where('type', ProductType::MOCK_EXAM->value)
            ->whereDoesntHave('exam')
            ->with('standard')
            ->latest()
            ->get();
    }

    public function create(ExamCreationService $service): void
    {
        $validated = $this->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'question_count' => ['required', 'integer', 'min:1'],
            'duration_minutes' => ['required', 'integer', 'min:1'],
            'passing_score' => ['nullable', 'numeric', 'between:0,100'],
            'available_from' => ['nullable', 'date'],
            'available_until' => ['required', 'date'],
        ]);

        $validated['type'] = ExamType::MOCK;

        $service->create(
            $validated,
            Auth::user()
        );

        session()->flash('success', 'آزمون آزمایشی با موفقیت ایجاد شد.');

        $this->reset([
            'product_id',
            'title',
            'description',
            'passing_score',
            'available_from',
            'available_until',
        ]);

        $this->question_count = 20;
        $this->duration_minutes = 60;

        $this->mount();
    }
};
