<?php

/*
| PathWise is light mode only. The shared layout must not load Flux's
| appearance script (the only thing that adds the "dark" class), and the old
| Appearance settings page now redirects to the profile page.
*/

test('every role dashboard renders in light mode only', function (string $role, string $route) {
    $this->actingAs(pathwiseRoleBoundaryUser($role))
        ->get(route($route))
        ->assertOk()
        ->assertSee('<meta name="color-scheme" content="light" />', false)
        ->assertDontSee('applyAppearance', false);
})->with([
    'super admin' => ['super_admin', 'super_admin.dashboard'],
    'admin' => ['admin', 'admin.dashboard'],
    'teacher' => ['teacher', 'teacher.dashboard'],
    'student' => ['student', 'student.dashboard'],
]);

test('the old appearance settings page redirects to the profile page', function () {
    $this->actingAs(pathwiseUser('student'))
        ->get(route('appearance.edit'))
        ->assertRedirect('/settings/profile');
});

test('the settings menu no longer offers an appearance option', function () {
    $this->actingAs(pathwiseUser('student'))
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertDontSee('settings/appearance', false);
});
