<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['nationality_type', 'identity', 'gender', 'first_name_fa', 'last_name_fa', 'nickname'])]

class Person extends Model
{
    public function mobiles(): BelongsToMany
    {
        return $this->belongsToMany(Mobile::class)
            ->withTimestamps();
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }
}
