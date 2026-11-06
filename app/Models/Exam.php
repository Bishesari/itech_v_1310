<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ExamType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Exam extends Model
{
    protected $fillable = [
        'standard_id',
        'product_id',
        'created_by',
        'title',
        'type',
        'description',
        'question_count',
        'duration_minutes',
        'passing_score',
        'available_from',
        'available_until',
    ];

    protected function casts(): array
    {
        return [
            'type' => ExamType::class,
            'question_count' => 'integer',
            'duration_minutes' => 'integer',
            'passing_score' => 'decimal:2',
            'available_from' => 'datetime',
            'available_until' => 'datetime',
        ];
    }

    public function standard(): BelongsTo
    {
        return $this->belongsTo(Standard::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(ExamAttempt::class);
    }

    public function chapters(): BelongsToMany
    {
        return $this->belongsToMany(Chapter::class)
            ->withTimestamps();
    }

    public function questions(): HasMany
    {
        return $this->hasMany(ExamQuestion::class);
    }
}
