<?php

use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Quiz;
use Illuminate\Support\Facades\Storage;

function readingPresentationFixture(): array
{
    $teacher = pathwiseApprovedTeacher();
    $course = pathwiseCourse($teacher, ['status' => 'published']);
    $lesson = $course->lessons()->create([
        'title' => 'Understanding networks', 'lesson_type' => 'text',
        'content' => "Legacy paragraph.\n\nSecond paragraph.", 'lesson_order' => 1,
        'is_published' => true,
    ]);
    $student = pathwiseUser('student');
    $enrollment = Enrollment::create([
        'student_id' => $student->id, 'course_id' => $course->id,
        'status' => 'active', 'enrolled_at' => now(), 'progress_percentage' => 0,
    ]);

    return [$student, $course, $lesson, $enrollment];
}

function readingNextControls(string $html): array
{
    $dom = new DOMDocument;
    @$dom->loadHTML('<?xml encoding="UTF-8">'.$html);

    // The "Next: {lesson title} →" control of the lesson navigation bar.
    return iterator_to_array((new DOMXPath($dom))->query('//a[@data-next-lesson]'));
}

test('Reading textbook keeps escaped text media captions and private downloads in saved order', function () {
    fakeFileDisks();
    [$student, $course, $lesson] = readingPresentationFixture();
    $file = $lesson->sections()->create([
        'type' => 'file', 'caption' => 'Network handout', 'original_name' => 'network-handout.pdf',
        'file_path' => 'lesson-files/presentation/handout.pdf', 'position' => 30,
    ]);
    $text = $lesson->sections()->create([
        'type' => 'text', 'caption' => 'Connecting ideas',
        'body' => "First paragraph.\n\n<script>alert('example')</script>\nThird line.", 'position' => 10,
    ]);
    $image = $lesson->sections()->create([
        'type' => 'image', 'caption' => 'A network diagram',
        'file_path' => 'lesson-files/presentation/diagram.png', 'position' => 20,
    ]);
    Storage::disk('local')->put($file->file_path, '%PDF-1.4 synthetic');
    Storage::disk('local')->put($image->file_path, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII='));

    $response = $this->actingAs($student)->get(route('student.lesson.view', $lesson));
    $response->assertOk()
        ->assertSeeInOrder(['Connecting ideas', 'First paragraph.', 'A network diagram', 'Network handout'])
        ->assertSee('&lt;script&gt;', false)->assertDontSee("<script>alert('example')</script>", false)
        ->assertSee('aria-labelledby="reading-material-title"', false)
        ->assertSee('aria-label="Download Network handout"', false)
        ->assertSee('loading="lazy"', false)
        ->assertSee('class="pw-reading-book__image"', false)
        ->assertSee(route('files.lesson-section', $image))
        ->assertSee(route('files.lesson-section', $file))
        ->assertDontSee($file->file_path);
    expect($lesson->fresh()->sections->pluck('id')->all())->toBe([$text->id, $image->id, $file->id]);
    $this->get(route('files.lesson-section', $file))->assertDownload('network-handout.pdf');
    $this->get(route('files.lesson-section', $image))->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
    $this->actingAs(pathwiseUser('student'))->get(route('files.lesson-section', $file))->assertForbidden();
});

test('legacy Reading stays escaped and viewing never completes a lesson', function () {
    [$student, $course, $lesson, $enrollment] = readingPresentationFixture();
    $lesson->update(['content' => "Literal <b>example</b>.\n\nAnother paragraph."]);
    $this->actingAs($student)->get(route('student.lesson.view', $lesson))
        ->assertOk()->assertSee('data-reading-legacy', false)
        ->assertSee('Literal &lt;b&gt;example&lt;/b&gt;.', false)
        ->assertSee('Another paragraph.')
        ->assertDontSee('<b>example</b>', false);
    $this->get(route('student.lesson.view', $lesson))->assertOk();
    expect(LessonProgress::where('student_id', $student->id)->where('lesson_id', $lesson->id)->sole()->status)->toBe('in_progress')
        ->and($enrollment->fresh()->progress_percentage)->toBe(0)
        ->and($lesson->fresh()->content)->toBe("Literal <b>example</b>.\n\nAnother paragraph.")
        ->and($lesson->fresh()->sections)->toHaveCount(0);
});

test('Reading has one canonical Next Lesson before and after completion with unchanged next-type routing', function (string $nextType, bool $completed) {
    [$student, $course, $lesson] = readingPresentationFixture();
    $next = $course->lessons()->create([
        'title' => 'Next learning step', 'lesson_type' => $nextType,
        'lesson_order' => 2, 'is_published' => true,
    ]);
    $target = route('student.lesson.view', $next);
    if ($nextType === 'quiz') {
        $quiz = Quiz::create(['course_id' => $course->id, 'lesson_id' => $next->id, 'title' => 'Knowledge check', 'is_published' => true]);
        $target = route('student.quiz.take', $quiz);
    }
    if ($completed) {
        LessonProgress::create(['student_id' => $student->id, 'lesson_id' => $lesson->id, 'status' => 'completed', 'completed_at' => now()]);
    }
    $response = $this->actingAs($student)->get(route('student.lesson.view', $lesson))->assertOk();
    $controls = readingNextControls($response->getContent());
    expect($controls)->toHaveCount(1)
        ->and($controls[0]->getAttribute('href'))->toBe($target)
        ->and($controls[0]->hasAttribute('data-next-lesson'))->toBeTrue();
    $response->assertSee('aria-label="Lesson navigation"', false);
    if ($completed) {
        $response->assertSee('Lesson completed')->assertDontSee('Mark as Complete');
    } else {
        $response->assertSee('Mark as Complete');
        expect(LessonProgress::where('lesson_id', $lesson->id)->sole()->status)->toBe('in_progress');
    }
})->with(['text', 'quiz', 'activity'])->with([false, true]);

test('final Reading retains finish navigation and explicit completion action', function () {
    [$student, $course, $lesson] = readingPresentationFixture();
    $response = $this->actingAs($student)->get(route('student.lesson.view', $lesson))->assertOk();
    expect(readingNextControls($response->getContent()))->toHaveCount(0);
    $response->assertSee('data-test="lesson-nav-finish"', false)->assertSee('Mark as Complete');
    $this->post(route('student.lesson.complete', $lesson))->assertRedirect();
    $response = $this->get(route('student.lesson.view', $lesson))->assertOk();
    $response->assertSee('Lesson completed')->assertDontSee('Mark as Complete')->assertSee('data-test="lesson-nav-finish"', false);
    expect(readingNextControls($response->getContent()))->toHaveCount(0)
        ->and(LessonProgress::where('lesson_id', $lesson->id)->sole()->status)->toBe('completed');
});

test('Reading layout defines intrinsic image sizing mobile wrapping and keyboard focus', function () {
    $css = file_get_contents(resource_path('css/app.css'));
    expect($css)->toContain('width: auto; height: auto; max-width: 100%')
        ->toContain('max-width: 74ch')
        ->toContain('@media (max-width: 480px)')
        ->toContain('.pw-reading-book__attachment:focus-visible')
        ->toContain('overflow-wrap: anywhere');
});
test('Reading preview can render a synthetic textbook without a persistent database', function () {
    fakeFileDisks();
    [$student, $course, $lesson] = readingPresentationFixture();
    $lesson->sections()->create([
        'type' => 'text', 'caption' => 'How networks connect us', 'position' => 0,
        'body' => "A network connects devices so they can share information. Each device has a role, from sending a message to helping it reach the right destination.\n\nThink of the internet as many smaller networks working together. A clear route helps information travel reliably between them.",
    ]);
    $lesson->sections()->create([
        'type' => 'image', 'caption' => 'Devices exchange information through a shared connection.',
        'file_path' => 'lesson-files/preview/network.png', 'position' => 1,
    ]);
    $lesson->sections()->create([
        'type' => 'text', 'caption' => 'Pause and reflect', 'position' => 2,
        'body' => "Which devices in your classroom communicate with each other?\n\nKeep this question in mind as you continue. You can return to this reading at any time.",
    ]);
    $lesson->sections()->create([
        'type' => 'file', 'caption' => 'Network study guide', 'original_name' => 'network-study-guide.pdf',
        'file_path' => 'lesson-files/preview/guide.pdf', 'position' => 3,
    ]);
    $course->lessons()->create([
        'title' => 'Explore network types', 'lesson_type' => 'text', 'content' => 'Next topic.',
        'lesson_order' => 2, 'is_published' => true,
    ]);
    $response = $this->actingAs($student)->get(route('student.lesson.view', $lesson))->assertOk();
    $response->assertSee('How networks connect us')->assertSee('Network study guide');
    if (getenv('PATHWISE_READING_PREVIEW') === '1') {
        file_put_contents(storage_path('framework/testing/reading-preview.html'), $response->getContent());
    }
});
