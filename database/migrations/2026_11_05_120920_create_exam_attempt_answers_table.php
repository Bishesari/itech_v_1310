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
        Schema::create('exam_attempt_answers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('exam_attempt_question_id')
                ->unique()
                ->constrained()
                ->cascadeOnDelete();

            // گزینه انتخاب‌شده از میان گزینه‌های Snapshot
            $table->foreignId('selected_option_id')
                ->nullable()
                ->constrained('exam_attempt_question_options')
                ->nullOnDelete();

            $table->boolean('is_marked_for_review')->default(false);

            $table->timestamp('answered_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_attempt_answers');
    }
};
