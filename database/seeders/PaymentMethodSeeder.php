<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $methods = [
            [
                'code' => 'sep',
                'name' => 'درگاه پرداخت سامان (SEP)',
                'is_active' => true,
                'sort_order' => 10,
            ],
            [
                'code' => 'cash',
                'name' => 'پرداخت نقدی',
                'is_active' => false,
                'sort_order' => 20,
            ],
            [
                'code' => 'card_to_card',
                'name' => 'کارت‌به‌کارت',
                'is_active' => false,
                'sort_order' => 30,
            ],
            [
                'code' => 'bank_transfer',
                'name' => 'واریز بانکی',
                'is_active' => false,
                'sort_order' => 40,
            ],
        ];

        foreach ($methods as $method) {
            PaymentMethod::query()->updateOrCreate(
                ['code' => $method['code']],
                [
                    'name' => $method['name'],
                    'is_active' => $method['is_active'],
                    'sort_order' => $method['sort_order'],
                ],
            );
        }
    }
}
