<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamQuestion extends Model
{
    protected $fillable = [
        'exam_id',
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

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
    public function options(): HasMany
    {
        return $this->hasMany(ExamQuestionOption::class);
    }
}
