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
        Schema::create('exam_questions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('exam_id')
                ->constrained()
                ->cascadeOnDelete();

            // ردیابی سؤال اصلی
            $table->foreignId('question_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->unsignedSmallInteger('position');

            // Snapshot متن و اطلاعات سؤال
            $table->json('question_snapshot');

            $table->timestamps();

            $table->unique(
                ['exam_id', 'position'],
                'exam_questions_position_unique'
            );

            $table->unique(
                ['exam_id', 'question_id'],
                'exam_questions_question_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_questions');
    }
};
