<?php

namespace App\Models;

use App\Traits\HasJalaliDates;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Chapter extends Model
{
    use HasJalaliDates;
    protected $fillable = [
        'standard_id',
        'number',
        'title',
    ];

    protected function casts(): array
    {
        return [
            'number' => 'integer',
        ];
    }

    public function standard(): BelongsTo
    {
        return $this->belongsTo(Standard::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    public function exams(): BelongsToMany
    {
        return $this->belongsToMany(Exam::class);
    }
}
