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

    $this->actingAs(pathwiseApprovedTeacher())
        ->get(route('teacher.courses.create'))
        ->assertOk()
        ->assertSee('Start from a template')
        ->assertSee('Hands-on Skills Workshop');
});

test('creating a course from a template adds unpublished outline lessons', function () {
    $this->seed(CourseTemplateSeeder::class);
    $teacher = pathwiseApprovedTeacher();
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
    $teacher = pathwiseApprovedTeacher();

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

    $this->actingAs(pathwiseApprovedTeacher())
        ->post(route('teacher.courses.store'), templateCourseInput(['template_id' => $template->id]))
        ->assertSessionHasErrors('template_id');

    expect(Course::count())->toBe(0);
});

test('a template course cannot be submitted until its outline lessons are published', function () {
    $this->seed(CourseTemplateSeeder::class);
    $teacher = pathwiseApprovedTeacher();
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

// Selector UI polish must not alter the existing curriculum-template behavior.
test('all four template choices retain their submitted IDs labels and accessible grouping', function () {
    $this->seed(CourseTemplateSeeder::class);
    $teacher = pathwiseApprovedTeacher();
    $response = $this->actingAs($teacher)->get(route('teacher.courses.create'))->assertOk();
    $document = \Dom\HTMLDocument::createFromString($response->getContent(), LIBXML_NOERROR);
    $selector = $document->querySelector('[data-course-template-selector]');
    expect(strtolower($selector->tagName))->toBe('fieldset')
        ->and($selector->querySelector('legend')->textContent)->toContain('Start from a template');
    $choices = $selector->querySelectorAll('input[type=radio][name=template_id]');
    expect($choices->length)->toBe(4);
    $values = [];
    foreach ($choices as $choice) {
        $values[] = $choice->getAttribute('value');
        expect($document->querySelector('label[for="'.$choice->getAttribute('id').'"]'))->not->toBeNull();
        foreach (explode(' ', $choice->getAttribute('aria-describedby')) as $descriptionId) {
            expect($document->getElementById($descriptionId))->not->toBeNull();
        }
    }
    expect($values)->toBe(array_merge([''], CourseTemplate::active()->orderBy('name')->pluck('id')->map(fn ($id) => (string) $id)->all()))
        ->and($selector->querySelector('#course-template-blank')->hasAttribute('checked'))->toBeTrue();
});

dataset('built in course template choices', [
    'blank' => [null, []],
    'workshop' => ['Hands-on Skills Workshop', ['Workshop Introduction and Setup', 'Demonstration', 'Guided Practice', 'Independent Exercise', 'Reflection and Next Steps']],
    'microcredential' => ['Short Course (Micro-credential)', ['Introduction', 'Key Lesson', 'Practice and Recap']],
    'standard' => ['Standard Course (3 Modules)', ['Course Overview and Learning Outcomes', 'Module 1: Core Concepts', 'Module 2: Applying the Concepts', 'Module 3: Deeper Topics', 'Course Summary and Review']],
]);

test('each existing template choice generates only its ordered unpublished Reading outline', function (?string $name, array $titles) {
    $this->seed(CourseTemplateSeeder::class);
    $teacher = pathwiseApprovedTeacher();
    $template = $name ? CourseTemplate::where('name', $name)->sole() : null;
    $templateSnapshot = CourseTemplate::orderBy('id')->get()->map(fn ($row) => $row->getAttributes())->all();
    $this->actingAs($teacher)->post(route('teacher.courses.store'), templateCourseInput(['template_id' => $template?->id]))
        ->assertSessionHasNoErrors()->assertRedirect(route('teacher.my-courses'));
    $course = Course::where('teacher_id', $teacher->id)->sole();
    $lessons = $course->lessons()->orderBy('lesson_order')->get();
    expect($course->template_id)->toBe($template?->id)->and($course->status)->toBe('draft')
        ->and($lessons->pluck('title')->all())->toBe($titles)
        ->and($lessons->pluck('lesson_order')->all())->toBe($titles ? range(1, count($titles)) : []);
    foreach ($lessons as $lesson) {
        expect((bool) $lesson->is_published)->toBeFalse()->and((bool) $lesson->is_preview)->toBeFalse()
            ->and($lesson->lesson_type)->toBe('text')->and($lesson->sections()->count())->toBe(0)
            ->and($lesson->content)->toContain('[Template guide - replace this text with your lesson content, then publish the lesson.]');
    }
    expect(CourseTemplate::orderBy('id')->get()->map(fn ($row) => $row->getAttributes())->all())->toBe($templateSnapshot);
})->with('built in course template choices');

test('template selection is retained after course validation fails', function () {
    $this->seed(CourseTemplateSeeder::class);
    $teacher = pathwiseApprovedTeacher();
    $template = CourseTemplate::where('name', 'Short Course (Micro-credential)')->sole();
    $this->actingAs($teacher)->from(route('teacher.courses.create'))->post(route('teacher.courses.store'), templateCourseInput(['title' => '', 'template_id' => $template->id]))
        ->assertSessionHasErrors('title');
    $response = $this->get(route('teacher.courses.create'))->assertOk();
    $document = \Dom\HTMLDocument::createFromString($response->getContent(), LIBXML_NOERROR);
    expect($document->querySelector('#course-template-'.$template->id)->hasAttribute('checked'))->toBeTrue()
        ->and($document->querySelector('#course-template-blank')->hasAttribute('checked'))->toBeFalse()
        ->and(Course::count())->toBe(0);
});

test('template Reading outlines survive unchanged builder conversion and intentional rich editing', function (string $name) {
    fakeFileDisks();
    $this->seed(CourseTemplateSeeder::class);
    $teacher = pathwiseApprovedTeacher();
    $template = CourseTemplate::where('name', $name)->sole();
    $this->actingAs($teacher)->post(route('teacher.courses.store'), templateCourseInput(['template_id' => $template->id]))->assertSessionHasNoErrors();
    $course = Course::where('teacher_id', $teacher->id)->sole();
    $lessons = $course->lessons()->orderBy('lesson_order')->get();
    $first = $lessons->first();
    $original = $first->content;
    $others = $lessons->skip(1)->map(fn ($lesson) => $lesson->getAttributes())->all();
    $this->get(route('teacher.lessons.edit', $first))->assertOk()->assertSee('data-reading-editor', false)->assertSee($original);
    $fields = ['title' => $first->title, 'lesson_type' => 'text', 'lesson_order' => 1, 'reading_builder' => 1, 'is_published' => 0];
    $this->put(route('teacher.lessons.update', $first), $fields + ['sections' => [
        ['type' => 'text', 'body_format' => 'plain', 'body' => str_replace("\n", "\r\n", $original)],
    ]])->assertSessionHasNoErrors();
    $section = $first->sections()->sole();
    expect($section->body)->toBe($original)->and($section->body_format)->toBe('plain')->and($first->fresh()->content)->toBe($original);
    $paragraphs = array_map(fn ($paragraph) => '<p>'.implode('<br>', array_map(fn ($line) => e($line), explode("\n", $paragraph))).'</p>', explode("\n\n", $original));
    $html = '<h2>'.e($first->title).'</h2>'.implode('', $paragraphs).'<p><strong>Teacher added explanation.</strong></p>';
    $this->put(route('teacher.lessons.update', $first), $fields + ['sections' => [
        ['id' => $section->id, 'type' => 'text', 'body_format' => 'html', 'body' => $html],
    ]])->assertSessionHasNoErrors();
    expect($section->fresh()->id)->toBe($section->id)->and($section->fresh()->body_format)->toBe('html')
        ->and($section->fresh()->body)->toContain('<h2>', '<strong>Teacher added explanation.</strong>')
        ->and($first->fresh()->content)->toContain($original, 'Teacher added explanation.')
        ->and((bool) $first->fresh()->is_published)->toBeFalse()
        ->and($course->fresh()->status)->toBe('draft')->and($course->fresh()->template_id)->toBe($template->id)
        ->and($course->lessons()->orderBy('lesson_order')->get()->skip(1)->map(fn ($lesson) => $lesson->getAttributes())->all())->toBe($others);
    $this->get(route('teacher.lessons.edit', $first))->assertOk()->assertSee('data-editor-html=', false)->assertSee('Teacher added explanation.');
})->with(['Hands-on Skills Workshop', 'Short Course (Micro-credential)', 'Standard Course (3 Modules)']);
