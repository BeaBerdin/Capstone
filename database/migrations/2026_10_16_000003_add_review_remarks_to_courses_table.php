<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Stores the Administrator's feedback when a course is returned to the
 * teacher for revision (Manuscript: Figure 28/29 and the Program Workflow,
 * "revising submissions based on feedback received").
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('courses', 'review_remarks')) {
            return;
        }

        Schema::table('courses', function (Blueprint $table) {
            $table->text('review_remarks')
                ->nullable()
                ->after('status');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('courses', 'review_remarks')) {
            Schema::table('courses', function (Blueprint $table) {
                $table->dropColumn('review_remarks');
            });
        }
    }
};
