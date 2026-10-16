<?php

use App\Models\Course;
use App\Models\CourseTemplate;
use Database\Seeders\CourseTemplateSeeder;

/*
| Manuscript: "pre-designed curriculum templates" (Rationale, Scope,
| Definition of Terms "Course Template", Table 1, Tables 8 and 9).
*/

function templateCourseInput(array $overrides = []): array
{
    $category = \App\Models\CourseCategory::firstOrCreate(['name' => 'Information Technology']);

    return array_merge([
        'title' => 'Course From Template',
        'description' => 'A course created from a template.',
        'category_id' => $category->id,
        'difficulty_level' => 'beginner',
        'price' => 0,
        'estimated_hours' => 10,
        'certificate_available' => 1,
    ], $overrides);
}

test('the template seeder creates the built-in templates and can run twice', function () {
    $this->seed(CourseTemplateSeeder::class);
    $this->seed(CourseTemplateSeeder::class);

    expect(CourseTemplate::count())->toBe(3)
        ->and(CourseTemplate::where('name', 'Standard Course (3 Modules)')->first()->lessonOutline())
        ->toHaveCount(5);
});

test('the create course page lists active templates', function () {
    $this->seed(CourseTemplateSeeder::class);

    $this->actingAs(pathwiseUser('teacher'))
        ->get(route('teacher.courses.create'))
        ->assertOk()
        ->assertSee('Start from a template')
        ->assertSee('Hands-on Skills Workshop');
});

test('creating a course from a template adds unpublished outline lessons', function () {
    $this->seed(CourseTemplateSeeder::class);
    $teacher = pathwiseUser('teacher');
    $template = CourseTemplate::where('name', 'Standard Course (3 Modules)')->firstOrFail();

    $this->actingAs($teacher)
        ->post(route('teacher.courses.store'), templateCourseInput(['template_id' => $template->id]))
        ->assertRedirect(route('teacher.my-courses'))
        ->assertSessionHasNoErrors();

    $course = Course::where('title', 'Course From Template')->firstOrFail();

    expect($course->template_id)->toBe($template->id)
        ->and($course->status)->toBe('draft')
        ->and($course->lessons()->count())->toBe(5)
        ->and($course->lessons()->where('is_published', true)->count())->toBe(0)
        ->and($course->lessons()->orderBy('lesson_order')->first()->title)
        ->toBe('Course Overview and Learning Outcomes');
});

test('creating a blank course adds no lessons', function () {
    $teacher = pathwiseUser('teacher');

    $this->actingAs($teacher)
        ->post(route('teacher.courses.store'), templateCourseInput())
        ->assertSessionHasNoErrors();

    $course = Course::where('title', 'Course From Template')->firstOrFail();

    expect($course->template_id)->toBeNull()
        ->and($course->lessons()->count())->toBe(0);
});

test('an inactive template cannot be used', function () {
    $template = CourseTemplate::create([
        'name' => 'Old Template',
        'structure' => ['lessons' => [['title' => 'One']]],
        'is_active' => false,
    ]);

    $this->actingAs(pathwiseUser('teacher'))
        ->post(route('teacher.courses.store'), templateCourseInput(['template_id' => $template->id]))
        ->assertSessionHasErrors('template_id');

    expect(Course::count())->toBe(0);
});

test('a template course cannot be submitted until its outline lessons are published', function () {
    $this->seed(CourseTemplateSeeder::class);
    $teacher = pathwiseUser('teacher');
    $template = CourseTemplate::firstOrFail();

    $this->actingAs($teacher)
        ->post(route('teacher.courses.store'), templateCourseInput(['template_id' => $template->id]));

    $course = Course::where('title', 'Course From Template')->firstOrFail();

    $this->actingAs($teacher)
        ->post(route('teacher.courses.submit', $course))
        ->assertSessionHas('error');

    expect($course->fresh()->status)->toBe('draft');

    $course->lessons()->update(['is_published' => true]);

    $this->actingAs($teacher)
        ->post(route('teacher.courses.submit', $course))
        ->assertSessionHas('success');

    expect($course->fresh()->status)->toBe('pending');
});

test('invalid outline entries in a template are ignored', function () {
    $template = new CourseTemplate([
        'name' => 'Messy',
        'structure' => ['lessons' => [['title' => 'Good'], ['title' => ''], 'not-an-array']],
    ]);

    expect($template->lessonOutline())->toHaveCount(1);
});
