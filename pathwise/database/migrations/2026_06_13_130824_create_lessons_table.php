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
    Schema::create('lessons', function (Blueprint $table) {
        $table->id();

        $table->foreignId('course_id')
            ->constrained()
            ->cascadeOnDelete();

        $table->string('title');

        $table->longText('content')->nullable();

        $table->enum('lesson_type', [
            'video',
            'document',
            'text',
            'quiz'
        ])->default('video');

        $table->string('video_url')->nullable();

        $table->string('file_path')->nullable();

        $table->integer('lesson_order')->default(1);

        $table->integer('duration_minutes')->nullable();

        $table->boolean('is_preview')->default(false);

        $table->boolean('is_published')->default(true);

        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lessons');
    }
};
