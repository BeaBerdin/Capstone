<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Completes the course_templates table, which until now only had
 * id and timestamps (Manuscript: Table 8 and "Course Template" in the
 * Definition of Terms).
 *
 * structure holds the template's lesson outline as JSON, for example:
 * {"lessons": [{"title": "...", "duration_minutes": 30, "guidance": "..."}]}
 *
 * Only adds columns; nothing is removed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_templates', function (Blueprint $table) {
            if (! Schema::hasColumn('course_templates', 'name')) {
                $table->string('name')->default('Untitled template')->after('id');
            }

            if (! Schema::hasColumn('course_templates', 'description')) {
                $table->text('description')->nullable()->after('name');
            }

            if (! Schema::hasColumn('course_templates', 'structure')) {
                $table->json('structure')->nullable()->after('description');
            }

            if (! Schema::hasColumn('course_templates', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('structure');
            }
        });
    }

    public function down(): void
    {
        foreach (['is_active', 'structure', 'description', 'name'] as $column) {
            if (Schema::hasColumn('course_templates', $column)) {
                Schema::table('course_templates', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
    }
};
