<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

test('reusable invitation migration rollback is blocked before SQL and preserves redemption and payment review data', function () {
    $teacher = pathwiseUser('teacher');
    $student = pathwiseUser('student');
    $course = pathwiseCourse($teacher);
    $now = now()->toDateTimeString();

    $enrollmentId = DB::table('enrollments')->insertGetId([
        'student_id' => $student->id,
        'course_id' => $course->id,
        'status' => 'active',
        'enrolled_at' => $now,
        'progress_percentage' => 0,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $parentInvitationId = DB::table('course_invitations')->insertGetId([
        'course_id' => $course->id,
        'created_by' => $teacher->id,
        'code' => 'parent-'.uniqid(),
        'is_private' => false,
        'deactivated_at' => $now,
        'parent_invitation_id' => null,
        'student_id' => null,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $childInvitationId = DB::table('course_invitations')->insertGetId([
        'course_id' => $course->id,
        'created_by' => $teacher->id,
        'code' => 'child-'.uniqid(),
        'is_private' => true,
        'deactivated_at' => null,
        'parent_invitation_id' => $parentInvitationId,
        'student_id' => $student->id,
        'accepted_by' => $student->id,
        'accepted_at' => $now,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $redemptionId = DB::table('course_invitation_redemptions')->insertGetId([
        'course_invitation_id' => $parentInvitationId,
        'student_id' => $student->id,
        'enrollment_id' => $enrollmentId,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $transactionId = DB::table('transactions')->insertGetId([
        'student_id' => $student->id,
        'course_id' => $course->id,
        'transaction_no' => 'rollback-check-'.uniqid(),
        'amount' => 0,
        'status' => 'pending',
        'payment_review_required_at' => $now,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $migration = require database_path('migrations/2026_11_21_000001_add_reusable_course_invitation_redemptions.php');
    $db = DB::connection();
    $db->enableQueryLog();
    $db->flushQueryLog();

    expect(fn () => $migration->down())->toThrow(RuntimeException::class, 'forward-only');
    $rollbackQueries = $db->getQueryLog();

    expect($rollbackQueries)->toBe([])
        ->and(Schema::hasTable('course_invitation_redemptions'))->toBeTrue()
        ->and(Schema::hasColumn('course_invitations', 'is_private'))->toBeTrue()
        ->and(Schema::hasColumn('course_invitations', 'deactivated_at'))->toBeTrue()
        ->and(Schema::hasColumn('course_invitations', 'parent_invitation_id'))->toBeTrue()
        ->and(Schema::hasColumn('transactions', 'payment_review_required_at'))->toBeTrue()
        ->and(DB::table('course_invitations')->where('id', $parentInvitationId)->value('is_private'))->toBe(0)
        ->and(DB::table('course_invitations')->where('id', $parentInvitationId)->value('deactivated_at'))->toBe($now)
        ->and(DB::table('course_invitations')->where('id', $childInvitationId)->value('parent_invitation_id'))->toBe($parentInvitationId)
        ->and(DB::table('course_invitation_redemptions')->where('id', $redemptionId)->value('enrollment_id'))->toBe($enrollmentId)
        ->and(DB::table('transactions')->where('id', $transactionId)->value('payment_review_required_at'))->toBe($now);
});

test('Reading lesson sections migration rollback is blocked before SQL and preserves ordered private file references and legacy content', function () {
    $teacher = pathwiseUser('teacher');
    $course = pathwiseCourse($teacher);
    $now = now()->toDateTimeString();

    $lessonId = DB::table('lessons')->insertGetId([
        'course_id' => $course->id,
        'title' => 'Legacy reading lesson',
        'content' => 'Existing legacy reading content stays available.',
        'lesson_type' => 'text',
        'lesson_order' => 1,
        'is_published' => true,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $sectionId = DB::table('lesson_sections')->insertGetId([
        'lesson_id' => $lessonId,
        'type' => 'file',
        'body' => null,
        'caption' => 'Download the handout',
        'file_path' => 'reading/private/lesson-handout.pdf',
        'original_name' => 'lesson-handout.pdf',
        'mime_type' => 'application/pdf',
        'position' => 3,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $migration = require database_path('migrations/2026_11_21_130000_create_lesson_sections_table.php');
    $db = DB::connection();
    $db->enableQueryLog();
    $db->flushQueryLog();

    expect(fn () => $migration->down())->toThrow(RuntimeException::class, 'forward-only');
    $rollbackQueries = $db->getQueryLog();

    expect($rollbackQueries)->toBe([])
        ->and(Schema::hasTable('lesson_sections'))->toBeTrue()
        ->and(DB::table('lesson_sections')->where('id', $sectionId)->value('lesson_id'))->toBe($lessonId)
        ->and(DB::table('lesson_sections')->where('id', $sectionId)->value('position'))->toBe(3)
        ->and(DB::table('lesson_sections')->where('id', $sectionId)->value('file_path'))->toBe('reading/private/lesson-handout.pdf')
        ->and(DB::table('lessons')->where('id', $lessonId)->value('content'))->toBe('Existing legacy reading content stays available.');
});