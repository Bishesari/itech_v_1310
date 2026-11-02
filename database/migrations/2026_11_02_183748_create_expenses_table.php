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
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();

            $table->foreignId('expense_category_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('financial_account_id')
                ->constrained()
                ->restrictOnDelete();

            $table->unsignedBigInteger('amount');

            $table->timestamp('paid_at');

            $table->string('tracking_code', 100)->nullable();

            $table->text('description')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index(['expense_category_id', 'paid_at']);
            $table->index(['financial_account_id', 'paid_at']);
            $table->index('tracking_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
