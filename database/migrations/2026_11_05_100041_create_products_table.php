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
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            $table->foreignId('standard_id')
                ->constrained()
                ->restrictOnDelete();

            $table->string('title', 200);
            $table->string('slug', 200)->unique();

            $table->string('type', 50);

            $table->string('short_description', 500)->nullable();
            $table->longText('description')->nullable();

            $table->string('seo_title', 200)->nullable();
            $table->string('seo_description', 320)->nullable();

            $table->boolean('is_active')->default(false);
            $table->boolean('is_publicly_listed')->default(false);

            $table->timestamps();

            $table->unique(
                ['standard_id', 'type'],
                'products_standard_type_unique'
            );

            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
