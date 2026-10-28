<?php

namespace App\Models;

use App\Enums\ContactType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
#[Fillable(['type', 'value'])]

class Contact extends Model
{
    protected function casts(): array
    {
        return [
            'type' => ContactType::class,
        ];
    }

    public function people(): BelongsToMany
    {
        return $this->belongsToMany(Person::class)
            ->withPivot(['verified', 'active'])
            ->withTimestamps();
    }
}
