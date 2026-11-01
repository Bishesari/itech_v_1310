<?php

namespace Database\Seeders;

use App\Models\Field;
use Illuminate\Database\Seeder;

class FieldSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $fields = [
            [
                'title' => 'فناوری اطلاعات',
                'slug' => 'information-technology',
            ],
            [
                'title' => 'امور مالی و بازرگانی',
                'slug' => 'finance-and-commerce',
            ],
            [
                'title' => 'هنرهای تجسمی',
                'slug' => 'visual-arts',
            ],

        ];

        foreach ($fields as $field) {
            Field::updateOrCreate(
                ['title' => $field['title']],
                ['slug' => $field['slug']],
            );
        }
    }
}
