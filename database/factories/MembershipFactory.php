<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Membership;
use App\Models\Person;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Membership>
 */
class MembershipFactory extends Factory
{
    protected $model = Membership::class;

    public function definition(): array
    {
        return [
            'person_id' => Person::factory(),
            'branch_id' => Branch::factory(),
            'is_active' => true,
        ];
    }
}
