<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Province;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Province>
 */
class ProvinceFactory extends Factory
{
    protected $model = Province::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->city(),
            'slug' => fake()->unique()->slug(2),
            'is_active' => true,
        ];
    }
}
