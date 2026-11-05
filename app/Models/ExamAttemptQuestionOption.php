<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamAttemptQuestionOption extends Model
{
    protected $fillable = [
        'exam_attempt_question_id',
        'option_id',
        'position',
        'option_snapshot',
        'is_correct',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'is_correct' => 'boolean',
        ];
    }

    public function attemptQuestion(): BelongsTo
    {
        return $this->belongsTo(ExamAttemptQuestion::class);
    }

    public function option(): BelongsTo
    {
        return $this->belongsTo(Option::class);
    }
}
