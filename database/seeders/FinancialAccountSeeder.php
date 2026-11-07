<?php

namespace Database\Seeders;

use App\Enums\FinancialAccountType;
use App\Models\FinancialAccount;
use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class FinancialAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $paymentMethod = PaymentMethod::query()
                ->where('code', 'sep')
                ->where('is_active', true)
                ->first();

            if (! $paymentMethod) {
                throw new RuntimeException(
                    'روش پرداخت SEP فعال نیست یا ثبت نشده است.'
                );
            }

            $account = FinancialAccount::query()->updateOrCreate(
                ['code' => 'sep_gateway'],
                [
                    'name' => 'درگاه پرداخت سامان',
                    'type' => FinancialAccountType::GATEWAY,
                    'provider' => 'sep',
                    'is_active' => true,
                    'description' => 'حساب مالی مربوط به تراکنش‌های درگاه پرداخت سامان',
                ],
            );

            $account->paymentMethods()->syncWithoutDetaching([
                $paymentMethod->id,
            ]);
        });

    }
}
