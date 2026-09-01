<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('learning_path_courses', function (Blueprint $table) {

            $table->foreignId('learning_path_id')
                  ->after('id')
                  ->constrained()
                  ->cascadeOnDelete();

            $table->foreignId('course_id')
                  ->after('learning_path_id')
                  ->constrained()
                  ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('learning_path_courses', function (Blueprint $table) {

            $table->dropForeign(['learning_path_id']);
            $table->dropForeign(['course_id']);

            $table->dropColumn([
                'learning_path_id',
                'course_id'
            ]);
        });
    }
};