<?php

use App\Models\User;
use App\Notifications\PathwiseNotification;

/*
| Functional Requirement p: users view notifications within their accounts.
| The bell shows the latest notifications and the unread count; opening one
| marks it as read; "mark all as read" clears the count; nobody can open
| another user's notification.
*/

function notifyUser(User $user, string $title, array $extra = []): string
{
    $user->notify(new PathwiseNotification(
        title: $title,
        message: 'Message for '.$title,
        type: $extra['type'] ?? 'general',
        courseId: $extra['courseId'] ?? null,
    ));

    return $user->notifications()->latest()->first()->id;
}

test('the bell shows the user\'s notifications and the unread count', function () {
    $student = pathwiseUser('student');

    notifyUser($student, 'First Notice');
    notifyUser($student, 'Second Notice');
    notifyUser(pathwiseUser('student'), 'Someone Else Notice');

    $this->actingAs($student)
        ->get(route('student.dashboard'))
        ->assertOk()
        ->assertSee('First Notice')
        ->assertSee('Second Notice')
        ->assertDontSee('Someone Else Notice')
        ->assertSee('pw-notification-badge', false)
        ->assertSee(route('notifications.read-all'), false);

    expect($student->unreadNotifications()->count())->toBe(2);
});

test('opening a notification marks it as read and goes to its page', function () {
    $student = pathwiseUser('student');
    $course = pathwiseCourse(pathwiseUser('teacher'), ['status' => 'published']);

    $id = notifyUser($student, 'Course Update', ['courseId' => $course->id]);
    notifyUser($student, 'Still Unread');

    $this->actingAs($student)
        ->post(route('notifications.read', $id))
        ->assertRedirect(route('student.my-courses'));

    expect($student->notifications()->find($id)->read_at)->not->toBeNull()
        ->and($student->unreadNotifications()->count())->toBe(1);
});

test('mark all as read clears the unread count', function () {
    $student = pathwiseUser('student');

    notifyUser($student, 'One');
    notifyUser($student, 'Two');

    $this->actingAs($student)
        ->from(route('student.dashboard'))
        ->post(route('notifications.read-all'))
        ->assertRedirect(route('student.dashboard'));

    expect($student->unreadNotifications()->count())->toBe(0)
        ->and($student->notifications()->count())->toBe(2);   // kept, only marked as read
});

test('a user cannot open another user\'s notification', function () {
    $owner = pathwiseUser('student');
    $id = notifyUser($owner, 'Private Notice');

    $this->actingAs(pathwiseUser('student'))
        ->post(route('notifications.read', $id))
        ->assertNotFound();

    expect($owner->notifications()->find($id)->read_at)->toBeNull();
});

test('guests cannot read or clear notifications', function () {
    $id = notifyUser(pathwiseUser('student'), 'Notice');

    $this->post(route('notifications.read', $id))->assertRedirect(route('login'));
    $this->post(route('notifications.read-all'))->assertRedirect(route('login'));
});
