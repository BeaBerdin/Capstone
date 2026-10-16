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
