<?php

use App\Enums\RefundStatus;
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
        Schema::create('refunds', function (Blueprint $table) {
            $table->id();

            $table->foreignId('payment_transaction_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('financial_account_id')
                ->constrained()
                ->restrictOnDelete();
            $table->foreignId('payment_method_id')
                ->constrained()
                ->restrictOnDelete();

            $table->unsignedBigInteger('amount');

            $table->string('status', 30)
                ->default(RefundStatus::PENDING->value);

            $table->timestamp('refunded_at')->nullable();

            $table->string('tracking_code', 100)->nullable();

            $table->text('description')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index(['payment_transaction_id', 'status']);
            $table->index('tracking_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('refunds');
    }
};
