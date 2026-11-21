<?php

use App\Models\User;

/*
| Manuscript: Functional Requirement (n) and Figure 31
| "Manage User Accounts and Roles" (Super Admin / EDP).
*/

beforeEach(function () {
    $this->seed(\Database\Seeders\RoleSeeder::class);
});

test('the super admin can open the user management page', function () {
    $this->actingAs(pathwiseUser('super_admin'))
        ->get(route('users.index'))
        ->assertOk()
        ->assertSee('Create Account');
});

test('teacher account screens explain EDP review and link existing teachers to their review', function () {
    $teacher = pathwiseUser('teacher');
    $edp = pathwiseUser('super_admin');

    $this->actingAs($edp)
        ->get(route('users.index'))
        ->assertOk()
        ->assertSee('Teacher accounts require EDP approval and email confirmation.')
        ->assertSee(route('users.teacher-review', $teacher), false)
        ->assertSee('Open teacher review');

    $this->get(route('users.create'))
        ->assertOk()
        ->assertSee('Teacher accounts enter the EDP review queue and confirm their email after approval.');
});

test('the super admin can create an administrator account', function () {
    $this->actingAs(pathwiseUser('super_admin'))
        ->post(route('users.store'), [
            'name' => 'Department Head',
            'email' => 'head@uclm.edu.ph',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'admin',
        ])
        ->assertRedirect(route('users.index'))
        ->assertSessionHasNoErrors();

    $created = User::where('email', 'head@uclm.edu.ph')->firstOrFail();

    expect($created->hasRole('admin'))->toBeTrue()
        ->and($created->hasVerifiedEmail())->toBeTrue()
        ->and($created->is_active)->toBeTrue();
});

test('creating an account rejects an unknown role', function () {
    $this->actingAs(pathwiseUser('super_admin'))
        ->post(route('users.store'), [
            'name' => 'Someone',
            'email' => 'someone@uclm.edu.ph',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'owner',
        ])
        ->assertSessionHasErrors('role');

    expect(User::where('email', 'someone@uclm.edu.ph')->exists())->toBeFalse();
});

test('only the super admin can create accounts', function () {
    $this->actingAs(pathwiseUser('admin'))
        ->post(route('users.store'), [
            'name' => 'Sneaky',
            'email' => 'sneaky@uclm.edu.ph',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'super_admin',
        ])
        ->assertForbidden();
});

test('the super admin can deactivate and reactivate an account', function () {
    $superAdmin = pathwiseUser('super_admin');
    $teacher = pathwiseUser('teacher');

    $this->actingAs($superAdmin)
        ->patch(route('users.update-status', $teacher), ['is_active' => 0])
        ->assertSessionHas('success');

    expect($teacher->fresh()->is_active)->toBeFalse();

    $this->actingAs($superAdmin)
        ->patch(route('users.update-status', $teacher), ['is_active' => 1]);

    expect($teacher->fresh()->is_active)->toBeTrue();
});

test('the super admin cannot deactivate their own account', function () {
    $superAdmin = pathwiseUser('super_admin');

    $this->actingAs($superAdmin)
        ->patch(route('users.update-status', $superAdmin), ['is_active' => 0])
        ->assertSessionHas('error');

    expect($superAdmin->fresh()->is_active)->toBeTrue();
});

test('a deactivated user is logged out on their next request', function () {
    $student = pathwiseUser('student');
    $student->forceFill(['is_active' => false])->save();

    $this->actingAs($student)
        ->get(route('student.dashboard'))
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('a deactivated user cannot log in', function () {
    $student = pathwiseUser('student');
    $student->forceFill(['is_active' => false])->save();

    $this->post(route('login.store'), [
        'email' => $student->email,
        'password' => 'password',
    ]);

    // The login itself succeeds, but the very next request logs them out.
    $this->get(route('dashboard'))->assertRedirect(route('login'));

    $this->assertGuest();
});
