<?php

use App\Http\Middleware\EnsureUserIsActive;
use App\Models\AuditLog;
use App\Models\StudentApplication;
use App\Models\User;
use Illuminate\Support\Facades\Notification;

/*
| Super Admin promotion safeguards and the audit log.
|
| Every role change needs a reason; promoting to Super Admin also needs the
| acting Super Admin's own password; the last active Super Admin cannot be
| demoted or deactivated. Sensitive actions are written to a read-only audit
| log that only the Super Admin can open. All checks are on the server.
*/

beforeEach(function () {
    $this->seed(\Database\Seeders\RoleSeeder::class);
    fakeFileDisks();
});

function safeguardRoles(User $user): array
{
    return $user->fresh()->roles()->pluck('name')->all();
}

/*
|--------------------------------------------------------------------------
| Only the Super Admin
|--------------------------------------------------------------------------
*/

test('non-Super-Admins get 403 on role changes and the Audit Log page', function (string $role) {
    // A fully approved teacher, so the role check itself answers (an
    // unapproved teacher is stopped even earlier by the EDP review).
    $actor = $role === 'teacher' ? pathwiseApprovedTeacher() : pathwiseUser($role);
    $target = pathwiseUser('student');

    $this->actingAs($actor)
        ->put(route('users.update-role', $target), ['role' => 'super_admin', 'reason' => 'Trying to escalate', 'current_password' => 'password'])
        ->assertForbidden();

    $this->actingAs($actor)
        ->put(route('users.update-role', $actor), ['role' => 'super_admin', 'reason' => 'Self promotion', 'current_password' => 'password'])
        ->assertForbidden();

    $this->actingAs($actor)->get(route('super-admin.audit-log.index'))->assertForbidden();

    expect(safeguardRoles($target))->toBe(['student'])
        ->and(safeguardRoles($actor))->toBe([$role])
        ->and(AuditLog::count())->toBe(0);
})->with(['admin', 'teacher', 'student']);

test('a guest is sent to the login page from the Audit Log', function () {
    $this->get(route('super-admin.audit-log.index'))->assertRedirect(route('login'));
});

/*
|--------------------------------------------------------------------------
| Reason required for every role change
|--------------------------------------------------------------------------
*/

test('every role change requires a short reason', function (?string $reason, string $role) {
    $student = pathwiseUser('student');

    $this->actingAs(pathwiseUser('super_admin'))
        ->put(route('users.update-role', $student), array_filter(['role' => $role, 'reason' => $reason]))
        ->assertSessionHasErrors('reason');

    expect(safeguardRoles($student))->toBe(['student'])
        ->and(AuditLog::count())->toBe(0);
})->with([
    'missing, to admin' => [null, 'admin'],
    'missing, to teacher' => [null, 'teacher'],
    'blank, to admin' => ['   ', 'admin'],
    'over 500 characters' => [str_repeat('x', 501), 'admin'],
]);

test('a role change with a reason is logged with actor, target, old and new role, reason and IP', function () {
    $superAdmin = pathwiseUser('super_admin');
    $student = pathwiseUser('student');

    $this->actingAs($superAdmin)
        ->put(route('users.update-role', $student), ['role' => 'admin', 'reason' => 'Appointed as department head'])
        ->assertSessionHas('success');

    $log = AuditLog::sole();

    expect(safeguardRoles($student))->toBe(['admin'])
        ->and($log->action)->toBe(AuditLog::ROLE_CHANGED)
        ->and($log->actor_id)->toBe($superAdmin->id)
        ->and($log->target_user_id)->toBe($student->id)
        ->and($log->old_value)->toBe('student')
        ->and($log->new_value)->toBe('admin')
        ->and($log->reason)->toBe('Appointed as department head')
        ->and($log->ip_address)->toBe('127.0.0.1')
        ->and($log->created_at)->not->toBeNull();
});

test('a role change to Teacher still goes to EDP review and is logged', function () {
    $student = pathwiseUser('student');

    $this->actingAs(pathwiseUser('super_admin'))
        ->put(route('users.update-role', $student), ['role' => 'teacher', 'reason' => 'Hired as part-time faculty'])
        ->assertSessionHas('success', 'Teacher role assigned. EDP review and email confirmation are required before teacher access.');

    $teacher = $student->fresh();

    expect($teacher->hasRole('teacher'))->toBeTrue()
        ->and($teacher->hasVerifiedEmail())->toBeFalse()
        ->and($teacher->currentTeacherApplication())->not->toBeNull()
        ->and($teacher->currentTeacherApplication()->status)->toBe(StudentApplication::PENDING)
        ->and(AuditLog::where('action', AuditLog::ROLE_CHANGED)->value('reason'))->toBe('Hired as part-time faculty');
});

/*
|--------------------------------------------------------------------------
| Promotion to Super Admin: password re-entry
|--------------------------------------------------------------------------
*/

test('promoting to Super Admin is refused without the acting Super Admin\'s correct password', function (?string $password, string $error) {
    $admin = pathwiseUser('admin');

    $this->actingAs(pathwiseUser('super_admin'))
        ->put(route('users.update-role', $admin), array_filter([
            'role' => 'super_admin',
            'reason' => 'Second EDP officer',
            'current_password' => $password,
        ]))
        ->assertSessionHasErrors(['current_password' => $error]);

    expect(safeguardRoles($admin))->toBe(['admin'])
        ->and(AuditLog::count())->toBe(0);
})->with([
    'no password' => [null, 'Please re-enter your password to make this user a Super Admin.'],
    'wrong password' => ['not-my-password', 'The password you entered is incorrect. The role was not changed.'],
]);

test('the target\'s password does not count, only the acting Super Admin\'s', function () {
    $admin = pathwiseUser('admin', ['password' => 'Target-Secret-7']);

    $this->actingAs(pathwiseUser('super_admin', ['password' => 'Actor-Secret-9']))
        ->put(route('users.update-role', $admin), ['role' => 'super_admin', 'reason' => 'Second EDP officer', 'current_password' => 'Target-Secret-7'])
        ->assertSessionHasErrors('current_password');

    expect(safeguardRoles($admin))->toBe(['admin']);
});

test('promoting to Super Admin with the correct password works and the password is never logged', function () {
    $superAdmin = pathwiseUser('super_admin', ['password' => 'Correct-Horse-91']);
    $admin = pathwiseUser('admin');

    $this->actingAs($superAdmin)
        ->put(route('users.update-role', $admin), ['role' => 'super_admin', 'reason' => 'Second EDP officer', 'current_password' => 'Correct-Horse-91'])
        ->assertSessionHas('success');

    $log = AuditLog::sole();
    $stored = json_encode($log->getAttributes());

    expect(safeguardRoles($admin))->toBe(['super_admin'])
        ->and($log->action)->toBe(AuditLog::PROMOTED_SUPER_ADMIN)
        ->and($log->old_value)->toBe('admin')
        ->and($log->new_value)->toBe('super_admin')
        ->and($log->reason)->toBe('Second EDP officer')
        ->and($stored)->not->toContain('Correct-Horse-91')
        ->and($stored)->not->toContain('current_password');
});

test('the User Management page has the confirmation modal with reason and password fields', function () {
    $this->actingAs(pathwiseUser('super_admin'))
        ->get(route('users.index'))
        ->assertOk()
        ->assertSee('data-test="role-dialog"', false)
        ->assertSee('id="role-dialog-reason"', false)
        ->assertSee('id="role-dialog-password"', false)
        ->assertSee('name="current_password"', false)
        ->assertSee('data-test="nav-audit-log"', false);
});

/*
|--------------------------------------------------------------------------
| Existing protections are kept
|--------------------------------------------------------------------------
*/

test('a Super Admin still cannot remove their own Super Admin role or deactivate themselves', function () {
    $superAdmin = pathwiseUser('super_admin');
    pathwiseUser('super_admin');

    $this->actingAs($superAdmin)
        ->put(route('users.update-role', $superAdmin), ['role' => 'admin', 'reason' => 'Stepping down'])
        ->assertSessionHas('error', 'You cannot remove your own Super Admin role.');

    $this->actingAs($superAdmin)
        ->patch(route('users.update-status', $superAdmin), ['is_active' => 0])
        ->assertSessionHas('error', 'You cannot deactivate your own account.');

    expect(safeguardRoles($superAdmin))->toBe(['super_admin'])
        ->and($superAdmin->fresh()->is_active)->not->toBeFalse()
        ->and(AuditLog::count())->toBe(0);
});

test('mark verified still cannot bypass EDP approval', function () {
    $edp = pathwiseUser('super_admin');
    $teacher = pathwiseUser('teacher', ['email_verified_at' => null]);
    $student = pathwiseUser('student', ['email_verified_at' => null]);
    StudentApplication::create([
        'user_id' => $student->id,
        'submission_no' => 1,
        'study_load_path' => 'study-loads/'.$student->id.'/load.pdf',
        'study_load_original_name' => 'load.pdf',
        'study_load_mime' => 'application/pdf',
        'study_load_size' => 10,
        'status' => StudentApplication::PENDING,
        'submitted_at' => now(),
    ]);

    $this->actingAs($edp)->patch(route('users.verify-email', $teacher))->assertSessionHas('error');
    $this->actingAs($edp)->patch(route('users.verify-email', $student))->assertSessionHas('error');

    expect($teacher->fresh()->hasVerifiedEmail())->toBeFalse()
        ->and($student->fresh()->hasVerifiedEmail())->toBeFalse()
        ->and(AuditLog::count())->toBe(0);
});

/*
|--------------------------------------------------------------------------
| The last active Super Admin
|--------------------------------------------------------------------------
*/

test('the last active Super Admin cannot be demoted or deactivated', function () {
    // The acting Super Admin was deactivated a moment ago by someone else
    // (simulated by skipping the "signed out when deactivated" middleware),
    // so the target is the last ACTIVE Super Admin.
    $this->withoutMiddleware(EnsureUserIsActive::class);

    $actor = pathwiseUser('super_admin', ['is_active' => false]);
    $lastActive = pathwiseUser('super_admin');

    $this->actingAs($actor)
        ->put(route('users.update-role', $lastActive), ['role' => 'admin', 'reason' => 'Reorganisation'])
        ->assertSessionHas('error', 'You cannot change the role of the last active Super Admin.');

    $this->actingAs($actor)
        ->patch(route('users.update-status', $lastActive), ['is_active' => 0])
        ->assertSessionHas('error', 'You cannot deactivate the last active Super Admin.');

    expect(safeguardRoles($lastActive))->toBe(['super_admin'])
        ->and($lastActive->fresh()->is_active)->not->toBeFalse()
        ->and(AuditLog::count())->toBe(0);
});

test('a Super Admin can be demoted or deactivated while another active one remains', function () {
    $actor = pathwiseUser('super_admin');
    $other = pathwiseUser('super_admin');
    $third = pathwiseUser('super_admin');

    $this->actingAs($actor)
        ->put(route('users.update-role', $other), ['role' => 'admin', 'reason' => 'Moved to the dean\'s office'])
        ->assertSessionHas('success');

    $this->actingAs($actor)
        ->patch(route('users.update-status', $third), ['is_active' => 0])
        ->assertSessionHas('success');

    expect(safeguardRoles($other))->toBe(['admin'])
        ->and($third->fresh()->is_active)->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| Every sensitive action writes an audit entry
|--------------------------------------------------------------------------
*/

test('mark verified, activate, deactivate and name/email edits are logged', function () {
    $edp = pathwiseUser('super_admin');
    $admin = pathwiseUser('admin', ['name' => 'Old Name', 'email' => 'old@uclm.edu.ph', 'email_verified_at' => null]);

    $this->actingAs($edp)->patch(route('users.verify-email', $admin))->assertSessionHas('success');
    $this->actingAs($edp)->patch(route('users.update-status', $admin), ['is_active' => 0])->assertSessionHas('success');
    $this->actingAs($edp)->patch(route('users.update-status', $admin), ['is_active' => 1])->assertSessionHas('success');
    $this->actingAs($edp)->put(route('users.update', $admin), ['name' => 'New Name', 'email' => 'new@uclm.edu.ph'])->assertSessionHasNoErrors();

    expect(AuditLog::orderBy('id')->pluck('action')->all())->toBe([
        AuditLog::EMAIL_VERIFIED,
        AuditLog::DEACTIVATED,
        AuditLog::ACTIVATED,
        AuditLog::DETAILS_UPDATED,
    ]);

    $edit = AuditLog::where('action', AuditLog::DETAILS_UPDATED)->sole();

    expect($edit->old_value)->toBe('Name: Old Name; Email: old@uclm.edu.ph')
        ->and($edit->new_value)->toBe('Name: New Name; Email: new@uclm.edu.ph')
        ->and(AuditLog::query()->pluck('target_user_id')->unique()->all())->toBe([$admin->id])
        ->and(AuditLog::query()->pluck('actor_id')->unique()->all())->toBe([$edp->id]);
});

test('saving a status or details without a change writes no entry', function () {
    $edp = pathwiseUser('super_admin');
    $admin = pathwiseUser('admin');

    $this->actingAs($edp)->patch(route('users.update-status', $admin), ['is_active' => 1]);
    $this->actingAs($edp)->put(route('users.update', $admin), ['name' => $admin->name, 'email' => $admin->email]);

    expect(AuditLog::count())->toBe(0);
});

test('EDP application approval and rejection are logged', function () {
    Notification::fake();
    $edp = pathwiseUser('super_admin');
    $approved = pathwiseUser('teacher', ['email_verified_at' => null]);
    $rejected = pathwiseUser('teacher', ['email_verified_at' => null]);
    $approve = StudentApplication::submitForTeacher($approved, 'registration');
    $reject = StudentApplication::submitForTeacher($rejected, 'registration');

    $this->actingAs($edp)
        ->post(route('super-admin.student-applications.approve', $approve), ['faculty_confirmed' => '1'])
        ->assertSessionHas('success');

    $this->actingAs($edp)
        ->post(route('super-admin.student-applications.reject', $reject), ['rejection_reason' => 'We could not confirm this faculty member.'])
        ->assertSessionHas('success');

    $approvedLog = AuditLog::where('action', AuditLog::APPLICATION_APPROVED)->sole();
    $rejectedLog = AuditLog::where('action', AuditLog::APPLICATION_REJECTED)->sole();

    expect($approvedLog->target_user_id)->toBe($approved->id)
        ->and($approvedLog->actor_id)->toBe($edp->id)
        ->and($approvedLog->new_value)->toBe(StudentApplication::APPROVED)
        ->and($rejectedLog->target_user_id)->toBe($rejected->id)
        ->and($rejectedLog->reason)->toBe('We could not confirm this faculty member.');
});

test('the audit log never stores passwords or file paths', function () {
    $edp = pathwiseUser('super_admin', ['password' => 'Edp-Password-123']);
    $admin = pathwiseUser('admin');

    $this->actingAs($edp)->put(route('users.update-role', $admin), [
        'role' => 'super_admin',
        'reason' => 'Backup EDP officer',
        'current_password' => 'Edp-Password-123',
    ]);
    $this->actingAs($edp)->post(route('users.store'), [
        'name' => 'New Admin',
        'email' => 'new.admin@uclm.edu.ph',
        'password' => 'Brand-New-Pass-456',
        'password_confirmation' => 'Brand-New-Pass-456',
        'role' => 'admin',
    ]);

    $everything = json_encode(AuditLog::all()->map->getAttributes());

    expect($everything)->not->toContain('Edp-Password-123')
        ->and($everything)->not->toContain('Brand-New-Pass-456')
        ->and($everything)->not->toContain('study-loads/');
});

/*
|--------------------------------------------------------------------------
| Audit Log page (read-only)
|--------------------------------------------------------------------------
*/

test('the Audit Log lists entries newest first and filters by action, user and date', function () {
    $edpOne = pathwiseUser('super_admin', ['name' => 'EDP One']);
    $edpTwo = pathwiseUser('super_admin', ['name' => 'EDP Two']);
    $maria = pathwiseUser('admin', ['name' => 'Maria Reyes', 'email_verified_at' => null]);
    $jose = pathwiseUser('student', ['name' => 'Jose Cruz']);

    $this->actingAs($edpOne)->patch(route('users.verify-email', $maria));
    $this->travel(1)->minutes();
    $this->actingAs($edpTwo)->patch(route('users.update-status', $jose), ['is_active' => 0]);

    $page = $this->actingAs($edpOne)->get(route('super-admin.audit-log.index'))->assertOk();
    $page->assertSeeInOrder(['Jose Cruz', 'Maria Reyes']);

    $this->actingAs($edpOne)
        ->get(route('super-admin.audit-log.index', ['action' => AuditLog::DEACTIVATED]))
        ->assertSee('Jose Cruz')
        ->assertDontSee('Maria Reyes');

    // "User" matches the person who acted or the account affected.
    $this->actingAs($edpOne)
        ->get(route('super-admin.audit-log.index', ['user' => 'EDP One']))
        ->assertSee('Maria Reyes')
        ->assertDontSee('Jose Cruz');

    $this->actingAs($edpOne)
        ->get(route('super-admin.audit-log.index', ['user' => 'jose']))
        ->assertSee('Jose Cruz')
        ->assertDontSee('Maria Reyes');

    $this->actingAs($edpOne)
        ->get(route('super-admin.audit-log.index', ['from' => now('Asia/Manila')->addDay()->format('Y-m-d')]))
        ->assertSee('No audit entries match these filters.');
});

test('the Audit Log is paginated', function () {
    $edp = pathwiseUser('super_admin');

    foreach (range(1, 30) as $i) {
        AuditLog::create(['actor_id' => $edp->id, 'action' => AuditLog::ACTIVATED, 'new_value' => 'active']);
    }

    $this->actingAs($edp)
        ->get(route('super-admin.audit-log.index'))
        ->assertOk()
        ->assertViewHas('logs', fn ($logs) => $logs->count() === 25 && $logs->total() === 30);
});

test('audit entries cannot be edited or deleted', function () {
    $log = AuditLog::create(['action' => AuditLog::ROLE_CHANGED, 'old_value' => 'student', 'new_value' => 'admin']);

    expect(fn () => $log->update(['new_value' => 'super_admin']))->toThrow(LogicException::class)
        ->and(fn () => $log->delete())->toThrow(LogicException::class)
        ->and(AuditLog::sole()->new_value)->toBe('admin');

    $routes = collect(app('router')->getRoutes()->getRoutes())
        ->filter(fn ($route) => str_contains($route->uri(), 'audit-log'));

    expect($routes)->toHaveCount(1)
        ->and($routes->first()->methods())->toBe(['GET', 'HEAD']);
});
