<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamAttemptAnswer extends Model
{
    protected $fillable = [
        'exam_attempt_question_id',
        'selected_option_id',
        'is_marked_for_review',
        'answered_at',
    ];

    protected function casts(): array
    {
        return [
            'is_marked_for_review' => 'boolean',
            'answered_at' => 'datetime',
        ];
    }

    public function attemptQuestion(): BelongsTo
    {
        return $this->belongsTo(
            ExamAttemptQuestion::class,
            'exam_attempt_question_id'
        );
    }

    public function selectedOption(): BelongsTo
    {
        return $this->belongsTo(
            ExamAttemptQuestionOption::class,
            'selected_option_id'
        );
    }
}
