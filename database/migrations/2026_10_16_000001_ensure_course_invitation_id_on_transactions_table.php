<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Repair migration for EXISTING databases.
 *
 * If your database already recorded the old (broken) 2026_09_19_133820
 * migration as "ran", Laravel will not run the corrected version again.
 * This new migration adds transactions.course_invitation_id only when it
 * is missing. It never drops or changes existing data.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (
            ! Schema::hasTable('transactions')
            || ! Schema::hasTable('course_invitations')
            || Schema::hasColumn('transactions', 'course_invitation_id')
        ) {
            return;
        }

        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('course_invitation_id')
                ->nullable()
                ->after('course_id')
                ->constrained('course_invitations')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        // Intentionally empty: the column may have been created by the
        // corrected 2026_09_19_133820 migration, which owns its rollback.
    }
};
