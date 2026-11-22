<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    $this->previousDefaultConnection = DB::getDefaultConnection();

    config(['database.connections.teacher_approval_upgrade' => [
        'driver' => 'sqlite',
        'database' => ':memory:',
        'prefix' => '',
        'foreign_key_constraints' => true,
    ]]);

    DB::purge('teacher_approval_upgrade');
    DB::setDefaultConnection('teacher_approval_upgrade');
    Schema::clearResolvedInstance('db.schema');

    Schema::create('student_applications', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('user_id');
        $table->unsignedInteger('submission_no');
        $table->string('study_load_path');
        $table->string('study_load_original_name');
        $table->string('study_load_mime');
        $table->unsignedInteger('study_load_size');
        $table->string('status');
        $table->timestamp('submitted_at');
        $table->unsignedBigInteger('reviewed_by')->nullable();
        $table->timestamp('reviewed_at')->nullable();
        $table->text('rejection_reason')->nullable();
        $table->timestamps();
    });

    DB::table('student_applications')->insert([
        [
            'user_id' => 101,
            'submission_no' => 1,
            'study_load_path' => '',
            'study_load_original_name' => '',
            'study_load_mime' => '',
            'study_load_size' => 0,
            'status' => 'approved',
            'submitted_at' => '2026-11-01 09:00:00',
            'reviewed_by' => 900,
            'reviewed_at' => '2026-11-02 09:00:00',
            'rejection_reason' => null,
            'created_at' => '2026-11-01 09:00:00',
            'updated_at' => '2026-11-02 09:00:00',
        ],
        [
            'user_id' => 202,
            'submission_no' => 1,
            'study_load_path' => 'study-loads/legacy-student.pdf',
            'study_load_original_name' => 'legacy-student.pdf',
            'study_load_mime' => 'application/pdf',
            'study_load_size' => 4096,
            'status' => 'pending',
            'submitted_at' => '2026-11-03 09:00:00',
            'reviewed_by' => null,
            'reviewed_at' => null,
            'rejection_reason' => null,
            'created_at' => '2026-11-03 09:00:00',
            'updated_at' => '2026-11-03 09:00:00',
        ],
    ]);
});

afterEach(function () {
    DB::setDefaultConnection($this->previousDefaultConnection);
    DB::purge('teacher_approval_upgrade');
    Schema::clearResolvedInstance('db.schema');
});

function teacherApprovalMigrationForTest(): Illuminate\Database\Migrations\Migration
{
    return require database_path('migrations/2026_11_21_150000_bind_teacher_approvals_to_role_and_email.php');
}

test('teacher approval migration classifies historical teacher reviews without granting or rewriting approval', function () {
    teacherApprovalMigrationForTest()->up();

    $history = DB::table('student_applications')->where('user_id', 101)->first();

    expect($history->applicant_role)->toBe('teacher')
        ->and($history->review_email)->toBeNull()
        ->and($history->review_origin)->toBe('legacy_history')
        ->and($history->status)->toBe('approved')
        ->and((int) $history->reviewed_by)->toBe(900)
        ->and($history->reviewed_at)->toBe('2026-11-02 09:00:00');
});

test('teacher approval migration preserves student Study Load records and decisions', function () {
    $before = DB::table('student_applications')->where('user_id', 202)->first();

    teacherApprovalMigrationForTest()->up();

    $after = DB::table('student_applications')->where('user_id', 202)->first();

    expect($after->applicant_role)->toBe('student')
        ->and($after->review_email)->toBeNull()
        ->and($after->review_origin)->toBeNull()
        ->and($after->study_load_path)->toBe($before->study_load_path)
        ->and($after->study_load_original_name)->toBe($before->study_load_original_name)
        ->and($after->study_load_mime)->toBe($before->study_load_mime)
        ->and((int) $after->study_load_size)->toBe((int) $before->study_load_size)
        ->and($after->status)->toBe($before->status)
        ->and($after->submitted_at)->toBe($before->submitted_at);
});

test('teacher approval migration rollback refuses before SQL and preserves review metadata', function () {
    $migration = teacherApprovalMigrationForTest();
    $migration->up();
    DB::table('student_applications')->where('user_id', 101)->update([
        'review_email' => 'reviewed@example.test',
        'review_origin' => 'edp_created',
    ]);

    $db = DB::connection();
    $db->enableQueryLog();
    $db->flushQueryLog();

    expect(fn () => $migration->down())->toThrow(
        RuntimeException::class,
        'forward-only',
    );
    $rollbackQueries = $db->getQueryLog();

    $history = DB::table('student_applications')->where('user_id', 101)->first();

    expect($rollbackQueries)->toBe([])
        ->and(Schema::hasColumn('student_applications', 'applicant_role'))->toBeTrue()
        ->and(Schema::hasColumn('student_applications', 'review_email'))->toBeTrue()
        ->and(Schema::hasColumn('student_applications', 'review_origin'))->toBeTrue()
        ->and($history->applicant_role)->toBe('teacher')
        ->and($history->review_email)->toBe('reviewed@example.test')
        ->and($history->review_origin)->toBe('edp_created')
        ->and($history->status)->toBe('approved');
});
