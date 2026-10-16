<?php

use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

// PathWise sends each role to its own dashboard (Manuscript Figures 35-38).
test('each role is redirected to its own dashboard', function (string $role, string $routeName) {
    $user = pathwiseUser($role);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route($routeName));
})->with([
    'super admin' => ['super_admin', 'super_admin.dashboard'],
    'administrator' => ['admin', 'admin.dashboard'],
    'teacher' => ['teacher', 'teacher.dashboard'],
    'student' => ['student', 'student.dashboard'],
]);

test('a user without a role cannot open the dashboard', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertForbidden();
});
