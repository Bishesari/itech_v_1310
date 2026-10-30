<?php

namespace App\Models;

use App\Enums\Nationality;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['nationality', 'identity', 'gender', 'first_name_fa', 'last_name_fa', 'nickname'])]

class Person extends Model
{
    protected function casts(): array
    {
        return [
            'nationality' => Nationality::class,
        ];
    }

    // Person.php
    public function contacts(): BelongsToMany
    {
        return $this->belongsToMany(Contact::class)
            ->using(ContactPerson::class)
            ->withPivot(['verified', 'active'])
            ->withTimestamps();
    }



    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }
}
