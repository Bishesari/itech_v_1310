<?php

use App\Enums\ProductType;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts.public')]
#[Title('محصولات فروشگاه آی‌تک')]
class extends Component
{
    public string $type;

    public function mount(string $type): void
    {
        abort_unless(
            ProductType::tryFrom($type) !== null,
            404
        );

        $this->type = $type;
    }

    public function with(): array
    {
        $productType = ProductType::from($this->type);

        $products = Product::query()
            ->where('type', $productType)
            ->where('is_active', true)
            ->where('is_publicly_listed', true)
            ->with([
                'prices' => fn ($query) => $query
                    ->where(function ($query) {
                        $query->whereNull('starts_at')
                            ->orWhere('starts_at', '<=', now());
                    })
                    ->where(function ($query) {
                        $query->whereNull('ends_at')
                            ->orWhere('ends_at', '>=', now());
                    }),
            ])
            ->latest()
            ->get();

        $accessedProductIds = Auth::check()
            ? Auth::user()
                ->productAccesses()
                ->whereIn('product_id', $products->pluck('id'))
                ->pluck('product_id')
                ->all()
            : [];

        return [
            'category' => [
                'title' => $productType->label(),
                'value' => $productType->value,
            ],
            'products' => $products,
            'accessedProductIds' => $accessedProductIds,
        ];
    }
};
