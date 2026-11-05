<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ExamAttemptQuestion extends Model
{
    protected $fillable = [
        'exam_attempt_id',
        'question_id',
        'position',
        'question_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'question_snapshot' => 'array',
        ];
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(ExamAttempt::class, 'exam_attempt_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(ExamAttemptQuestionOption::class);
    }

    public function answer(): HasOne
    {
        return $this->hasOne(ExamAttemptAnswer::class);
    }
}
