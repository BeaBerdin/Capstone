<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Links a course to the template it was created from
 * (Manuscript: Table 9, courses.template_id, nullable).
 *
 * Deleting a template never deletes courses: the link is set to NULL.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('courses', 'template_id')) {
            return;
        }

        Schema::table('courses', function (Blueprint $table) {
            $table->foreignId('template_id')
                ->nullable()
                ->after('category_id')
                ->constrained('course_templates')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('courses', 'template_id')) {
            Schema::table('courses', function (Blueprint $table) {
                $table->dropConstrainedForeignId('template_id');
            });
        }
    }
};
