<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Branch;
use App\Models\City;
use App\Models\Province;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Branch>
 */
class BranchFactory extends Factory
{
    protected $model = Branch::class;

    public function definition(): array
    {
        return [
            'short_name' => fake()->unique()->city(),
            'code' => fake()->unique()->numerify('#######'),
            'is_main' => false,

            'province_id' => Province::factory(),

            'city_id' => function (array $attributes) {
                return City::factory()->create([
                    'province_id' => $attributes['province_id'],
                ])->id;
            },

            'address' => fake()->address(),
            'postal_code' => null,
            'phone' => null,
            'mobile' => null,
            'is_active' => true,
        ];
    }
}
