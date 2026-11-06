<?php

namespace App\Models;

use App\Enums\ExamAttemptStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class ExamAttempt extends Model
{
    protected $fillable = [
        'exam_id',
        'user_id',
        'status',
        'started_at',
        'deadline_at',
        'submitted_at',
        'question_count',
        'duration_minutes',
        'correct_count',
        'score',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'deadline_at' => 'datetime',
            'submitted_at' => 'datetime',
            'question_count' => 'integer',
            'duration_minutes' => 'integer',
            'correct_count' => 'integer',
            'score' => 'decimal:2',
            'status' => ExamAttemptStatus::class,
        ];
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(ExamAttemptQuestion::class);
    }

    public function answers(): HasManyThrough
    {
        return $this->hasManyThrough(
            ExamAttemptAnswer::class,
            ExamAttemptQuestion::class
        );
    }
    public function productAccess(): BelongsTo
    {
        return $this->belongsTo(ProductAccess::class);
    }
}
