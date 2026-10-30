<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class ContactPerson extends Pivot
{
    protected $table = 'contact_person';

    protected function casts(): array
    {
        return [
            'verified' => 'boolean',
            'active' => 'boolean',
        ];
    }
}
