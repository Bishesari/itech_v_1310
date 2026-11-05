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
        Schema::create('product_accesses', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('product_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('order_item_id')
                ->unique('product_accesses_order_item_unique')
                ->constrained()
                ->restrictOnDelete();

            $table->timestamp('granted_at');

            $table->timestamps();

            $table->index(
                ['user_id', 'product_id'],
                'product_accesses_user_product_idx'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_accesses');
    }
};
