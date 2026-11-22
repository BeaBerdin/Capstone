<?php

use App\Http\Controllers\CourseInvitationController;
use App\Models\CourseInvitation;
use App\Models\Enrollment;

/*
| Students join a course with an invite code on the Course Invitations page.
| The code only opens the usual invitation page, so every existing rule
| still applies there: free courses enroll on Accept, paid courses go to
| payment, and expired, used or other students' codes are refused.
*/

function joinInvitation(array $courseAttributes = [], array $invitationAttributes = []): CourseInvitation
{
    $teacher = pathwiseUser('teacher');
    $course = pathwiseCourse($teacher, array_merge(['status' => 'published', 'price' => 0], $courseAttributes));

    return CourseInvitation::create(array_merge([
        'course_id' => $course->id,
        'created_by' => $teacher->id,
        'student_id' => null,
        'code' => 'ABCD1234EFGH',
        'expires_at' => null,
    ], $invitationAttributes));
}

test('the student sidebar links to Course Invitations and the page has the code box', function () {
    $student = pathwiseUser('student');

    $this->actingAs($student)
        ->get(route('student.dashboard'))
        ->assertOk()
        ->assertSee('data-test="nav-course-invitations"', false);

    $this->actingAs($student)
        ->get(route('student.invitations.index'))
        ->assertOk()
        ->assertSee('Join with invite code')
        ->assertSee(route('student.invitations.join'), false);
});

test('a valid code opens the invitation page, and accepting enrolls in a free course', function (string $typed) {
    $invitation = joinInvitation();
    $student = pathwiseUser('student');

    $this->actingAs($student)
        ->post(route('student.invitations.join'), ['code' => $typed])
        ->assertRedirect(route('course-invitations.show', 'ABCD1234EFGH'));

    $this->actingAs($student)
        ->post(route('course-invitations.accept', $invitation->code))
        ->assertRedirect(route('student.learn.course', $invitation->course));

    expect(Enrollment::where('student_id', $student->id)->where('course_id', $invitation->course_id)->exists())->toBeTrue();
})->with([
    'exact' => 'ABCD1234EFGH',
    'lower case with spaces' => '  abcd 1234 efgh ',
    'with dashes' => 'ABCD-1234-EFGH',
    'whole link pasted' => 'https://learnpathwise.site/invitations/ABCD1234EFGH',
]);

test('a paid course code leads to payment, never to a free enrollment', function () {
    $invitation = joinInvitation(['price' => 499]);
    $student = pathwiseUser('student');

    $this->actingAs($student)
        ->post(route('student.invitations.join'), ['code' => 'ABCD1234EFGH'])
        ->assertRedirect(route('course-invitations.show', 'ABCD1234EFGH'));

    $this->actingAs($student)
        ->post(route('course-invitations.accept', $invitation->code))
        ->assertRedirect(route('course-invitations.show', $invitation->code));

    expect(Enrollment::where('student_id', $student->id)->count())->toBe(0);
});

test('bad, expired, used, unpublished and other students\' codes are refused', function (Closure $setup, string $message) {
    $student = pathwiseUser('student');
    $code = $setup($student);

    $this->actingAs($student)
        ->post(route('student.invitations.join'), ['code' => $code])
        ->assertRedirect(route('student.invitations.index'))
        ->assertSessionHasErrors(['code' => $message]);

    expect(Enrollment::where('student_id', $student->id)->count())->toBe(0);
})->with([
    'wrong format' => [fn () => 'abc', 'Enter the 12-character invite code from your teacher.'],
    'unknown code' => [fn () => 'ZZZZ9999ZZZZ', 'That invite code is not valid. Check the code and try again.'],
    'sent to another student' => [fn () => joinInvitation([], ['student_id' => pathwiseUser('student')->id])->code, 'That invite code is not valid. Check the code and try again.'],
    'expired' => [fn () => joinInvitation([], ['expires_at' => now()->subMinute()])->code, 'That invite code has expired. Ask your teacher for a new one.'],
    'already used' => [fn () => joinInvitation([], ['accepted_at' => now()])->code, 'That invite code has already been used. Ask your teacher for a new one.'],
    'course unpublished' => [fn () => joinInvitation(['status' => 'draft'])->code, 'That course is not open for enrollment right now.'],
]);

test('invite code attempts are rate-limited', function () {
    $student = pathwiseUser('student');

    foreach (range(1, 10) as $attempt) {
        $this->actingAs($student)->post(route('student.invitations.join'), ['code' => 'ZZZZ9999ZZZZ']);
    }

    $this->actingAs($student)
        ->post(route('student.invitations.join'), ['code' => 'ZZZZ9999ZZZZ'])
        ->assertStatus(429);
});

test('only students can use the Course Invitations page', function (string $role) {
    $this->actingAs(pathwiseUser($role))
        ->get(route('student.invitations.index'))
        ->assertForbidden();
})->with(['teacher', 'admin', 'super_admin']);

test('the page lists open invitations sent to the student, not other students\' or used ones', function () {
    $student = pathwiseUser('student');
    $mine = joinInvitation(['title' => 'Course For Me'], ['student_id' => $student->id]);
    joinInvitation(['title' => 'Course For Someone Else'], ['code' => 'OTHER0000001', 'student_id' => pathwiseUser('student')->id]);
    joinInvitation(['title' => 'Course Already Joined'], ['code' => 'USED00000001', 'student_id' => $student->id, 'accepted_at' => now()]);

    $this->actingAs($student)
        ->get(route('student.invitations.index'))
        ->assertOk()
        ->assertSee('Course For Me')
        ->assertSee(route('course-invitations.show', $mine->code), false)
        ->assertDontSee('Course For Someone Else')
        ->assertDontSee('Course Already Joined');
});

test('the teacher sees a copyable link for open invitations only', function () {
    $open = joinInvitation();
    joinInvitation([], ['code' => 'USED00000002', 'created_by' => $open->created_by, 'accepted_at' => now()]);

    $this->actingAs($open->creator)
        ->get(route('course-invitations.index'))
        ->assertOk()
        ->assertSee('data-copy="'.route('course-invitations.show', $open->code).'"', false)
        ->assertDontSee('data-copy="'.route('course-invitations.show', 'USED00000002').'"', false);
});

test('codes are normalized from typed text or a pasted link', function () {
    expect(CourseInvitationController::normalizeCode(' ab-cd 12 '))->toBe('ABCD12')
        ->and(CourseInvitationController::normalizeCode('https://learnpathwise.site/invitations/abcd1234efgh?x=1'))->toBe('ABCD1234EFGH');
});
