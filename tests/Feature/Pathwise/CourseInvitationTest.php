<?php

use App\Models\CourseInvitation;
use App\Models\Department;
use App\Models\Enrollment;
use App\Models\StudentApplication;
use App\Models\Transaction;
use App\Notifications\PathwiseNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
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
        ->assertSessionHasErrors('invitation');

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

    $this->post(route('login.store'), ['email' => $student->email, 'password' => 'password'])
        ->assertRedirect($link);
    $this->get($link)->assertOk();
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

    $response->assertSeeInOrder(array_map(fn (int $number) => sprintf('Batchmate %02d', $number), range(1, 10)))
        ->assertDontSee('Batchmate 11')->assertDontSee('Batchmate 12');
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
        ->assertSessionHasErrors('student_id');

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

    $delivery = CourseInvitation::where('parent_invitation_id', $invitation->id)->sole();
    expect($invitation->fresh()->student_id)->toBeNull()
        ->and($delivery->student_id)->toBe($student->id)
        ->and($delivery->is_private)->toBeTrue()
        ->and($delivery->code)->not->toBe($invitation->code);
    Notification::assertSentTo($student, PathwiseNotification::class,
        fn (PathwiseNotification $notice) => $notice->invitationId === $delivery->id && $notice->invitationCode === $delivery->code);
});

dataset('used invitations', [
    'accepted' => [
        fn (App\Models\User $student) => ['accepted_at' => now(), 'accepted_by' => $student->id],
    ],
    'expired' => [
        fn (App\Models\User $student) => ['expires_at' => now()->subDay()],
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

test('the server refuses to send accepted or expired invitations', function (Closure $state) {
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
        ->assertSessionHasErrors('student_id');

    expect($invitation->fresh()->student_id)->toBeNull();
    Notification::assertNothingSent();
})->with('used invitations');

test('two students can independently redeem a paid course code without a payment', function () {
    Notification::fake();
    Http::fake();
    $teacher = pathwiseUser('teacher');
    $course = pathwiseCourse($teacher, ['status' => 'published', 'price' => 499]);
    $invitation = invitationFor($course, $teacher);
    $first = pathwiseUser('student');
    $second = pathwiseUser('student');

    foreach ([$first, $second, $first] as $student) {
        $this->actingAs($student)
            ->post(route('course-invitations.accept', $invitation->code))
            ->assertRedirect(route('student.learn.course', $course));
    }

    expect(Enrollment::where('course_id', $course->id)->where('status', 'active')->count())->toBe(2)
        ->and($invitation->redeemedBy()->orderBy('users.id')->pluck('users.id')->all())->toBe([$first->id, $second->id])
        ->and($invitation->fresh()->accepted_at)->toBeNull()
        ->and(Transaction::count())->toBe(0);
    Http::assertNothingSent();
    Notification::assertSentToTimes($first, PathwiseNotification::class, 1);
    Notification::assertSentToTimes($second, PathwiseNotification::class, 1);

    $this->actingAs($first)->get(route('student.invitations.index'))
        ->assertOk()->assertSee($course->title)->assertSee('Joined')
        ->assertSee(route('student.learn.course', $course), false);
});

test('the student can paste a code or link and reviews it before accepting', function () {
    $teacher = pathwiseUser('teacher');
    $course = pathwiseCourse($teacher, ['status' => 'published']);
    $invitation = invitationFor($course, $teacher);
    $link = route('course-invitations.show', $invitation->code);
    $page = route('student.invitations.index');
    $this->actingAs(pathwiseUser('student'))->get($page)
        ->assertOk()->assertSee('My Invitations')->assertSee('No invitations yet')->assertSee('join-with-code');

    $this->from($page)->post(route('student.invitations.join'), ['code' => strtolower($invitation->code)])
        ->assertRedirect($link);
    $this->from($page)->post(route('student.invitations.join'), ['code' => $link.'?source=teacher'])
        ->assertRedirect($link);
    $this->from($page)->post(route('student.invitations.join'), ['code' => ['invalid']])
        ->assertSessionHasErrors('code');
    $this->from($page)->post(route('student.invitations.join'), ['code' => 'https://example.test/?path=/invitations/'.$invitation->code])
        ->assertSessionHasErrors('code');

    $this->get($link)->assertOk()->assertSee($course->title)->assertSee('Accept and join for free')
        ->assertHeader('Referrer-Policy', 'no-referrer');
    expect(Enrollment::count())->toBe(0);
});

test('private delivery is recipient bound and does not consume the shared code', function () {
    Notification::fake();
    $teacher = pathwiseUser('teacher');
    $course = pathwiseCourse($teacher, ['status' => 'published', 'title' => 'Private invitation course', 'price' => 499]);
    $root = invitationFor($course, $teacher);
    $recipient = pathwiseUser('student');
    $other = pathwiseUser('student');

    foreach (range(1, 2) as $attempt) {
        $this->actingAs($teacher)->post(route('course-invitations.send', $root), ['student_id' => $recipient->id])
            ->assertSessionHas('success');
    }

    $private = CourseInvitation::where('parent_invitation_id', $root->id)->sole();
    $link = route('course-invitations.show', $private->code);
    $index = route('student.invitations.index');
    $this->actingAs($other)->get($index)->assertOk()
        ->assertDontSee($link, false)->assertDontSee($course->title);
    $this->get($link)->assertRedirect($index)->assertSessionHas('error');
    $this->from($index)->post(route('student.invitations.join'), ['code' => $private->code])
        ->assertRedirect($index)->assertSessionHasErrors('code');
    $this->post(route('course-invitations.accept', $private->code))->assertRedirect($index);
    expect(Enrollment::count())->toBe(0);

    $this->actingAs($recipient)->get($index)->assertOk()->assertSee($link, false);
    $this->post(route('course-invitations.accept', $private->code))
        ->assertRedirect(route('student.learn.course', $course));
    $this->actingAs($other)->post(route('course-invitations.accept', $root->code))
        ->assertRedirect(route('student.learn.course', $course));

    expect($private->redeemedBy()->sole()->id)->toBe($recipient->id)
        ->and($root->redeemedBy()->sole()->id)->toBe($other->id)
        ->and(Transaction::count())->toBe(0);
});

test('an orphaned private invitation does not become a shared code', function () {
    $teacher = pathwiseUser('teacher');
    $course = pathwiseCourse($teacher, ['status' => 'published']);
    $invitation = invitationFor($course, $teacher);
    // A legacy private record can have lost its recipient.
    $invitation->update(['student_id' => null, 'is_private' => true]);
    $this->actingAs(pathwiseUser('student'))->get(route('course-invitations.show', $invitation->code))
        ->assertRedirect(route('student.invitations.index'));
    $this->post(route('course-invitations.accept', $invitation->code))
        ->assertRedirect(route('student.invitations.index'));
    expect(Enrollment::count())->toBe(0);
});

test('expiring at the current instant and deactivating a parent both block private invitations', function (string $state) {
    Notification::fake();
    $teacher = pathwiseUser('teacher');
    $course = pathwiseCourse($teacher, ['status' => 'published']);
    $root = invitationFor($course, $teacher);
    $recipient = pathwiseUser('student');
    $this->actingAs($teacher)->post(route('course-invitations.send', $root), ['student_id' => $recipient->id]);
    $private = CourseInvitation::where('parent_invitation_id', $root->id)->sole();

    if ($state === 'Expired') {
        $root->update(['expires_at' => now()]);
    } else {
        $this->delete(route('course-invitations.destroy', $root))->assertSessionHas('success');
    }

    $this->actingAs($recipient)->get(route('student.invitations.index'))->assertOk()->assertSee($state);
    $this->get(route('course-invitations.show', $private->code))->assertOk()->assertDontSee('data-accept-button');
    $this->post(route('course-invitations.accept', $private->code))->assertSessionHasErrors('invitation');
    $this->post(route('course-invitations.accept', $root->code))->assertSessionHasErrors('invitation');
    expect(Enrollment::count())->toBe(0)
        ->and($private->redeemedBy()->count())->toBe(0);
})->with(['Expired', 'Deactivated']);

test("a teacher cannot alter an invitation for another teacher's course", function () {
    $owner = pathwiseUser('teacher');
    $course = pathwiseCourse($owner, ['status' => 'published']);
    $root = invitationFor($course, $owner);

    $this->actingAs(pathwiseUser('teacher'))->post(route('course-invitations.store'), ['course_id' => $course->id])
        ->assertSessionHasErrors('course_id');
    $this->post(route('course-invitations.send', $root), ['student_id' => pathwiseUser('student')->id])->assertForbidden();
    $this->delete(route('course-invitations.destroy', $root))->assertForbidden();

    expect($root->fresh()->deactivated_at)->toBeNull();
});

test('a pending or unverified student sees no invitation details and cannot join', function (bool $pending) {
    $teacher = pathwiseUser('teacher');
    $course = pathwiseCourse($teacher, ['status' => 'published', 'title' => 'Hidden invitation details', 'price' => 499]);
    $invitation = invitationFor($course, $teacher);
    $student = pathwiseUser('student', ['email_verified_at' => null]);
    if ($pending) {
        $student->studentApplications()->create([
            'submission_no' => 1,
            'status' => StudentApplication::PENDING,
            'study_load_path' => 'study-loads/test.pdf',
            'study_load_original_name' => 'test.pdf',
            'study_load_mime' => 'application/pdf',
            'study_load_size' => 20,
            'submitted_at' => now(),
        ]);
    }

    $gate = route($pending ? 'student-application.status' : 'verification.notice');
    $this->actingAs($student)->get(route('course-invitations.show', $invitation->code))
        ->assertOk()->assertDontSee($course->title)->assertDontSee('data-accept-button')->assertSee($gate, false);
    $this->get(route('student.invitations.index'))->assertRedirect($gate);
    $this->post(route('student.invitations.join'), ['code' => $invitation->code])->assertRedirect($gate);
    $this->post(route('course-invitations.accept', $invitation->code))->assertRedirect($gate);

    expect(Enrollment::count())->toBe(0);
})->with(['pending' => true, 'unverified' => false]);

test('other roles cannot preview redeem or list student invitations', function (string $role) {
    $teacher = pathwiseUser('teacher');
    $course = pathwiseCourse($teacher, ['status' => 'published']);
    $invitation = invitationFor($course, $teacher);

    $this->actingAs(pathwiseUser($role))->get(route('course-invitations.show', $invitation->code))->assertForbidden();
    $this->get(route('student.invitations.index'))->assertForbidden();
    $this->post(route('course-invitations.accept', $invitation->code))->assertForbidden();
    expect(Enrollment::count())->toBe(0);
})->with(['teacher', 'admin', 'super_admin']);

test('a redemption cannot recreate a cancelled or removed enrollment', function (string $resolution) {
    Notification::fake();
    $teacher = pathwiseUser('teacher');
    $student = pathwiseUser('student');
    $course = pathwiseCourse($teacher, ['status' => 'published']);
    $used = invitationFor($course, $teacher);
    $other = invitationFor($course, $teacher);
    $this->actingAs($student)->post(route('course-invitations.accept', $used->code))
        ->assertRedirect(route('student.learn.course', $course));

    $enrollment = Enrollment::sole();
    if ($resolution === 'removed') {
        $enrollment->delete();
    } else {
        $enrollment->update(['status' => 'cancelled']);
    }

    $this->post(route('course-invitations.accept', $other->code))->assertSessionHasErrors('invitation');
    $this->post(route('student.enroll', $course))->assertSessionHas('error');
    expect($used->redeemedBy()->count())->toBe(1)
        ->and($other->redeemedBy()->count())->toBe(0)
        ->and(Enrollment::withAccess()->count())->toBe(0);
})->with(['cancelled', 'removed']);
