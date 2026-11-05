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
        Schema::create('exam_attempts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('exam_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('user_id')
                ->constrained()
                ->restrictOnDelete();

            $table->string('status', 20)->default('in_progress');

            // زمان شروع و مهلت پایان آزمون
            $table->timestamp('started_at');
            $table->timestamp('deadline_at');
            $table->timestamp('submitted_at')->nullable();

            // اطلاعات تثبیت‌شده در زمان شروع آزمون
            $table->unsignedSmallInteger('question_count');
            $table->unsignedSmallInteger('duration_minutes');

            // نتیجه آزمون
            $table->unsignedSmallInteger('correct_count')->nullable();
            $table->decimal('score', 5, 2)->nullable();

            $table->timestamps();

            // هر کاربر برای هر آزمون فقط یک تلاش دارد.
            $table->unique(
                ['exam_id', 'user_id'],
                'exam_attempts_exam_user_unique'
            );

            $table->index(
                ['status', 'deadline_at'],
                'exam_attempts_status_deadline_idx'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_attempts');
    }
};
