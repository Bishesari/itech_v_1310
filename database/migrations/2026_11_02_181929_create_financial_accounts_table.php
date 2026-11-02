<?php

use App\Enums\FinancialAccountType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('financial_accounts', function (Blueprint $table) {
            $table->id();

            $table->string('name', 150);
            $table->string('code', 50)->unique();

            $table->string('type', 30)
                ->default(FinancialAccountType::OTHER->value);

            $table->string('bank_name', 100)->nullable();
            $table->string('account_number', 50)->nullable();
            $table->string('card_number', 30)->nullable();
            $table->string('iban', 34)->nullable();

            $table->string('provider', 100)->nullable();

            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();

            $table->timestamps();

            $table->index(['type', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_accounts');
    }
};
