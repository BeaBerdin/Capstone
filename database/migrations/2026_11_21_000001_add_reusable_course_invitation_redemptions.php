<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_invitations', function (Blueprint $table) {
            $table->boolean('is_private')->default(false);
            $table->timestamp('deactivated_at')->nullable();
            $table->foreignId('parent_invitation_id')->nullable()->constrained('course_invitations')->cascadeOnDelete();
            $table->unique(['parent_invitation_id', 'student_id'], 'course_invitation_recipient_unique');
        });

        // Retain the recipient boundary if a student account is later deleted.
        DB::table('course_invitations')->whereNotNull('student_id')->update(['is_private' => true]);

        Schema::create('course_invitation_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_invitation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('enrollment_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
            $table->unique(['course_invitation_id', 'student_id'], 'course_invitation_student_redemption_unique');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->timestamp('payment_review_required_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('payment_review_required_at');
        });

        Schema::dropIfExists('course_invitation_redemptions');

        Schema::table('course_invitations', function (Blueprint $table) {
            $table->dropUnique('course_invitation_recipient_unique');
            $table->dropConstrainedForeignId('parent_invitation_id');
            $table->dropColumn(['is_private', 'deactivated_at']);
        });
    }
};
