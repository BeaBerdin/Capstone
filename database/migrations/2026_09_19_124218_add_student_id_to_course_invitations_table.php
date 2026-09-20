<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_invitations', function (Blueprint $table) {
            $table->foreignId('student_id')
                ->nullable()
                ->after('created_by')
                ->constrained('users')
                ->nullOnDelete();

            $table->index(['student_id', 'accepted_at']);
        });
    }

    public function down(): void
    {
        Schema::table('course_invitations', function (Blueprint $table) {
            $table->dropForeign(['student_id']);
            $table->dropIndex([
                'student_id',
                'accepted_at',
            ]);
            $table->dropColumn('student_id');
        });
    }
};