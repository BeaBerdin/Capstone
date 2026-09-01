<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_recommendations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('student_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('course_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->decimal('recommendation_score', 5, 2)
                ->default(0);

            $table->text('reason')->nullable();

            $table->boolean('is_viewed')
                ->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_recommendations');
    }
};