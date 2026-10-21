<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Course reviews: students with an active or completed enrollment rate a
| published course (1-5) with an optional comment, one review per student
| per course. An administrator can hide a review with a reason.
|
| The earlier empty placeholder table was dropped by
| 2026_10_21_000001_drop_unused_payments_and_course_reviews_tables.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_reviews', function (Blueprint $table) {
            $table->id();

            $table->foreignId('course_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('student_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->unsignedTinyInteger('rating');
            $table->text('comment')->nullable();

            $table->boolean('is_hidden')->default(false);
            $table->string('hidden_reason', 500)->nullable();

            $table->foreignId('hidden_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->unique(['course_id', 'student_id'], 'course_reviews_course_student_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_reviews');
    }
};
