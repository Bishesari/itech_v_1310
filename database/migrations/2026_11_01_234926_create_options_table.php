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
        Schema::create('options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')
                ->constrained('questions')
                ->cascadeOnDelete();

            $table->text('text')->nullable();
            $table->enum('dir', ['rtl', 'ltr'])->default('rtl'); // قالب نوشتار
            $table->string('image_path')->nullable();

            $table->boolean('is_correct')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('options');
    }
};
