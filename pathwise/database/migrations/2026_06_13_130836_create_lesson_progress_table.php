<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lesson_progress', function (Blueprint $table) {

            $table->id();

            $table->foreignId('student_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('lesson_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->enum('status', [
                'not_started',
                'in_progress',
                'completed'
            ])->default('not_started');

            $table->timestamp('started_at')->nullable();

            $table->timestamp('completed_at')->nullable();

            $table->integer('time_spent_minutes')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_progress');
    }
};