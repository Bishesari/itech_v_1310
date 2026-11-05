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
        Schema::create('chapter_exam', function (Blueprint $table) {
            $table->id();

            $table->foreignId('chapter_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('exam_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique(
                ['chapter_id', 'exam_id'],
                'chapter_exam_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chapter_exam');
    }
};
