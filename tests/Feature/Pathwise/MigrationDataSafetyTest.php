<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

test('legacy table cleanup checks both tables before dropping either', function () {
    Schema::create('payments', function (Blueprint $table) {
        $table->id();
    });
    DB::table('course_reviews')->insert([
        'course_id' => pathwiseCourse(pathwiseUser('teacher'))->id,
        'student_id' => pathwiseUser('student')->id,
        'rating' => 5,
    ]);

    $migration = require database_path('migrations/2026_10_21_000001_drop_unused_payments_and_course_reviews_tables.php');

    expect(fn () => $migration->up())->toThrow(RuntimeException::class, 'course_reviews contains existing data');
    expect(Schema::hasTable('payments'))->toBeTrue();
    expect(DB::table('course_reviews')->count())->toBe(1);
});

test('legacy table cleanup preserves populated payments', function () {
    Schema::create('payments', function (Blueprint $table) {
        $table->id();
    });
    DB::table('payments')->insert(['id' => 1]);

    $migration = require database_path('migrations/2026_10_21_000001_drop_unused_payments_and_course_reviews_tables.php');

    expect(fn () => $migration->up())->toThrow(RuntimeException::class, 'payments contains existing data');
    expect(DB::table('payments')->count())->toBe(1);
    expect(Schema::hasTable('course_reviews'))->toBeTrue();
});

test('execution column cleanup preserves existing submission values', function () {
    $migration = require database_path('migrations/2026_10_15_233405_remove_code_execution_fields_from_submissions_table.php');
    $migration->down();

    $student = pathwiseUser('student');
    $course = pathwiseCourse(pathwiseUser('teacher'));
    $assignment = App\Models\Assignment::create([
        'course_id' => $course->id,
        'title' => 'Preserved assignment',
        'description' => 'Preserve legacy execution data.',
        'submission_type' => 'code',
        'max_score' => 100,
    ]);
    $submission = App\Models\Submission::create([
        'assignment_id' => $assignment->id,
        'student_id' => $student->id,
        'status' => 'submitted',
    ]);
    DB::table('submissions')->where('id', $submission->id)->update(['execution_output' => 'Saved output']);

    expect(fn () => $migration->up())->toThrow(RuntimeException::class, 'execution_output contains existing data');
    expect(DB::table('submissions')->where('id', $submission->id)->value('execution_output'))->toBe('Saved output');
    expect(Schema::hasColumn('submissions', 'code_language'))->toBeTrue();
});
