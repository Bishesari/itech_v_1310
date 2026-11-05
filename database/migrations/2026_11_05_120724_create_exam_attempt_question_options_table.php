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
        Schema::create('exam_attempt_question_options', function (Blueprint $table) {
            $table->id();

            $table->foreignId('exam_attempt_question_id')
                ->constrained()
                ->cascadeOnDelete();

            // ردیابی گزینه اصلی
            $table->foreignId('option_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->unsignedTinyInteger('position');

            // Snapshot متن گزینه
            $table->json('option_snapshot');

            // پاسخ صحیح در زمان برگزاری آزمون
            $table->boolean('is_correct');

            $table->timestamps();

            $table->unique(
                ['exam_attempt_question_id', 'position'],
                'exam_attempt_question_options_position_unique'
            );

            $table->index('option_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_attempt_question_options');
    }
};
