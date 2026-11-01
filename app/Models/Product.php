<?php

namespace App\Models;

use App\Enums\ProductType;
use App\Traits\HasJalaliDates;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;


class Product extends Model
{
    use HasJalaliDates;

    protected $fillable = [
        'title',
        'slug',
        'type',
        'short_description',
        'description',
        'seo_title',
        'seo_description',
        'is_active',
        'is_publicly_listed',
    ];

    protected function casts(): array
    {
        return [
            'type' => ProductType::class,
            'is_active' => 'boolean',
            'is_publicly_listed' => 'boolean',
        ];
    }

    public function prices(): HasMany
    {
        return $this->hasMany(ProductPrice::class);
    }

    public function standards(): BelongsToMany
    {
        return $this->belongsToMany(Standard::class)
            ->withTimestamps();
    }

    public function accesses(): HasMany
    {
        return $this->hasMany(ProductAccess::class);
    }

    public function isWrittenSampleQuestion(): bool
    {
        return $this->type === ProductType::WrittenSampleQuestion;
    }

    public function currentPrice(
        ?\Illuminate\Contracts\Support\CarbonInterface $at = null
    ): ?ProductPrice {
        $at ??= now();

        if ($this->relationLoaded('prices')) {
            return $this->prices
                ->filter(fn (ProductPrice $price) => $price->starts_at->lte($at)
                    && (
                        $price->ends_at === null
                        || $price->ends_at->gt($at)
                    )
                )
                ->sortByDesc('starts_at')
                ->first();
        }

        return $this->prices()
            ->where('starts_at', '<=', $at)
            ->where(fn ($query) => $query->whereNull('ends_at')
                ->orWhere('ends_at', '>', $at)
            )
            ->orderByDesc('starts_at')
            ->first();
    }

    public function simulatedExam(): HasOne
    {
        return $this->hasOne(Exam::class, 'product_id');
    }
}
