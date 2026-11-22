<?php

use App\Models\Course;
use App\Models\CourseTemplate;
use App\Models\Enrollment;
use App\Models\LessonProgress;
use Database\Seeders\CourseTemplateSeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// The exclusion is deliberately field-scoped globally, not a route authorization rule.
test('Reading whitespace exceptions leave unrelated fields trimmed on form and JSON requests', function (bool $json) {
    Route::post('/_tests/reading-trim-probe', fn (Request $request) => response()->json($request->all()));
    $values = [
        'title' => '  Title  ', 'description' => "  Description\n", 'body' => '  Other body  ',
        'content' => '  Other content  ', 'empty' => '   ', 'answer_text' => "  Existing answer\n",
        'sections' => [['body' => "\n  Reading text  \n", 'caption' => '  Caption  ', 'type' => ' text ']],
        'unrelated' => ['body' => '  Nested other body  '],
    ];
    $response = $json ? $this->postJson('/_tests/reading-trim-probe', $values) : $this->post('/_tests/reading-trim-probe', $values);
    $response->assertOk()->assertJsonPath('title', 'Title')->assertJsonPath('description', 'Description')
        ->assertJsonPath('body', 'Other body')->assertJsonPath('content', 'Other content')->assertJsonPath('empty', null)
        ->assertJsonPath('unrelated.body', 'Nested other body')->assertJsonPath('sections.0.caption', 'Caption')
        ->assertJsonPath('sections.0.type', 'text')->assertJsonPath('sections.0.body', "\n  Reading text  \n")
        ->assertJsonPath('answer_text', "  Existing answer\n");
})->with([false, true]);

test('template to rich Reading to approved Student display preserves guidance and approval boundaries', function () {
    fakeFileDisks();
    $this->seed(CourseTemplateSeeder::class);
    $teacher = pathwiseApprovedTeacher();
    $student = pathwiseUser('student');
    $reviewer = pathwiseUser('super_admin');
    $template = CourseTemplate::where('name', 'Short Course (Micro-credential)')->sole();
    $category = \App\Models\CourseCategory::firstOrCreate(['name' => 'Integration templates']);
    $this->actingAs($teacher)->post(route('teacher.courses.store'), [
        'title' => 'Template Reading integration', 'description' => 'A synthetic integration course.',
        'category_id' => $category->id, 'difficulty_level' => 'beginner', 'price' => 0,
        'estimated_hours' => 1, 'certificate_available' => 0, 'template_id' => $template->id,
    ])->assertSessionHasNoErrors();
    $course = Course::where('teacher_id', $teacher->id)->sole();
    $lessons = $course->lessons()->orderBy('lesson_order')->get();
    $first = $lessons->first();
    $guidance = $first->content;
    expect($lessons)->toHaveCount(3)->and($course->status)->toBe('draft');
    Enrollment::create(['course_id' => $course->id, 'student_id' => $student->id, 'status' => 'active']);
    $this->actingAs($student)->get(route('student.lesson.view', $first))->assertNotFound();
    $paragraphs = array_map(fn ($text) => '<p>'.implode('<br>', array_map(fn ($line) => e($line), explode("\n", $text))).'</p>', explode("\n\n", $guidance));
    $html = '<h2>Teacher explanation</h2>'.implode('', $paragraphs).'<p><strong>Additional context</strong> <em>with emphasis</em>.</p>';
    $this->actingAs($teacher)->put(route('teacher.lessons.update', $first), [
        'title' => $first->title, 'lesson_type' => 'text', 'lesson_order' => 1, 'is_published' => 1,
        'reading_builder' => 1, 'sections' => [['type' => 'text', 'body_format' => 'html', 'body' => $html]],
    ])->assertSessionHasNoErrors();
    $this->post(route('teacher.courses.submit', $course))->assertSessionHas('error');
    expect($course->fresh()->status)->toBe('draft');
    foreach ($lessons->skip(1) as $lesson) {
        $this->put(route('teacher.lessons.update', $lesson), [
            'title' => $lesson->title, 'lesson_type' => 'text', 'lesson_order' => $lesson->lesson_order,
            'is_published' => 1, 'content' => $lesson->content,
        ])->assertSessionHasNoErrors();
    }
    $this->post(route('teacher.courses.submit', $course))->assertSessionHas('success');
    expect($course->fresh()->status)->toBe('pending');
    $this->actingAs($student)->get(route('student.lesson.view', $first))
        ->assertRedirect(route('student.my-courses'))->assertSessionHas('course_under_review', true);
    $this->actingAs($reviewer)->post(route('courses.approve', $course))->assertSessionHas('success');
    expect($course->fresh()->status)->toBe('published')->and($course->fresh()->template_id)->toBe($template->id)
        ->and($first->fresh()->content)->toContain($guidance, 'Additional context')
        ->and($first->sections()->sole()->body_format)->toBe('html');
    $response = $this->actingAs($student)->get(route('student.lesson.view', $first))->assertOk()
        ->assertSee('<strong>Additional context</strong>', false)->assertSee('data-reading-material', false);
    $document = \Dom\HTMLDocument::createFromString($response->getContent(), LIBXML_NOERROR);
    $next = array_filter(iterator_to_array($document->querySelectorAll('a')), fn ($link) => trim($link->textContent) === 'Next Lesson');
    expect($next)->toHaveCount(1)->and(LessonProgress::where('lesson_id', $first->id)->where('student_id', $student->id)->sole()->status)->toBe('in_progress');
    foreach ($lessons->skip(1) as $lesson) expect($lesson->fresh()->content)->toBe($lesson->content);
});
