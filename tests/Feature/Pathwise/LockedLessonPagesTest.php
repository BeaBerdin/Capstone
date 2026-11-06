<?php

use App\Models\Lesson;

/*
| Final validation audit SYS-2 (approved November 6, 2026): the teacher who
| owns a course that is pending approval or published is sent back to the
| lesson list with an explanation instead of a 403 error page. Saving stays
| blocked, and other teachers still get 403.
*/

function lockedLessonFixture(string $status): array
{
    $teacher = pathwiseUser('teacher');
    $course = pathwiseCourse($teacher, ['status' => $status]);

    $lesson = Lesson::create([
        'course_id' => $course->id,
        'title' => 'Locked Lesson',
        'content' => 'Original content',
        'lesson_type' => 'text',
        'lesson_order' => 1,
        'is_preview' => false,
        'is_published' => true,
    ]);

    return [$teacher, $course, $lesson];
}

test('the owner is redirected from the add and edit lesson pages of a pending course with an explanation', function () {
    [$teacher, $course, $lesson] = lockedLessonFixture('pending');

    foreach ([route('teacher.lessons.create', $course), route('teacher.lessons.edit', $lesson)] as $url) {
        $this->actingAs($teacher)
            ->get($url)
            ->assertRedirect(route('teacher.lessons', $course))
            ->assertSessionHas('error', fn ($message) => str_contains($message, 'waiting for administrator approval'));
    }

    $this->actingAs($teacher)
        ->followingRedirects()
        ->get(route('teacher.lessons.create', $course))
        ->assertOk()
        ->assertSee('waiting for administrator approval');
});

test('the owner is redirected from the add and edit lesson pages of a published course with an explanation', function () {
    [$teacher, $course, $lesson] = lockedLessonFixture('published');

    foreach ([route('teacher.lessons.create', $course), route('teacher.lessons.edit', $lesson)] as $url) {
        $this->actingAs($teacher)
            ->get($url)
            ->assertRedirect(route('teacher.lessons', $course))
            ->assertSessionHas('error', fn ($message) => str_contains($message, 'This course is published'));
    }
});

test('saving a lesson of a locked course is still refused', function (string $status) {
    [$teacher, $course, $lesson] = lockedLessonFixture($status);

    $this->actingAs($teacher)
        ->post(route('teacher.lessons.store', $course), [
            'title' => 'New lesson',
            'lesson_type' => 'text',
            'content' => 'Text',
            'lesson_order' => 2,
        ])
        ->assertForbidden();

    expect(Lesson::where('course_id', $course->id)->count())->toBe(1)
        ->and($lesson->fresh()->content)->toBe('Original content');
})->with(['pending', 'published']);

test('draft and returned courses still open the add and edit lesson pages', function (string $status) {
    [$teacher, $course, $lesson] = lockedLessonFixture($status);

    $this->actingAs($teacher)->get(route('teacher.lessons.create', $course))->assertOk();
    $this->actingAs($teacher)->get(route('teacher.lessons.edit', $lesson))->assertOk();
})->with(['draft', 'rejected']);

test('another teacher still gets 403 on a locked course instead of a redirect', function () {
    [, $course, $lesson] = lockedLessonFixture('pending');
    $otherTeacher = pathwiseUser('teacher');

    $this->actingAs($otherTeacher)->get(route('teacher.lessons.create', $course))->assertForbidden();
    $this->actingAs($otherTeacher)->get(route('teacher.lessons.edit', $lesson))->assertForbidden();
});
