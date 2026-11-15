<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Student registration applications (EDP approval).
|
| One row per Study Load submission: the first registration and every
| resubmission. A row is never overwritten; only its own decision (status,
| reviewer, review time, rejection reason) is filled in once.
|
| Existing accounts get no rows and need none: an account without any
| application row is treated as approved (grandfathered). This migration
| does not touch the users table or any existing data.
*/

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_applications', function (Blueprint $table) {
            $table->id();

            // Accounts are deactivated, never deleted, so history is kept.
            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete();

            // 1 for the first registration, 2 for the first resubmission, ...
            $table->unsignedInteger('submission_no');

            // Private disk path (storage/app/private/study-loads/{user}/...).
            $table->string('study_load_path');
            $table->string('study_load_original_name');
            $table->string('study_load_mime', 100);
            $table->unsignedBigInteger('study_load_size');

            // pending | approved | rejected
            $table->string('status', 20)->default('pending');

            // An explicit default keeps MariaDB/MySQL with
            // explicit_defaults_for_timestamp=OFF from adding
            // "ON UPDATE CURRENT_TIMESTAMP", which would overwrite the
            // submission time whenever the EDP decides.
            $table->timestamp('submitted_at')->useCurrent();

            // The Super Admin (EDP) who decided, and when.
            $table->foreignId('reviewed_by')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->timestamps();

            $table->unique(['user_id', 'submission_no']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_applications');
    }
};
