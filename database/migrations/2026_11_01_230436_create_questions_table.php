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
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chapter_id')
                ->constrained('chapters')
                ->cascadeOnDelete();

            $table->string('type', 30)
                ->default('multiple_choice')
                ->index();

            $table->text('text');
            $table->text('explanation')->nullable();

            $table->string('difficulty', 20)->default('medium');

            $table->string('image_path')->nullable();

            $table->longText('code')->nullable();
            $table->string('code_language', 30)->nullable();

            $table->boolean('is_active')->default(true);
            $table->boolean('is_frequently_final')->default(false);

            $table->foreignId('assigned_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
