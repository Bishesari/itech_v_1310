<?php

namespace App\Models;

use App\Enums\NationalityType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Person extends Model
{
    protected function casts(): array
    {
        return [
            'nationality_type' => NationalityType::class,
            'gender' => 'boolean',
        ];
    }

    public function mobiles(): BelongsToMany
    {
        return $this->belongsToMany(Mobile::class);
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }
}
