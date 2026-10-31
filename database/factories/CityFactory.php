<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\City;
use App\Models\Province;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<City>
 */
class CityFactory extends Factory
{
    protected $model = City::class;

    public function definition(): array
    {
        return [
            'province_id' => Province::factory(),
            'name' => fake()->city(),
            'slug' => fake()->unique()->slug(2),
            'is_active' => true,
        ];
    }
}
