<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds transactions.course_invitation_id.
 *
 * FIX (P0): the previous version of this file accidentally created the
 * course_invitations table a second time (it is already created by
 * 2026_09_19_105146_create_course_invitations_table). On a fresh database
 * that crashed with "table already exists", and the column that
 * TransactionController writes to was never created.
 *
 * Every step is guarded, so this migration is safe to run on databases
 * where the column already exists.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('transactions')) {
            return;
        }

        if (Schema::hasColumn('transactions', 'course_invitation_id')) {
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
        if (
            Schema::hasTable('transactions')
            && Schema::hasColumn('transactions', 'course_invitation_id')
        ) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->dropConstrainedForeignId('course_invitation_id');
            });
        }
    }
};
