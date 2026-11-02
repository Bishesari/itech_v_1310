<?php

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
        Schema::create('financial_account_payment_method', function (Blueprint $table) {
            $table->id();

            $table->foreignId('financial_account_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('payment_method_id')
                ->constrained()
                ->restrictOnDelete();

            $table->timestamps();

            $table->unique([
                'financial_account_id',
                'payment_method_id',
            ], 'financial_account_payment_method_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_account_payment_method');
    }
};
