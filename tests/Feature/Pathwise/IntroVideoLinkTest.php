<?php

use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\Department;

/*
| The course introduction video link: only https links can be saved, by
| teachers and by administrators, and the student page never prints a
| link that is not http or https.
*/

function introVideoCourseInput(array $overrides = []): array
{
    return array_merge([
        'title' => 'Intro Video Course',
        'description' => 'A course description.',
        'category_id' => CourseCategory::firstOrCreate(['name' => 'Information Technology'])->id,
        'difficulty_level' => 'beginner',
        'price' => 0,
        'status' => 'draft',
        'estimated_hours' => 4,
        'certificate_available' => 1,
    ], $overrides);
}

dataset('unsafe intro video links', [
    'javascript scheme' => 'javascript:alert(document.cookie)',
    'javascript scheme, mixed case' => 'JaVaScRiPt:alert(1)',
    'data scheme' => 'data:text/html,<script>alert(1)</script>',
    'vbscript scheme' => 'vbscript:msgbox(1)',
    'file scheme' => 'file:///etc/passwd',
    'ftp scheme' => 'ftp://example.com/video.mp4',
    'plain http' => 'http://www.youtube.com/watch?v=abc123',
    'no scheme' => 'www.youtube.com/watch?v=abc123',
    'protocol-relative' => '//evil.example.com/video',
    'not a link' => 'click here',
]);

test('a teacher cannot save an unsafe intro video link', function (string $link) {
    $teacher = pathwiseUser('teacher');
    $draft = pathwiseCourse($teacher, ['status' => 'draft', 'title' => 'Existing Draft']);

    $this->actingAs($teacher)
        ->post(route('teacher.courses.store'), introVideoCourseInput(['intro_video' => $link]))
        ->assertSessionHasErrors('intro_video');

    expect(Course::where('title', 'Intro Video Course')->exists())->toBeFalse();

    $this->actingAs($teacher)
        ->put(route('teacher.courses.update', $draft), introVideoCourseInput(['title' => 'Existing Draft', 'intro_video' => $link]))
        ->assertSessionHasErrors('intro_video');

    expect($draft->fresh()->intro_video)->toBeNull();
})->with('unsafe intro video links');

test('an administrator cannot save an unsafe intro video link', function (string $link) {
    $department = Department::create(['name' => 'Computing']);
    $admin = pathwiseUser('admin', ['department_id' => $department->id]);
    $course = pathwiseCourse(pathwiseUser('teacher', ['department_id' => $department->id]), ['status' => 'draft', 'title' => 'Existing Draft']);

    $this->actingAs($admin)
        ->post(route('courses.store'), introVideoCourseInput(['intro_video' => $link]))
        ->assertSessionHasErrors('intro_video');

    expect(Course::where('title', 'Intro Video Course')->exists())->toBeFalse();

    $this->actingAs($admin)
        ->put(route('courses.update', $course), introVideoCourseInput(['title' => 'Existing Draft', 'intro_video' => $link]))
        ->assertSessionHasErrors('intro_video');

    expect($course->fresh()->intro_video)->toBeNull();
})->with('unsafe intro video links');

test('an https intro video link is saved by a teacher and by an administrator', function () {
    $link = 'https://www.youtube.com/watch?v=abc123';
    $department = Department::create(['name' => 'Computing']);
    $teacher = pathwiseUser('teacher', ['department_id' => $department->id]);

    $this->actingAs($teacher)
        ->post(route('teacher.courses.store'), introVideoCourseInput(['intro_video' => $link]))
        ->assertSessionHasNoErrors();

    $course = Course::where('title', 'Intro Video Course')->sole();
    expect($course->intro_video)->toBe($link);

    $this->actingAs($teacher)
        ->put(route('teacher.courses.update', $course), introVideoCourseInput(['intro_video' => 'https://youtu.be/xyz789']))
        ->assertSessionHasNoErrors();
    expect($course->fresh()->intro_video)->toBe('https://youtu.be/xyz789');

    $this->actingAs(pathwiseUser('admin', ['department_id' => $department->id]))
        ->put(route('courses.update', $course), introVideoCourseInput(['intro_video' => 'https://vimeo.com/12345']))
        ->assertSessionHasNoErrors();
    expect($course->fresh()->intro_video)->toBe('https://vimeo.com/12345');

    // Leaving it empty is still allowed.
    $this->actingAs($teacher)
        ->put(route('teacher.courses.update', $course->fresh()), introVideoCourseInput(['intro_video' => '']))
        ->assertSessionHasNoErrors();
    expect($course->fresh()->intro_video)->toBeNull();
});

test('the student course page never prints a link that is not http or https', function (string $stored) {
    $course = pathwiseCourse(pathwiseUser('teacher'), ['status' => 'published', 'title' => 'Published Course']);
    // An unsafe value that reached the database some other way (old data).
    Course::whereKey($course->id)->update(['intro_video' => $stored]);

    $response = $this->actingAs(pathwiseUser('student'))->get(route('student.course.show', $course))->assertOk();

    expect($response->getContent())
        ->not->toContain('href="javascript:')
        ->not->toContain('href="data:')
        ->not->toContain('href="'.e($stored).'"');
    expect($course->fresh()->safeIntroVideoUrl())->toBeNull();
})->with([
    'javascript:alert(1)',
    'JAVASCRIPT:alert(1)',
    'data:text/html,<script>alert(1)</script>',
    'vbscript:msgbox(1)',
    'ftp://example.com/video.mp4',
    '//evil.example.com/video',
    'not a link',
]);

test('the student course page prints http and https intro video links', function (string $stored) {
    $course = pathwiseCourse(pathwiseUser('teacher'), ['status' => 'published', 'title' => 'Published Course']);
    Course::whereKey($course->id)->update(['intro_video' => $stored]);

    $this->actingAs(pathwiseUser('student'))->get(route('student.course.show', $course))
        ->assertOk()
        ->assertSee('href="'.e($stored).'"', false);
})->with(['https://www.youtube.com/watch?v=abc123', 'http://example.com/older-link']);
