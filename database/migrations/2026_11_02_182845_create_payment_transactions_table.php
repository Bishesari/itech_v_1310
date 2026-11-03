<?php

use App\Enums\PaymentTransactionStatus;
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
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('payment_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('payment_method_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('financial_account_id')
                ->constrained()
                ->restrictOnDelete();

            $table->unsignedBigInteger('amount');

            $table->string('status', 30)
                ->default(PaymentTransactionStatus::PENDING->value);

            $table->string('tracking_code', 100)->nullable();
            $table->string('transaction_id', 150)->nullable();

            $table->timestamp('paid_at')->nullable();

            $table->text('description')->nullable();

            $table->string('gateway', 30)->nullable();
            $table->string('gateway_request_id', 100)->nullable()->unique();
            $table->string('gateway_token', 150)->nullable();
            $table->string('gateway_reference', 150)->nullable();
            $table->timestamp('gateway_token_requested_at')->nullable();


            $table->timestamps();

            $table->index(['payment_id', 'status']);
            $table->index('tracking_code');
            $table->index('transaction_id');
            $table->index(['financial_account_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};
