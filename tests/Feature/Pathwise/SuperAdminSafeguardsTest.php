<?php

use App\Http\Middleware\EnsureUserIsActive;
use App\Models\AuditLog;
use App\Models\StudentApplication;
use App\Models\User;
use Illuminate\Support\Facades\Notification;

/*
| Phase 1: Super Admin safeguards. Only the Super Admin changes roles,
| verification and account status (enforced on the server); nobody changes
| their own; promotions to Super Admin need the acting Super Admin's
| password; the last active Super Admin is protected; every sensitive action
| is written to a read-only audit log without passwords or file contents.
*/

beforeEach(function () {
    $this->seed(\Database\Seeders\RoleSeeder::class);
    fakeFileDisks();
});

function roleOf(User $user): array
{
    return $user->fresh()->roles()->pluck('name')->all();
}

/*
|--------------------------------------------------------------------------
| Only the Super Admin, never on their own account
|--------------------------------------------------------------------------
*/

test('only the Super Admin can change roles, verification, status or open the Audit Log', function (string $role) {
    $actor = pathwiseUser($role);
    $target = pathwiseUser('student', ['email_verified_at' => null]);

    $this->actingAs($actor)->put(route('users.update-role', $target), ['role' => 'admin', 'reason' => 'Promotion test'])->assertForbidden();
    $this->actingAs($actor)->patch(route('users.update-status', $target), ['is_active' => 0])->assertForbidden();
    $this->actingAs($actor)->patch(route('users.verify-email', $target), ['reason' => 'Verification test'])->assertForbidden();
    $this->actingAs($actor)->get(route('super-admin.audit-log.index'))->assertForbidden();

    // Not even on their own account.
    $this->actingAs($actor)->put(route('users.update-role', $actor), ['role' => 'super_admin'])->assertForbidden();
    $this->actingAs($actor)->patch(route('users.verify-email', $actor), ['reason' => 'Self verification'])->assertForbidden();

    expect(roleOf($target))->toBe(['student'])
        ->and(roleOf($actor))->toBe([$role])
        ->and($target->fresh()->hasVerifiedEmail())->toBeFalse()
        ->and($target->fresh()->is_active)->not->toBeFalse()
        ->and(AuditLog::count())->toBe(0);
})->with(['admin', 'teacher', 'student']);

test('an unverified Super Admin cannot reach the verification action at all', function () {
    $superAdmin = pathwiseUser('super_admin', ['email_verified_at' => null]);

    $this->actingAs($superAdmin)
        ->patch(route('users.verify-email', $superAdmin), ['reason' => 'Self verification'])
        ->assertRedirect(route('verification.notice'));

    expect($superAdmin->fresh()->hasVerifiedEmail())->toBeFalse()
        ->and(AuditLog::count())->toBe(0);
});

test('a Super Admin cannot change their own role, verification or status', function () {
    $superAdmin = pathwiseUser('super_admin');
    pathwiseUser('super_admin');

    $this->actingAs($superAdmin)
        ->put(route('users.update-role', $superAdmin), ['role' => 'admin', 'reason' => 'Stepping down'])
        ->assertSessionHas('error', 'You cannot change your own role. Ask another Super Admin.');

    $this->actingAs($superAdmin)
        ->patch(route('users.verify-email', $superAdmin), ['reason' => 'Self verification'])
        ->assertSessionHas('error', 'You cannot mark your own email as verified.');

    $this->actingAs($superAdmin)
        ->patch(route('users.update-status', $superAdmin), ['is_active' => 0])
        ->assertSessionHas('error');

    expect(roleOf($superAdmin))->toBe(['super_admin'])
        ->and($superAdmin->fresh()->is_active)->not->toBeFalse()
        ->and(AuditLog::count())->toBe(0);

    // The page shows no role form for the signed-in Super Admin's own row.
    $this->actingAs($superAdmin)
        ->get(route('users.index'))
        ->assertOk()
        ->assertSee('data-test="own-role-locked"', false)
        ->assertDontSee('action="'.route('users.update-role', $superAdmin).'"', false);
});

/*
|--------------------------------------------------------------------------
| Changing a role to Teacher
|--------------------------------------------------------------------------
*/

test('changing a role to Teacher needs a short reason', function (?string $reason) {
    $student = pathwiseUser('student');

    $this->actingAs(pathwiseUser('super_admin'))
        ->put(route('users.update-role', $student), array_filter(['role' => 'teacher', 'reason' => $reason]))
        ->assertSessionHasErrors('reason');

    expect(roleOf($student))->toBe(['student'])
        ->and(AuditLog::count())->toBe(0);
})->with([
    'no reason' => [null],
    'too short' => ['ok'],
]);

test('changing a role to Teacher is logged with the reason', function () {
    $superAdmin = pathwiseUser('super_admin');
    $student = pathwiseUser('student');

    $this->actingAs($superAdmin)
        ->put(route('users.update-role', $student), ['role' => 'teacher', 'reason' => 'Hired as a part-time instructor'])
        ->assertSessionHas('success');

    $log = AuditLog::sole();

    expect(roleOf($student))->toBe(['teacher'])
        ->and($log->action)->toBe(AuditLog::ROLE_CHANGED)
        ->and($log->actor_id)->toBe($superAdmin->id)
        ->and($log->target_user_id)->toBe($student->id)
        ->and($log->old_value)->toBe('student')
        ->and($log->new_value)->toBe('teacher')
        ->and($log->reason)->toBe('Hired as a part-time instructor')
        ->and($log->ip_address)->toBe('127.0.0.1')
        ->and($log->created_at)->not->toBeNull();
});

test('the Super Admin\'s change to Teacher approves a pending or rejected application', function (string $status) {
    $superAdmin = pathwiseUser('super_admin');
    $teacher = pathwiseUser('teacher', ['email_verified_at' => now()]);
    $application = StudentApplication::submitForTeacher($teacher, 1);
    $application->forceFill(['status' => $status])->save();

    // A pending teacher who is changed to Student first, then back to Teacher.
    $teacher->roles()->sync([App\Models\Role::where('name', 'student')->value('id')]);

    $this->actingAs($superAdmin)
        ->put(route('users.update-role', $teacher), ['role' => 'teacher', 'reason' => 'Confirmed with the dean'])
        ->assertSessionHas('success');

    expect($application->fresh()->status)->toBe(StudentApplication::APPROVED)
        ->and($application->fresh()->reviewed_by)->toBe($superAdmin->id)
        ->and($teacher->fresh()->awaitingApproval())->toBeFalse()
        ->and(AuditLog::where('action', AuditLog::APPLICATION_APPROVED)->where('target_user_id', $teacher->id)->exists())->toBeTrue();

    $this->actingAs($teacher->fresh())->get(route('teacher.dashboard'))->assertOk();
})->with([StudentApplication::PENDING, StudentApplication::REJECTED]);

/*
|--------------------------------------------------------------------------
| Promoting to Super Admin
|--------------------------------------------------------------------------
*/

test('promoting to Super Admin needs the acting Super Admin\'s correct password', function (?string $password, string $error) {
    $admin = pathwiseUser('admin');

    $this->actingAs(pathwiseUser('super_admin'))
        ->put(route('users.update-role', $admin), array_filter(['role' => 'super_admin', 'current_password' => $password]))
        ->assertSessionHasErrors(['current_password' => $error]);

    expect(roleOf($admin))->toBe(['admin'])
        ->and(AuditLog::count())->toBe(0);
})->with([
    'no password' => [null, 'Please re-enter your password to make this user a Super Admin.'],
    'wrong password' => ['not-my-password', 'The password you entered is incorrect.'],
]);

test('promoting to Super Admin with the correct password works and the password is never logged', function () {
    $superAdmin = pathwiseUser('super_admin', ['password' => 'Correct-Horse-91']);
    $admin = pathwiseUser('admin');

    $this->actingAs($superAdmin)
        ->put(route('users.update-role', $admin), ['role' => 'super_admin', 'current_password' => 'Correct-Horse-91'])
        ->assertSessionHas('success');

    expect(roleOf($admin))->toBe(['super_admin']);

    $log = AuditLog::sole();
    $stored = json_encode($log->getAttributes());

    expect($log->new_value)->toBe('super_admin')
        ->and($stored)->not->toContain('Correct-Horse-91')
        ->and($stored)->not->toContain('current_password');
});

/*
|--------------------------------------------------------------------------
| The last active Super Admin
|--------------------------------------------------------------------------
*/

test('the last active Super Admin cannot be demoted or deactivated', function () {
    // The acting Super Admin was deactivated a moment ago by someone else
    // (simulated by skipping the "signed out when deactivated" middleware),
    // so the target is the last active Super Admin.
    $this->withoutMiddleware(EnsureUserIsActive::class);

    $actor = pathwiseUser('super_admin', ['is_active' => false]);
    $lastActive = pathwiseUser('super_admin');

    $this->actingAs($actor)
        ->put(route('users.update-role', $lastActive), ['role' => 'admin'])
        ->assertSessionHas('error', 'You cannot change the role of the last active Super Admin.');

    $this->actingAs($actor)
        ->patch(route('users.update-status', $lastActive), ['is_active' => 0])
        ->assertSessionHas('error', 'You cannot deactivate the last active Super Admin.');

    expect(roleOf($lastActive))->toBe(['super_admin'])
        ->and($lastActive->fresh()->is_active)->not->toBeFalse()
        ->and(AuditLog::count())->toBe(0);
});

test('another Super Admin can be demoted or deactivated while an active one remains', function () {
    $actor = pathwiseUser('super_admin');
    $other = pathwiseUser('super_admin');
    $third = pathwiseUser('super_admin');

    $this->actingAs($actor)
        ->put(route('users.update-role', $other), ['role' => 'admin'])
        ->assertSessionHas('success');

    $this->actingAs($actor)
        ->patch(route('users.update-status', $third), ['is_active' => 0, 'reason' => 'Left the EDP office'])
        ->assertSessionHas('success');

    expect(roleOf($other))->toBe(['admin'])
        ->and($third->fresh()->is_active)->toBeFalse()
        ->and(AuditLog::where('action', AuditLog::DEACTIVATED)->value('reason'))->toBe('Left the EDP office');
});

/*
|--------------------------------------------------------------------------
| Mark verified
|--------------------------------------------------------------------------
*/

test('mark verified needs a short reason and is logged', function () {
    $superAdmin = pathwiseUser('super_admin');
    $teacher = pathwiseUser('teacher', ['email_verified_at' => null]);

    $this->actingAs($superAdmin)
        ->patch(route('users.verify-email', $teacher))
        ->assertSessionHasErrors('reason');

    $this->actingAs($superAdmin)
        ->patch(route('users.verify-email', $teacher), ['reason' => 'ok'])
        ->assertSessionHasErrors('reason');

    expect($teacher->fresh()->hasVerifiedEmail())->toBeFalse();

    $this->actingAs($superAdmin)
        ->patch(route('users.verify-email', $teacher), ['reason' => 'Confirmed in person at the EDP office'])
        ->assertSessionHas('success');

    $log = AuditLog::sole();

    expect($teacher->fresh()->hasVerifiedEmail())->toBeTrue()
        ->and($log->action)->toBe(AuditLog::EMAIL_VERIFIED)
        ->and($log->reason)->toBe('Confirmed in person at the EDP office');
});

test('mark verified never approves a pending student or teacher application', function (string $role) {
    $user = pathwiseUser($role, ['email_verified_at' => null]);
    $application = $role === 'teacher'
        ? StudentApplication::submitForTeacher($user, 1)
        : StudentApplication::create([
            'user_id' => $user->id, 'submission_no' => 1, 'study_load_path' => 'study-loads/x.pdf',
            'study_load_original_name' => 'x.pdf', 'study_load_mime' => 'application/pdf', 'study_load_size' => 1,
            'status' => StudentApplication::PENDING, 'submitted_at' => now(),
        ]);

    $this->actingAs(pathwiseUser('super_admin'))
        ->patch(route('users.verify-email', $user), ['reason' => 'Trying to skip the review'])
        ->assertSessionHas('error');

    expect($application->fresh()->status)->toBe(StudentApplication::PENDING)
        ->and($user->fresh()->hasVerifiedEmail())->toBeFalse()
        ->and(AuditLog::count())->toBe(0);
})->with(['student', 'teacher']);

/*
|--------------------------------------------------------------------------
| Everything sensitive is logged
|--------------------------------------------------------------------------
*/

test('activation, deactivation, account creation and email changes are logged', function () {
    $superAdmin = pathwiseUser('super_admin');
    $teacher = pathwiseUser('teacher', ['email' => 'old@uclm.edu.ph']);

    $this->actingAs($superAdmin)->patch(route('users.update-status', $teacher), ['is_active' => 0]);
    $this->actingAs($superAdmin)->patch(route('users.update-status', $teacher), ['is_active' => 1]);
    $this->actingAs($superAdmin)->put(route('users.update', $teacher), ['name' => $teacher->name, 'email' => 'new@uclm.edu.ph']);
    $this->actingAs($superAdmin)->post(route('users.store'), [
        'name' => 'New Admin',
        'email' => 'new.admin@uclm.edu.ph',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
        'role' => 'admin',
    ]);

    expect(AuditLog::orderBy('id')->pluck('action')->all())->toBe([
        AuditLog::DEACTIVATED,
        AuditLog::ACTIVATED,
        AuditLog::EMAIL_CHANGED,
        AuditLog::ACCOUNT_CREATED,
    ]);

    $emailLog = AuditLog::where('action', AuditLog::EMAIL_CHANGED)->sole();
    expect($emailLog->old_value)->toBe('old@uclm.edu.ph')
        ->and($emailLog->new_value)->toBe('new@uclm.edu.ph')
        ->and(json_encode(AuditLog::all()->map->getAttributes()))->not->toContain('Password123!');
});

test('application approval and rejection are logged', function () {
    Notification::fake();
    $superAdmin = pathwiseUser('super_admin');
    $first = pathwiseUser('teacher', ['email_verified_at' => null]);
    $second = pathwiseUser('teacher', ['email_verified_at' => null]);
    $approve = StudentApplication::submitForTeacher($first, 1);
    $reject = StudentApplication::submitForTeacher($second, 1);

    $this->actingAs($superAdmin)->post(route('super-admin.student-applications.approve', $approve));
    $this->actingAs($superAdmin)->post(route('super-admin.student-applications.reject', $reject), [
        'rejection_reason' => 'The ID photo is unreadable.',
    ]);

    $approved = AuditLog::where('action', AuditLog::APPLICATION_APPROVED)->sole();
    $rejected = AuditLog::where('action', AuditLog::APPLICATION_REJECTED)->sole();

    expect($approved->target_user_id)->toBe($first->id)
        ->and($approved->new_value)->toBe(StudentApplication::APPROVED)
        ->and($rejected->target_user_id)->toBe($second->id)
        ->and($rejected->reason)->toBe('The ID photo is unreadable.');
});

/*
|--------------------------------------------------------------------------
| Audit Log page (read-only)
|--------------------------------------------------------------------------
*/

test('the Audit Log page lists entries and filters by action, actor, user and date', function () {
    $edpOne = pathwiseUser('super_admin', ['name' => 'EDP One']);
    $edpTwo = pathwiseUser('super_admin', ['name' => 'EDP Two']);
    $maria = pathwiseUser('student', ['name' => 'Maria Reyes', 'email_verified_at' => null]);
    $jose = pathwiseUser('student', ['name' => 'Jose Cruz']);

    $this->actingAs($edpOne)->patch(route('users.verify-email', $maria), ['reason' => 'Confirmed at the window']);
    $this->actingAs($edpTwo)->patch(route('users.update-status', $jose), ['is_active' => 0]);

    $this->actingAs($edpOne)
        ->get(route('super-admin.audit-log.index'))
        ->assertOk()
        ->assertSee('Email marked verified')
        ->assertSee('Account deactivated')
        ->assertSee('Confirmed at the window');

    $this->actingAs($edpOne)
        ->get(route('super-admin.audit-log.index', ['action' => AuditLog::DEACTIVATED]))
        ->assertSee('Jose Cruz')
        ->assertDontSee('Maria Reyes');

    $this->actingAs($edpOne)
        ->get(route('super-admin.audit-log.index', ['actor' => $edpOne->id]))
        ->assertSee('Maria Reyes')
        ->assertDontSee('Jose Cruz');

    $this->actingAs($edpOne)
        ->get(route('super-admin.audit-log.index', ['target' => 'jose']))
        ->assertSee('Jose Cruz')
        ->assertDontSee('Maria Reyes');

    $this->actingAs($edpOne)
        ->get(route('super-admin.audit-log.index', ['from' => now('Asia/Manila')->addDay()->format('Y-m-d')]))
        ->assertSee('No audit entries match these filters.');
});

test('audit entries cannot be edited or deleted', function () {
    $log = AuditLog::create(['action' => AuditLog::ROLE_CHANGED, 'old_value' => 'student', 'new_value' => 'teacher']);

    expect(fn () => $log->update(['new_value' => 'super_admin']))->toThrow(LogicException::class)
        ->and(fn () => $log->delete())->toThrow(LogicException::class)
        ->and(AuditLog::sole()->new_value)->toBe('teacher');

    // The only Audit Log route is a GET.
    $routes = collect(app('router')->getRoutes()->getRoutes())
        ->filter(fn ($route) => str_contains($route->uri(), 'audit-log'));

    expect($routes)->toHaveCount(1)
        ->and($routes->first()->methods())->toBe(['GET', 'HEAD']);
});

test('the Super Admin sidebar links to the Audit Log', function () {
    $this->actingAs(pathwiseUser('super_admin'))
        ->get(route('users.index'))
        ->assertOk()
        ->assertSee('data-test="nav-audit-log"', false)
        ->assertSee('data-test="role-dialog"', false)
        ->assertSee('data-test="verify-dialog"', false);
});
