<?php

namespace App\Models;

use App\Enums\CodeLanguage;
use App\Enums\QuestionDifficulty;
use App\Enums\QuestionType;
use App\Traits\HasJalaliDates;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    use HasJalaliDates;
    protected $fillable = [
        'chapter_id',
        'type',
        'text',
        'explanation',
        'difficulty',
        'image_path',
        'code',
        'code_language',
        'is_active',
        'is_frequently_final',
        'assigned_by',
    ];

    protected function casts(): array
    {
        return [
            'type' => QuestionType::class,
            'difficulty' => QuestionDifficulty::class,
            'is_active' => 'boolean',
            'is_frequently_final' => 'boolean',
            'code_language' => CodeLanguage::class,
        ];
    }

    public function chapter(): BelongsTo
    {
        return $this->belongsTo(Chapter::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(Option::class);
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function attemptQuestions(): HasMany
    {
        return $this->hasMany(ExamAttemptQuestion::class);
    }
}
