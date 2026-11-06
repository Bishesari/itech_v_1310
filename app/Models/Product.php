<?php

namespace App\Models;

use App\Enums\ProductType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Product extends Model
{

    protected $fillable = [
        'standard_id',
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

    public function standard(): BelongsTo
    {
        return $this->belongsTo(Standard::class);
    }

    public function prices(): HasMany
    {
        return $this->hasMany(ProductPrice::class);
    }

    public function exam(): HasOne
    {
        return $this->hasOne(Exam::class);
    }
}
