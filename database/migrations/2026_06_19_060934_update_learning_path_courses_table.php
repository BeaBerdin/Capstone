<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite (used only by the automated tests) cannot add a required
        // column without a default value, so the columns are optional there.
        // MySQL keeps the original definition.
        $isSqlite = Schema::getConnection()->getDriverName() === 'sqlite';

        Schema::table('learning_path_courses', function (Blueprint $table) use ($isSqlite) {

            $learningPathId = $table->foreignId('learning_path_id')
                  ->after('id');

            if ($isSqlite) {
                $learningPathId->nullable();
            }

            $learningPathId->constrained()
                  ->cascadeOnDelete();

            $courseId = $table->foreignId('course_id')
                  ->after('learning_path_id');

            if ($isSqlite) {
                $courseId->nullable();
            }

            $courseId->constrained()
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