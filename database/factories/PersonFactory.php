<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Person;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Person>
 */
class PersonFactory extends Factory
{
    protected $model = Person::class;

    public function definition(): array
    {
        return [
            'nationality' => null,
            'identity' => null,
            'gender' => fake()->boolean(),
            'first_name_fa' => fake()->firstName(),
            'last_name_fa' => fake()->lastName(),
            'nickname' => null,
        ];
    }
}
