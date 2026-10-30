<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\City;
use App\Models\Province;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $bushehr = Province::where('slug', 'bushehr')->firstOrFail();

        $bushehrcity = City::where('province_id', $bushehr->id)
            ->where('slug', 'bushehr')
            ->firstOrFail();

        Branch::updateOrCreate(
            ['code' => 'BU001'],
            [
                'short_name' => 'مرکزی بوشهر',
                'is_main' => true,
                'province_id' => $bushehr->id,
                'city_id' => $bushehrcity->id,
                'address' => 'بوشهر، سنگی، اول گلخونه، ساختمان سیراف',
                'postal_code' => '7514977863',
                'phone' => '07733103350',
                'mobile' => '09350568163',
                'is_active' => true,
            ]
        );

        Branch::updateOrCreate(
            ['code' => 'BU002'],
            [
                'short_name' => 'یک بوشهر',
                'is_main' => false,
                'province_id' => $bushehr->id,
                'city_id' => $bushehrcity->id,
                'address' => 'بوشهر، میدان برج، پشت بیمارستان مادر و کودک',
                'postal_code' => null,
                'phone' => '07733103350',
                'mobile' => '09034336111',
                'is_active' => true,
            ]
        );
    }
}
