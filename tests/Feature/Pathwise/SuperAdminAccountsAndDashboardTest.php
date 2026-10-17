<?php

use App\Models\Enrollment;
use App\Models\Transaction;
use App\Models\User;

/*
| B6 / Manuscript FR (n) and the Super Admin Dashboard description:
| the Super Admin verifies accounts and edits name and email (no deleting),
| and the dashboard shows Total Users, Pending Role Assignments, Payments
| to Verify, System Status and a Recent Activity panel.
*/

test('the Super Admin can mark an unverified email as verified', function () {
    $student = pathwiseUser('student', ['email_verified_at' => null]);

    $this->actingAs(pathwiseUser('super_admin'))
        ->patch(route('users.verify-email', $student))
        ->assertSessionHas('success');

    expect($student->fresh()->hasVerifiedEmail())->toBeTrue();
});

test('the users page offers Mark verified only for unverified emails', function () {
    $unverified = pathwiseUser('student', ['email_verified_at' => null]);
    $verified = pathwiseUser('student');

    $this->actingAs(pathwiseUser('super_admin'))
        ->get(route('users.index'))
        ->assertOk()
        ->assertSee(route('users.verify-email', $unverified), false)
        ->assertDontSee(route('users.verify-email', $verified), false)
        ->assertSee(route('users.edit', $verified), false);
});

test('the Super Admin can edit a user\'s name and email', function () {
    $teacher = pathwiseUser('teacher', ['name' => 'Old Name', 'email' => 'old@example.com']);

    $this->actingAs(pathwiseUser('super_admin'))
        ->get(route('users.edit', $teacher))
        ->assertOk()
        ->assertSee('Old Name')
        ->assertSee('old@example.com');

    $this->actingAs(pathwiseUser('super_admin'))
        ->put(route('users.update', $teacher), [
            'name' => 'Maria Santos',
            'email' => 'maria.santos@example.com',
        ])
        ->assertRedirect(route('users.index'))
        ->assertSessionHasNoErrors();

    $teacher->refresh();

    expect($teacher->name)->toBe('Maria Santos')
        ->and($teacher->email)->toBe('maria.santos@example.com')
        ->and($teacher->hasVerifiedEmail())->toBeTrue()
        ->and($teacher->hasRole('teacher'))->toBeTrue();
});

test('changing only the name keeps the email verification as it was', function () {
    $student = pathwiseUser('student', ['email' => 'same@example.com', 'email_verified_at' => null]);

    $this->actingAs(pathwiseUser('super_admin'))
        ->put(route('users.update', $student), [
            'name' => 'Renamed Student',
            'email' => 'same@example.com',
        ])
        ->assertSessionHasNoErrors();

    expect($student->fresh()->name)->toBe('Renamed Student')
        ->and($student->fresh()->hasVerifiedEmail())->toBeFalse();
});

test('the email must be valid and not used by another account', function () {
    pathwiseUser('student', ['email' => 'taken@example.com']);
    $student = pathwiseUser('student', ['name' => 'Keep Me', 'email' => 'mine@example.com']);
    $superAdmin = pathwiseUser('super_admin');

    $this->actingAs($superAdmin)
        ->put(route('users.update', $student), ['name' => 'Keep Me', 'email' => 'taken@example.com'])
        ->assertSessionHasErrors('email');

    $this->actingAs($superAdmin)
        ->put(route('users.update', $student), ['name' => 'Keep Me', 'email' => 'not-an-email'])
        ->assertSessionHasErrors('email');

    $this->actingAs($superAdmin)
        ->put(route('users.update', $student), ['name' => '', 'email' => 'mine@example.com'])
        ->assertSessionHasErrors('name');

    expect($student->fresh()->email)->toBe('mine@example.com')
        ->and($student->fresh()->name)->toBe('Keep Me');
});

test('there is no way to delete a user from User Management', function () {
    $student = pathwiseUser('student');

    $response = $this->actingAs(pathwiseUser('super_admin'))
        ->delete('/super-admin/users/'.$student->id);

    expect($response->status())->toBeIn([404, 405])
        ->and(User::find($student->id))->not->toBeNull();
});

test('only the Super Admin can verify or edit accounts', function (string $role) {
    $student = pathwiseUser('student', ['name' => 'Untouched', 'email_verified_at' => null]);
    $user = pathwiseUser($role);

    $this->actingAs($user)->patch(route('users.verify-email', $student))->assertForbidden();
    $this->actingAs($user)->get(route('users.edit', $student))->assertForbidden();
    $this->actingAs($user)
        ->put(route('users.update', $student), ['name' => 'Hacked', 'email' => 'hacked@example.com'])
        ->assertForbidden();

    expect($student->fresh()->name)->toBe('Untouched')
        ->and($student->fresh()->hasVerifiedEmail())->toBeFalse();
})->with(['admin', 'teacher', 'student']);

test('the dashboard shows the manuscript summary cards with real numbers', function () {
    $superAdmin = pathwiseUser('super_admin');
    $student = pathwiseUser('student');
    User::factory()->create(['name' => 'No Role Yet']);   // no role
    $course = pathwiseCourse(pathwiseUser('teacher'), ['status' => 'published', 'price' => 499]);

    Transaction::create([
        'student_id' => $student->id,
        'course_id' => $course->id,
        'transaction_no' => 'TXN-DASH-1',
        'amount' => 499,
        'payment_method' => 'PayMongo',
        'status' => 'pending',
    ]);

    $this->actingAs($superAdmin)
        ->get(route('super_admin.dashboard'))
        ->assertOk()
        ->assertSee('data-dashboard-section="summary"', false)
        ->assertSeeInOrder(['Total Users', '4', 'Pending Role Assignments', '1', 'Payments to Verify', '1', 'System Status'])
        ->assertViewHas('pendingRoleAssignments', 1)
        ->assertViewHas('paymentsToVerify', 1);
});

test('System Status lists what is not configured, without showing any keys', function () {
    config([
        'services.gemini.api_key' => 'secret-gemini-key',
        'services.paymongo.secret_key' => null,
        'services.paymongo.public_key' => null,
        'mail.default' => 'log',
    ]);

    $this->actingAs(pathwiseUser('super_admin'))
        ->get(route('super_admin.dashboard'))
        ->assertOk()
        ->assertSee('Needs attention')
        ->assertSee('Not configured: PayMongo payments, Email delivery')
        ->assertDontSee('secret-gemini-key');
});

test('System Status shows Operational when everything is configured', function () {
    config([
        'services.gemini.api_key' => 'key',
        'services.paymongo.secret_key' => 'sk_test_x',
        'services.paymongo.public_key' => 'pk_test_x',
        'mail.default' => 'smtp',
    ]);

    $this->actingAs(pathwiseUser('super_admin'))
        ->get(route('super_admin.dashboard'))
        ->assertOk()
        ->assertSee('Operational')
        ->assertSee('All services configured');
});

test('Recent Activity lists registrations, role assignments, enrollments and payments with tags', function () {
    $superAdmin = pathwiseUser('super_admin');

    $this->travel(1)->minutes();
    $pending = User::factory()->unverified()->create(['name' => 'Pending Registrant']);

    $this->travel(1)->minutes();
    $student = pathwiseUser('student', ['name' => 'Enrolled Student']);
    $course = pathwiseCourse(pathwiseUser('teacher'), ['status' => 'published', 'title' => 'Dashboard Course', 'price' => 499]);

    $this->travel(1)->minutes();
    Enrollment::create([
        'student_id' => $student->id,
        'course_id' => $course->id,
        'status' => 'active',
        'enrolled_at' => now(),
        'progress_percentage' => 0,
    ]);

    $this->travel(1)->minutes();
    Transaction::create([
        'student_id' => $student->id,
        'course_id' => $course->id,
        'transaction_no' => 'TXN-DASH-2',
        'amount' => 499,
        'payment_method' => 'PayMongo',
        'status' => 'approved',
    ]);

    $response = $this->actingAs($superAdmin)
        ->get(route('super_admin.dashboard'))
        ->assertOk()
        ->assertSee('data-dashboard-section="recent-activity"', false)
        ->assertSee('Pending Registrant')
        ->assertSee('Assigned role: student')
        ->assertSee('Dashboard Course')
        ->assertSee('₱499.00');

    $activity = $response->viewData('recentActivity');

    // Newest first: the payment, then the enrollment.
    expect($activity->first()['type'])->toBe('Payment')
        ->and($activity->first()['status'])->toBe('Verified')
        ->and($activity->get(1)['type'])->toBe('Enrollment')
        ->and($activity->firstWhere('title', 'Pending Registrant')['status'])->toBe('Pending')
        ->and($activity)->toHaveCount(8);
});

test('assigning a role records when it happened', function () {
    $user = User::factory()->create();
    $role = App\Models\Role::firstOrCreate(['name' => 'teacher']);

    $user->roles()->sync([$role->id]);

    expect(DB::table('user_roles')->where('user_id', $user->id)->value('created_at'))->not->toBeNull();
});
