<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('course_invitation_id')
                ->nullable()
                ->after('course_id')
                ->constrained('course_invitations')
                ->nullOnDelete();

            $table->index('course_invitation_id');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['course_invitation_id']);
            $table->dropIndex(['course_invitation_id']);
            $table->dropColumn('course_invitation_id');
        });
    }
};