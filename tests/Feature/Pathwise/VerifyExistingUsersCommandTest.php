<?php

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

/* Existing non-teacher accounts may be backfilled; teachers require EDP review. */

test('existing unverified users become verified and nothing else changes', function () {
    $student = pathwiseUser('student', ['email_verified_at' => null, 'email' => 'old-student@example.com']);
    $teacher = pathwiseUser('teacher', ['email_verified_at' => null]);
    $before = DB::table('users')->where('id', $student->id)->first();

    $this->travel(1)->minutes();

    $this->artisan('users:verify-existing')
        ->expectsOutputToContain('Accounts marked as verified: 1')
        ->assertSuccessful();

    $after = DB::table('users')->where('id', $student->id)->first();

    expect($student->fresh()->hasVerifiedEmail())->toBeTrue()
        ->and($teacher->fresh()->hasVerifiedEmail())->toBeFalse()
        ->and($student->fresh()->email_verified_at->equalTo(now()))->toBeTrue()
        ->and($after->password)->toBe($before->password)
        ->and($after->updated_at)->toBe($before->updated_at)
        ->and(collect((array) $after)->except('email_verified_at')->all())
        ->toBe(collect((array) $before)->except('email_verified_at')->all())
        ->and($student->fresh()->hasRole('student'))->toBeTrue()
        ->and($teacher->fresh()->hasRole('teacher'))->toBeTrue()
        ->and(Hash::check('password', $student->fresh()->password))->toBeTrue();
});

test('already verified users keep their original date', function () {
    $verifiedAt = now()->subYear();
    $user = pathwiseUser('student', ['email_verified_at' => $verifiedAt]);

    $this->travel(1)->minutes();

    $this->artisan('users:verify-existing')
        ->expectsOutputToContain('Accounts marked as verified: 0')
        ->assertSuccessful();

    expect($user->fresh()->email_verified_at->equalTo($verifiedAt))->toBeTrue();
});

test('--dry-run lists the accounts and changes nothing', function () {
    $user = pathwiseUser('student', ['email_verified_at' => null, 'email' => 'waiting@example.com']);
    pathwiseUser('student', ['email' => 'done@example.com']);

    $this->travel(1)->minutes();

    $this->artisan('users:verify-existing --dry-run')
        ->expectsOutputToContain('Accounts that would be marked as verified: 1')
        ->expectsOutputToContain('waiting@example.com')
        ->doesntExpectOutputToContain('done@example.com')
        ->assertSuccessful();

    expect($user->fresh()->email_verified_at)->toBeNull();
});

test('running it twice is safe', function () {
    $user = pathwiseUser('student', ['email_verified_at' => null]);

    $this->travel(1)->minutes();
    $this->artisan('users:verify-existing')->assertSuccessful();
    $firstDate = $user->fresh()->email_verified_at;

    $this->travel(1)->days();
    $this->artisan('users:verify-existing')
        ->expectsOutputToContain('Accounts marked as verified: 0')
        ->assertSuccessful();

    expect($user->fresh()->email_verified_at->equalTo($firstDate))->toBeTrue();
});

test('an account without a created_at date counts as existing', function () {
    $user = pathwiseUser('student', ['email_verified_at' => null]);
    DB::table('users')->where('id', $user->id)->update(['created_at' => null]);

    $this->artisan('users:verify-existing')->assertSuccessful();

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});

test('a user who registers after the command still has to verify their email', function () {
    $this->seed(RoleSeeder::class);
    Notification::fake();
    fakeFileDisks();

    $existing = pathwiseUser('student', ['email_verified_at' => null]);

    $this->travel(1)->minutes();
    $this->artisan('users:verify-existing')->assertSuccessful();

    $this->travel(1)->minutes();

    $this->post(route('register.store'), [
        'first_name' => 'New',
        'last_name' => 'Student',
        'email' => 'new-student@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'student',
        'study_load' => Illuminate\Http\UploadedFile::fake()->create('study-load.pdf', 200, 'application/pdf'),
    ])->assertSessionHasNoErrors();

    $newUser = User::where('email', 'new-student@example.com')->sole();

    expect($existing->fresh()->hasVerifiedEmail())->toBeTrue()
        ->and($newUser->hasVerifiedEmail())->toBeFalse();

    // A new student first waits for the EDP's approval (Student Applications).
    $this->get(route('student.dashboard'))->assertRedirect(route('student-application.status'));
});

test('an account created when the command starts is not changed', function () {
    // The clock is stopped, so this user is created at the command's start time.
    $user = pathwiseUser('student', ['email_verified_at' => null]);

    $this->artisan('users:verify-existing')
        ->expectsOutputToContain('Accounts marked as verified: 0')
        ->assertSuccessful();

    expect($user->fresh()->email_verified_at)->toBeNull();
});
