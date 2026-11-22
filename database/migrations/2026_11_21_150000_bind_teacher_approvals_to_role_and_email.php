<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_applications', function (Blueprint $table) {
            $table->string('applicant_role', 16)->default('student');
            $table->string('review_email')->nullable();
            $table->string('review_origin', 32)->nullable();
            $table->index(['user_id', 'applicant_role', 'submission_no'], 'applications_user_role_submission_idx');
        });

        // The old teacher workflow always saved these four empty file fields.
        // Keep the historical decision, but do not infer which email was reviewed.
        DB::table('student_applications')
            ->where('study_load_path', '')
            ->where('study_load_original_name', '')
            ->where('study_load_mime', '')
            ->where('study_load_size', 0)
            ->update(['applicant_role' => 'teacher', 'review_origin' => 'legacy_history']);
    }

    public function down(): void
    {
        // The old schema cannot represent teacher-specific role, email, and
        // origin metadata. Refuse before issuing SQL so historical and new
        // teacher review records cannot silently lose their approval scope.
        throw new RuntimeException(
            'The Teacher Approval migration is forward-only. Rollback would discard teacher approval role, reviewed-email, and review-origin metadata. Use a forward recovery migration or restore a verified backup.'
        );
    }
};
