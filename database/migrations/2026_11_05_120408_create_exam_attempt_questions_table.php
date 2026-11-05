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
        Schema::create('exam_attempt_questions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('exam_attempt_id')
                ->constrained()
                ->cascadeOnDelete();

            // ردیابی سؤال اصلی؛ Snapshot مستقل باقی می‌ماند.
            $table->foreignId('question_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->unsignedSmallInteger('position');

            // Snapshot متن و اطلاعات سؤال
            $table->json('question_snapshot');

            $table->timestamps();

            $table->unique(
                ['exam_attempt_id', 'position'],
                'exam_attempt_questions_position_unique'
            );

            $table->index('question_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_attempt_questions');
    }
};
