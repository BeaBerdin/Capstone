<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('learning_paths', function (Blueprint $table) {
            $table->foreignId('student_id')
                ->nullable()
                ->after('id')
                ->constrained('users')
                ->nullOnDelete();

            $table->string('difficulty_level')
                ->nullable()
                ->after('description');

            $table->boolean('is_generated')
                ->default(false)
                ->after('difficulty_level');
        });
    }

    public function down(): void
    {
        Schema::table('learning_paths', function (Blueprint $table) {
            $table->dropForeign(['student_id']);

            $table->dropColumn([
                'student_id',
                'difficulty_level',
                'is_generated',
            ]);
        });
    }
};