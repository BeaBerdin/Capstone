<?php

use App\Models\Assignment;
use App\Models\Certificate;
use App\Models\Enrollment;
use App\Models\Submission;
use App\Models\Transaction;
use App\Models\User;
use Livewire\Livewire;

/*
| Rule: PATHWISE accounts are deactivated, not deleted, so enrollments,
| payments, submissions and certificates are kept (Manuscript FR (n):
| the Super Admin activates and deactivates accounts).
*/

function studentWithRecords(): array
{
    $teacher = pathwiseUser('teacher');
    $student = pathwiseUser('student');
    $course = pathwiseCourse($teacher, ['status' => 'published', 'price' => 499]);

    Enrollment::create([
        'student_id' => $student->id,
        'course_id' => $course->id,
        'status' => 'completed',
        'enrolled_at' => now(),
        'progress_percentage' => 100,
    ]);

    Transaction::create([
        'student_id' => $student->id,
        'course_id' => $course->id,
        'transaction_no' => 'TXN-KEEP-1',
        'amount' => 499,
        'payment_method' => 'PayMongo',
        'status' => 'approved',
    ]);

    Certificate::create([
        'student_id' => $student->id,
        'course_id' => $course->id,
        'certificate_number' => 'PATH-2026-00099',
        'issued_date' => now()->toDateString(),
        'status' => 'issued',
    ]);

    $assignment = Assignment::create([
        'course_id' => $course->id,
        'title' => 'Essay',
        'submission_type' => 'essay',
        'max_score' => 100,
        'is_published' => true,
    ]);

    Submission::create([
        'assignment_id' => $assignment->id,
        'student_id' => $student->id,
        'answer_text' => 'My essay',
        'status' => 'submitted',
        'submitted_at' => now(),
    ]);

    return [$teacher, $student];
}

function recordCounts(): array
{
    return [
        'enrollments' => Enrollment::count(),
        'transactions' => Transaction::count(),
        'certificates' => Certificate::count(),
        'submissions' => Submission::count(),
    ];
}

test('deactivating your own account keeps all of your records', function () {
    [, $student] = studentWithRecords();
    $before = recordCounts();

    $this->actingAs($student);

    Livewire::test('pages::settings.delete-user-modal')
        ->set('password', 'password')
        ->call('deactivateAccount')
        ->assertHasNoErrors();

    expect(User::find($student->id))->not->toBeNull()
        ->and(User::find($student->id)->is_active)->toBeFalse()
        ->and(recordCounts())->toBe($before)
        ->and($before)->toBe(['enrollments' => 1, 'transactions' => 1, 'certificates' => 1, 'submissions' => 1]);
});

test('a teacher deactivating their account keeps every student\'s records', function () {
    [$teacher] = studentWithRecords();
    $before = recordCounts();

    $this->actingAs($teacher);

    Livewire::test('pages::settings.delete-user-modal')
        ->set('password', 'password')
        ->call('deactivateAccount')
        ->assertHasNoErrors();

    expect(User::find($teacher->id)->is_active)->toBeFalse()
        ->and(recordCounts())->toBe($before);
});

test('a deactivated account cannot sign in again', function () {
    $user = User::factory()->create(['is_active' => false]);

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->get(route('dashboard'))->assertRedirect(route('login'));
    $this->assertGuest();
});

test('a Super Admin cannot deactivate their own account from settings', function () {
    $superAdmin = pathwiseUser('super_admin');

    $this->actingAs($superAdmin);

    Livewire::test('pages::settings.delete-user-modal')
        ->set('password', 'password')
        ->call('deactivateAccount')
        ->assertHasErrors(['password']);

    expect($superAdmin->fresh()->is_active)->not->toBeFalse();
});

test('the settings page offers deactivation, not deletion', function () {
    $this->actingAs(pathwiseUser('student'))
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertSee('Deactivate account')
        ->assertDontSee('Delete account');
});

test('the settings page tells every user to contact the EDP office for permanent deletion', function (string $role) {
    $this->actingAs(pathwiseUser($role))
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertSee('To request permanent deletion of your account, please contact the EDP office.')
        ->assertDontSee('confirm-delete-user-button', false)
        ->assertDontSee('wire:submit="deleteUser"', false);
})->with(['student', 'teacher', 'admin', 'super_admin']);

test('the old self-deletion action no longer exists', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    expect(fn () => Livewire::test('pages::settings.delete-user-modal')
        ->set('password', 'password')
        ->call('deleteUser'))
        ->toThrow(Exception::class);

    expect(User::find($user->id))->not->toBeNull()
        ->and(User::find($user->id)->is_active)->not->toBeFalse();
});

test('the Super Admin can reactivate a deactivated account in User Management', function () {
    $superAdmin = pathwiseUser('super_admin');
    $student = pathwiseUser('student', ['is_active' => false]);

    $this->actingAs($superAdmin)
        ->patch(route('users.update-status', $student), ['is_active' => 1])
        ->assertSessionHasNoErrors();

    expect($student->fresh()->is_active)->toBeTrue();
});

test('deleting a user in code is refused', function () {
    [, $student] = studentWithRecords();
    $before = recordCounts();

    expect(fn () => $student->delete())
        ->toThrow(LogicException::class, 'deactivated, not deleted');

    expect(User::find($student->id))->not->toBeNull()
        ->and(recordCounts())->toBe($before);
});
