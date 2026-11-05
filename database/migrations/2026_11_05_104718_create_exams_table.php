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
        Schema::create('exams', function (Blueprint $table) {
            $table->id();

            $table->foreignId('standard_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('product_access_id')
                ->nullable()
                ->unique('exams_product_access_unique')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('created_by')
                ->constrained('users')
                ->restrictOnDelete();

            $table->string('title', 200);
            $table->string('type', 30);

            $table->text('description')->nullable();

            $table->unsignedSmallInteger('question_count');

            $table->unsignedSmallInteger('duration_minutes');

            $table->decimal('passing_score', 5, 2)->nullable();

            $table->timestamp('available_from')->nullable();
            $table->timestamp('available_until');

            $table->timestamps();

            $table->index(
                ['standard_id', 'type'],
                'exams_standard_type_idx'
            );

            $table->index(
                ['type', 'available_from', 'available_until'],
                'exams_type_availability_idx'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exams');
    }
};
