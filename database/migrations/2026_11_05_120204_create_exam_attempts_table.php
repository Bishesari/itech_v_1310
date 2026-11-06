<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
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

            // فقط برای تلاش‌های آزمون آزمایشی مقدار دارد.
            $table->foreignId('product_access_id')
                ->nullable()
                ->constrained('product_accesses')
                ->restrictOnDelete();

            // برای آزمون‌های داخلی، شناسه کاربر را نگه می‌دارد.
            // برای آزمون آزمایشی مقدار آن NULL خواهد بود.
            $table->unsignedBigInteger('internal_user_id')
                ->nullable()
                ->virtualAs(
                    'CASE WHEN product_access_id IS NULL THEN user_id ELSE NULL END'
                );

            $table->string('status', 20)->default('in_progress');

            $table->timestamp('started_at');
            $table->timestamp('deadline_at');
            $table->timestamp('submitted_at')->nullable();

            $table->unsignedSmallInteger('question_count');
            $table->unsignedSmallInteger('duration_minutes');

            $table->unsignedSmallInteger('correct_count')->nullable();
            $table->decimal('score', 5, 2)->nullable();

            $table->timestamps();

            // هر دسترسی خریداری‌شده فقط یک بار قابل استفاده است.
            $table->unique(
                'product_access_id',
                'exam_attempts_product_access_unique'
            );

            // هر کاربر برای هر آزمون داخلی فقط یک تلاش دارد.
            $table->unique(
                ['exam_id', 'internal_user_id'],
                'exam_attempts_exam_internal_user_unique'
            );

            $table->index(
                ['status', 'deadline_at'],
                'exam_attempts_status_deadline_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_attempts');
    }
};
