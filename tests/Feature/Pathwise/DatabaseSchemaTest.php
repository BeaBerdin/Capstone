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

test('the unused payments and course_reviews tables do not exist after migrating', function (string $table) {
    expect(Schema::hasTable($table))->toBeFalse();
})->with(['payments', 'course_reviews']);

test('rolling back the drop migration recreates both tables as they were', function () {
    $migration = require database_path('migrations/2026_10_21_000001_drop_unused_payments_and_course_reviews_tables.php');

    $migration->down();

    foreach (['payments', 'course_reviews'] as $table) {
        expect(Schema::hasTable($table))->toBeTrue()
            ->and(Schema::getColumnListing($table))->toEqualCanonicalizing(['id', 'created_at', 'updated_at']);
    }

    $migration->up();

    expect(Schema::hasTable('payments'))->toBeFalse()
        ->and(Schema::hasTable('course_reviews'))->toBeFalse();
});
