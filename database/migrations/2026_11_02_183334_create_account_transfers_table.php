<?php

use App\Enums\AccountTransferStatus;
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
        Schema::create('account_transfers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('from_account_id')
                ->constrained('financial_accounts')
                ->restrictOnDelete();

            $table->foreignId('to_account_id')
                ->constrained('financial_accounts')
                ->restrictOnDelete();

            $table->unsignedBigInteger('amount');

            $table->timestamp('transferred_at')->nullable();

            $table->string('status', 30)
                ->default(AccountTransferStatus::PENDING->value);

            $table->text('description')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index(['from_account_id', 'status']);
            $table->index(['to_account_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_transfers');
    }
};
