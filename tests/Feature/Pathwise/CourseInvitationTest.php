<?php

use App\Models\CourseInvitation;
use App\Models\Department;
use App\Models\Enrollment;
use App\Notifications\PathwiseNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

/*
| Course invitations (Manuscript Scope: teachers invite students through a
| unique link). Invitations are for published courses only, the expiry is
| validated instead of crashing, and teachers only see relevant students.
*/

function invitationFor(App\Models\Course $course, App\Models\User $teacher): CourseInvitation
{
    return CourseInvitation::create([
        'course_id' => $course->id,
        'created_by' => $teacher->id,
        'code' => strtoupper(Str::random(12)),
    ]);
}

test('an invitation expiry in Philippine time is stored in UTC, with or without seconds', function (string $input) {
    $this->travelTo(Carbon::parse('2026-10-16 08:00:00', 'UTC'));

    $teacher = pathwiseUser('teacher');
    $course = pathwiseCourse($teacher, ['status' => 'published']);

    $this->actingAs($teacher)
        ->post(route('course-invitations.store'), [
            'course_id' => $course->id,
            'expires_at' => $input,
        ])
        ->assertRedirect(route('course-invitations.index'))
        ->assertSessionHasNoErrors();

    expect(CourseInvitation::sole()->expires_at->utc()->format('Y-m-d H:i'))->toBe('2026-10-20 02:00');
})->with([
    'without seconds' => '2026-10-20T10:00',
    'with seconds' => '2026-10-20T10:00:00',
]);

test('an invitation expiry in another format is a validation error, not a server error', function () {
    $teacher = pathwiseUser('teacher');
    $course = pathwiseCourse($teacher, ['status' => 'published']);

    $this->actingAs($teacher)
        ->post(route('course-invitations.store'), [
            'course_id' => $course->id,
            'expires_at' => '2026-12-20',
        ])
        ->assertSessionHasErrors('expires_at');

    expect(CourseInvitation::count())->toBe(0);
});

test('an invitation expiry in the past is rejected', function () {
    $teacher = pathwiseUser('teacher');
    $course = pathwiseCourse($teacher, ['status' => 'published']);

    $this->actingAs($teacher)
        ->post(route('course-invitations.store'), [
            'course_id' => $course->id,
            'expires_at' => now('Asia/Manila')->subDay()->format('Y-m-d\TH:i'),
        ])
        ->assertSessionHasErrors('expires_at');

    expect(CourseInvitation::count())->toBe(0);
});

test('invitations can only be created for the teacher\'s published courses', function () {
    $teacher = pathwiseUser('teacher');
    $draft = pathwiseCourse($teacher, ['status' => 'draft', 'title' => 'Draft Course Title']);
    $published = pathwiseCourse($teacher, ['status' => 'published', 'title' => 'Published Course Title']);

    $this->actingAs($teacher)
        ->get(route('course-invitations.index'))
        ->assertOk()
        ->assertSee('Published Course Title')
        ->assertDontSee('Draft Course Title');

    $this->actingAs($teacher)
        ->post(route('course-invitations.store'), ['course_id' => $draft->id])
        ->assertSessionHasErrors('course_id');

    expect(CourseInvitation::count())->toBe(0);
});

test('an invitation to a course that was unpublished cannot be accepted', function () {
    $teacher = pathwiseUser('teacher');
    $student = pathwiseUser('student');
    $course = pathwiseCourse($teacher, ['status' => 'published', 'price' => 0]);
    $invitation = invitationFor($course, $teacher);

    $course->update(['status' => 'draft']);

    $this->actingAs($student)
        ->post(route('course-invitations.accept', $invitation->code))
        ->assertRedirect(route('course-invitations.show', $invitation->code))
        ->assertSessionHas('error');

    expect(Enrollment::where('student_id', $student->id)->exists())->toBeFalse()
        ->and($invitation->fresh()->accepted_at)->toBeNull();
});

test('a logged-out visitor opening an invitation link logs in first, then sees the invitation', function () {
    $teacher = pathwiseUser('teacher');
    $student = pathwiseUser('student');
    $course = pathwiseCourse($teacher, ['status' => 'published', 'price' => 0]);
    $invitation = invitationFor($course, $teacher);
    $link = route('course-invitations.show', $invitation->code);

    $this->get($link)->assertRedirect(route('login'));

    // Laravel remembers the link, so logging in returns to the invitation.
    expect(session('url.intended'))->toBe($link);

    $this->actingAs($student)->get($link)->assertOk();
});

function enrollIn(App\Models\User $student, App\Models\Course $course): void
{
    Enrollment::create([
        'student_id' => $student->id,
        'course_id' => $course->id,
        'status' => 'active',
        'enrolled_at' => now(),
        'progress_percentage' => 0,
    ]);
}

test('the invitations page does not list students until the teacher searches', function () {
    $teacher = pathwiseUser('teacher');
    invitationFor(pathwiseCourse($teacher, ['status' => 'published']), $teacher);
    $student = pathwiseUser('student', ['name' => 'Listed Nowhere Student']);

    $this->actingAs($teacher)
        ->get(route('course-invitations.index'))
        ->assertOk()
        ->assertDontSee('Listed Nowhere Student')
        ->assertDontSee($student->email);
});

test('a student search needs at least 3 characters', function () {
    $teacher = pathwiseUser('teacher');
    $invitation = invitationFor(pathwiseCourse($teacher, ['status' => 'published']), $teacher);
    pathwiseUser('student', ['name' => 'Ana Reyes']);

    $this->actingAs($teacher)
        ->get(route('course-invitations.index', ['invitation' => $invitation->id, 'q' => 'An']))
        ->assertOk()
        ->assertSee('Type at least 3 characters')
        ->assertDontSee('Ana Reyes');
});

test('a teacher finds students by name or email, from any department', function () {
    $teacher = pathwiseUser('teacher', [
        'department_id' => Department::create(['name' => 'College of Computing'])->id,
    ]);
    $invitation = invitationFor(pathwiseCourse($teacher, ['status' => 'published']), $teacher);

    pathwiseUser('student', [
        'name' => 'Maria Santos',
        'email' => 'msantos@example.com',
        'department_id' => Department::create(['name' => 'College of Business'])->id,
    ]);

    $this->actingAs($teacher)
        ->get(route('course-invitations.index', ['invitation' => $invitation->id, 'q' => 'Santos']))
        ->assertOk()
        ->assertSee('Maria Santos');

    $this->actingAs($teacher)
        ->get(route('course-invitations.index', ['invitation' => $invitation->id, 'q' => 'msantos@']))
        ->assertOk()
        ->assertSee('Maria Santos');
});

test('the student search leaves out students already enrolled in the course and non-students', function () {
    $teacher = pathwiseUser('teacher');
    $course = pathwiseCourse($teacher, ['status' => 'published']);
    $invitation = invitationFor($course, $teacher);

    enrollIn(pathwiseUser('student', ['name' => 'Searchname Enrolled']), $course);
    enrollIn(pathwiseUser('student', ['name' => 'Searchname Other Course']), pathwiseCourse($teacher, ['status' => 'published']));
    pathwiseUser('teacher', ['name' => 'Searchname Teacher']);

    $this->actingAs($teacher)
        ->get(route('course-invitations.index', ['invitation' => $invitation->id, 'q' => 'Searchname']))
        ->assertOk()
        ->assertSee('Searchname Other Course')
        ->assertDontSee('Searchname Enrolled')
        ->assertDontSee('Searchname Teacher');
});

test('the student search returns at most 10 results', function () {
    $teacher = pathwiseUser('teacher');
    $invitation = invitationFor(pathwiseCourse($teacher, ['status' => 'published']), $teacher);

    foreach (range(1, 12) as $number) {
        pathwiseUser('student', ['name' => sprintf('Batchmate %02d', $number)]);
    }

    $response = $this->actingAs($teacher)
        ->get(route('course-invitations.index', ['invitation' => $invitation->id, 'q' => 'Batchmate']))
        ->assertOk();

    expect(substr_count($response->getContent(), 'Batchmate '))->toBe(10);
});

test('a teacher cannot send an invitation to a student already enrolled in the course', function () {
    Notification::fake();

    $teacher = pathwiseUser('teacher');
    $course = pathwiseCourse($teacher, ['status' => 'published']);
    $student = pathwiseUser('student');
    enrollIn($student, $course);
    $invitation = invitationFor($course, $teacher);

    $this->actingAs($teacher)
        ->from(route('course-invitations.index'))
        ->post(route('course-invitations.send', $invitation), ['student_id' => $student->id])
        ->assertRedirect(route('course-invitations.index'))
        ->assertSessionHas('error');

    expect($invitation->fresh()->student_id)->toBeNull();
    Notification::assertNothingSent();
});

test('a teacher can send an invitation to any student not yet enrolled in the course', function () {
    Notification::fake();

    $teacher = pathwiseUser('teacher');
    $student = pathwiseUser('student');
    $invitation = invitationFor(pathwiseCourse($teacher, ['status' => 'published']), $teacher);

    $this->actingAs($teacher)
        ->post(route('course-invitations.send', $invitation), ['student_id' => $student->id])
        ->assertSessionHas('success');

    expect($invitation->fresh()->student_id)->toBe($student->id);
    Notification::assertSentTo($student, PathwiseNotification::class);
});

dataset('used invitations', [
    'accepted' => [
        fn (App\Models\User $student) => ['accepted_at' => now(), 'accepted_by' => $student->id],
        'already been accepted',
    ],
    'expired' => [
        fn (App\Models\User $student) => ['expires_at' => now()->subDay()],
        'has expired',
    ],
]);

test('accepted or expired invitations show no student search or send controls', function (Closure $state) {
    $teacher = pathwiseUser('teacher');
    $student = pathwiseUser('student', ['name' => 'Searchable Student']);
    $invitation = invitationFor(pathwiseCourse($teacher, ['status' => 'published']), $teacher);
    $invitation->update($state($student));

    $this->actingAs($teacher)
        ->get(route('course-invitations.index'))
        ->assertOk()
        ->assertDontSee('Find Student')
        ->assertDontSee(route('course-invitations.send', $invitation), false);

    // A hand-typed search URL for the used invitation shows no results either.
    $this->actingAs($teacher)
        ->get(route('course-invitations.index', ['invitation' => $invitation->id, 'q' => 'Searchable']))
        ->assertOk()
        ->assertDontSee('Searchable Student')
        ->assertDontSee(route('course-invitations.send', $invitation), false);
})->with('used invitations');

test('the server refuses to send accepted or expired invitations', function (Closure $state, string $message) {
    Notification::fake();

    $teacher = pathwiseUser('teacher');
    $student = pathwiseUser('student');
    $otherStudent = pathwiseUser('student');
    $invitation = invitationFor(pathwiseCourse($teacher, ['status' => 'published']), $teacher);
    $invitation->update($state($student));

    $this->actingAs($teacher)
        ->from(route('course-invitations.index'))
        ->post(route('course-invitations.send', $invitation), ['student_id' => $otherStudent->id])
        ->assertRedirect(route('course-invitations.index'))
        ->assertSessionHas('error', fn (string $error) => str_contains($error, $message));

    expect($invitation->fresh()->student_id)->toBeNull();
    Notification::assertNothingSent();
})->with('used invitations');
