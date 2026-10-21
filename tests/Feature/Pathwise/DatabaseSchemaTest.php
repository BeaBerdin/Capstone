<?php

use Illuminate\Support\Facades\Schema;

/*
| These tests only pass if EVERY migration runs on a brand-new database.
| Before the fixes, a fresh install crashed because a migration created
| the course_invitations table twice.
*/

test('all migrations run on a fresh database and create the expected columns', function (string $table, string $column) {
    expect(Schema::hasColumn($table, $column))->toBeTrue();
})->with([
    'transactions.course_invitation_id' => ['transactions', 'course_invitation_id'],
    'users.is_active' => ['users', 'is_active'],
    'courses.review_remarks' => ['courses', 'review_remarks'],
    'courses.template_id' => ['courses', 'template_id'],
    'course_templates.name' => ['course_templates', 'name'],
    'course_templates.structure' => ['course_templates', 'structure'],
    'course_templates.is_active' => ['course_templates', 'is_active'],
]);

test('new users are active by default', function () {
    $user = \App\Models\User::factory()->create()->fresh();

    expect($user->is_active)->toBeTrue();
});

test('the unused payments table does not exist after migrating', function () {
    expect(Schema::hasTable('payments'))->toBeFalse();
});

test('course_reviews is the real reviews table after migrating', function () {
    // The empty placeholder was dropped (2026_10_21_000001) and the real
    // table created again (2026_10_21_000002).
    expect(Schema::getColumnListing('course_reviews'))->toEqualCanonicalizing([
        'id', 'course_id', 'student_id', 'rating', 'comment',
        'is_hidden', 'hidden_reason', 'hidden_by', 'created_at', 'updated_at',
    ]);
});

test('rolling back the drop migration recreates payments as it was and leaves course_reviews alone', function () {
    $migration = require database_path('migrations/2026_10_21_000001_drop_unused_payments_and_course_reviews_tables.php');

    $migration->down();

    expect(Schema::hasTable('payments'))->toBeTrue()
        ->and(Schema::getColumnListing('payments'))->toEqualCanonicalizing(['id', 'created_at', 'updated_at'])
        ->and(Schema::hasColumn('course_reviews', 'rating'))->toBeTrue();
});
