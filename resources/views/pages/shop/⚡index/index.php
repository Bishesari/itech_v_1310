<?php

use App\Enums\ProductType;
use App\Models\Product;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts.public')]
#[Title('فروشگاه محصولات | آموزشگاه آی‌تک')]
class extends Component
{
    public function with(): array
    {
        $categories = [];

        foreach (ProductType::cases() as $type) {
            $categories[] = [
                'value' => $type->value,
                'title' => $type->label(),
                'description' => match ($type) {
                    ProductType::WRITTEN_FREQUENT_QUESTIONS => 'دسترسی به مجموعه سؤالات پرتکرار آزمون‌های کتبی.',
                    ProductType::MOCK_EXAM => 'آمادگی و ارزیابی مهارت‌ها با شرکت در آزمون‌های آزمایشی.',
                },
                'icon' => match ($type) {
                    ProductType::WRITTEN_FREQUENT_QUESTIONS => 'document-text',
                    ProductType::MOCK_EXAM => 'clipboard-document-check',
                },
                'count' => Product::query()
                    ->where('type', $type->value)
                    ->where('is_active', true)
                    ->where('is_publicly_listed', true)
                    ->count(),
            ];
        }

        return [
            'categories' => $categories,
        ];
    }
};
