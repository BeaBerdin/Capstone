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
    Schema::create('courses', function (Blueprint $table) {
        $table->id();

        $table->foreignId('teacher_id')
            ->constrained('users')
            ->cascadeOnDelete();

        $table->foreignId('category_id')
            ->constrained('course_categories')
            ->cascadeOnDelete();

        $table->string('title');
        $table->text('description');

        $table->string('thumbnail')->nullable();
        $table->string('intro_video')->nullable();

        $table->enum('difficulty_level', [
            'beginner',
            'intermediate',
            'advanced'
        ])->default('beginner');

        $table->decimal('price', 10, 2)->default(0);

        $table->enum('status', [
            'draft',
            'pending',
            'approved',
            'rejected',
            'published'
        ])->default('draft');

        $table->integer('estimated_hours')->nullable();

        $table->boolean('certificate_available')->default(true);

        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
