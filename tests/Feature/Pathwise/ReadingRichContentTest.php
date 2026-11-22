<?php

use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Support\ReadingContent;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

function richReadingFields(array $extra = []): array
{
    return array_replace([
        'title' => 'Formatted reading', 'lesson_type' => 'text', 'lesson_order' => 1,
        'is_published' => 1, 'reading_builder' => 1,
    ], $extra);
}

function richReadingFixture(): array
{
    $teacher = pathwiseApprovedTeacher();
    $course = pathwiseCourse($teacher);
    $lesson = $course->lessons()->create(richReadingFields(['content' => 'Original mirror']));
    $rich = $lesson->sections()->create([
        'type' => 'text', 'body_format' => 'html', 'body' => '<p>Original <strong>meaning</strong></p>', 'position' => 0,
    ]);

    return [$teacher, $course, $lesson, $rich];
}

test('rich sanitizer allows only essential formatting and is idempotent', function () {
    $service = new ReadingContent;
    $source = '<h2>Topic</h2><h3>Detail</h3><p><b>B</b><strong>S</strong><i>I</i><em>E</em><u>U</u><br><code>x &lt; y</code></p><ol><li>One</li></ol><ul><li>Two</li></ul><pre><code>  a &lt; b'."\n".'    next();</code></pre><a href="https://example.test/read" title="Reference">Read</a>';
    $safe = $service->sanitize($source);
    foreach (['h2', 'h3', 'p', 'b', 'strong', 'i', 'em', 'u', 'br', 'code', 'ol', 'ul', 'li', 'pre', 'a'] as $tag) {
        expect($safe)->toContain('<'.$tag);
    }
    expect($safe)->toContain('rel="noopener noreferrer"')
        ->toContain('x &lt; y')
        ->and($service->sanitize($safe))->toBe($safe);
});

test('rich sanitizer removes stored XSS and unapproved attributes', function (string $attack) {
    $safe = (new ReadingContent)->sanitize('<p>Keep this.</p>'.$attack);
    expect($safe)->toContain('<p>Keep this.</p>')
        ->not->toMatch('/<(script|iframe|object|embed|img|svg|math|style|meta|form|input|video|audio|link)\b/i')
        ->not->toMatch('/\s(on[a-z]+|style|class|id|src|srcset|target)\s*=/i')
        ->not->toContain('javascript:', 'vbscript:', 'data:image', 'data:text', 'file:///');
})->with([
    '<script>alert(1)</script>',
    '<p onclick="alert(1)" style="position:fixed" class="overlay" id="page">Words</p>',
    '<a href="javascript:alert(1)">Bad</a>',
    '<a href="java&#x73;cript:alert(1)">Bad</a>',
    "<a href=\"java\nscript:alert(1)\">Bad</a>",
    '<a href="vbscript:msgbox(1)">Bad</a><a href="file:///C:/secret">Bad</a>',
    '<a href="data:text/html;base64,PHNjcmlwdD4=">Bad</a>',
    '<img src="https://example.test/tracker" onerror="alert(1)">',
    '<img src="data:image/png;base64,AAAA"><img src="/storage/secret.png">',
    '<iframe srcdoc="<script>alert(1)</script>"></iframe><object data="javascript:alert(1)"></object><embed src="https://example.test">',
    '<svg><a xlink:href="javascript:alert(1)">SVG</a></svg><math><mtext><img src=x onerror=alert(1)></mtext></math>',
    '<meta http-equiv="refresh" content="0;url=javascript:alert(1)"><style>body{display:none}</style><form><input autofocus onfocus=alert(1)></form>',
    '<noscript><p title="</noscript><img src=x onerror=alert(1)>">bad</p></noscript>',
]);

test('rich links reject relative storage URLs and preserve safe explicit schemes', function () {
    $service = new ReadingContent;
    $safe = $service->sanitize('<a href="/storage/a.pdf">Local</a><a href="https://example.test/%73torage/a.pdf">Public</a><a href="//example.test/file">Relative</a><a href="mailto:test@example.test">Email</a><a href="https://example.test/topic">Topic</a>');
    expect($safe)->not->toContain('href="/storage', 'href="https://example.test/%73torage', 'href="//')
        ->toContain('href="mailto:test&#64;example.test"', 'href="https://example.test/topic"');
});

test('rich extraction retains readable headings lists links unicode and escaped code', function () {
    $html = '<h2>Networks</h2><p>Café &amp; ideas<br>Second line.</p><ol><li>First</li><li>Second</li></ol><ul><li>Outer<ul><li>Inner</li></ul></li></ul><pre><code>if (a &lt; b) {'."\n".'    print("&lt;script&gt;");'."\n".'}</code></pre><p><a href="https://example.test/guide">Guide</a></p>';
    $plain = (new ReadingContent)->plainText($html);
    expect($plain)->toContain("Networks\n\nCafé & ideas\nSecond line.")
        ->toContain("1. First\n2. Second")
        ->toContain("- Outer\n  - Inner")
        ->toContain('if (a < b) {'."\n".'    print("<script>");')
        ->toContain('Guide (https://example.test/guide)')
        ->not->toContain('<h2>', '<p>', '<li>', '<code>', '&lt;');
});

test('rich Reading saves sanitized content and readable AI mirror and renders through sanitizer again', function () {
    $teacher = pathwiseApprovedTeacher();
    $course = pathwiseCourse($teacher);
    $html = '<h2>Glaciers</h2><p onclick="evil()">Ice shapes <strong>valleys</strong>.</p><ol><li>Snow</li><li>Ice</li></ol><pre><code>x &lt; y</code></pre><img src="https://example.test/pixel"><script>evil()</script>';
    $this->actingAs($teacher)->post(route('teacher.lessons.store', $course), richReadingFields([
        'sections' => [['type' => 'text', 'body_format' => 'html', 'caption' => 'Earth science', 'body' => $html]],
    ]))->assertSessionHasNoErrors();
    $lesson = $course->lessons()->sole();
    $section = $lesson->sections()->sole();
    expect($section->body_format)->toBe('html')
        ->and($section->body)->not->toContain('<script', 'onclick', '<img')
        ->and($lesson->content)->toContain('Earth science', 'Glaciers', 'Ice shapes valleys.', "1. Snow\n2. Ice", 'x < y')
        ->not->toContain('<strong>', '<h2>', '<img', 'evil()');
    $course->update(['status' => 'published']);
    $student = pathwiseUser('student');
    Enrollment::create(['student_id' => $student->id, 'course_id' => $course->id, 'status' => 'active']);
    // Even an out-of-band dirty import is sanitized at the render boundary.
    $section->update(['body' => $section->body.'<script>renderAttack()</script>']);
    $this->actingAs($student)->get(route('student.lesson.view', $lesson))->assertOk()
        ->assertSee('<strong>valleys</strong>', false)->assertSee('x &lt; y', false)
        ->assertDontSee('<script>renderAttack()', false)->assertDontSee('onclick="evil()"', false);
    expect(LessonProgress::where('lesson_id', $lesson->id)->sole()->status)->toBe('in_progress');
});

test('plain sections and legacy lessons never become HTML implicitly', function () {
    $teacher = pathwiseApprovedTeacher();
    $course = pathwiseCourse($teacher, ['status' => 'published']);
    $literal = "  <h2>Literal old text</h2>\n\nKeep & preserve.  ";
    $lesson = $course->lessons()->create(richReadingFields(['content' => $literal]));
    $section = $lesson->sections()->create(['type' => 'text', 'body' => $literal]);
    $student = pathwiseUser('student');
    Enrollment::create(['student_id' => $student->id, 'course_id' => $course->id, 'status' => 'active']);
    $this->actingAs($student)->get(route('student.lesson.view', $lesson))->assertOk()
        ->assertSee('&lt;h2&gt;Literal old text&lt;/h2&gt;', false)
        ->assertDontSee('<h2>Literal old text</h2>', false);
    expect($section->fresh()->body)->toBe($literal)->and($section->fresh()->body_format)->toBe('plain');
    $course->update(['status' => 'draft']);
    $this->actingAs($teacher)->put(route('teacher.lessons.update', $lesson), richReadingFields([
        'sections' => [['id' => $section->id, 'type' => 'text', 'body' => $literal]],
    ]))->assertSessionHasNoErrors();
    expect($section->fresh()->body)->toBe($literal);
});

test('legacy administrative edits cannot flatten rich or multiple sections and rejected metadata rolls back', function (bool $rich) {
    fakeFileDisks();
    [$teacher, $course, $lesson, $section] = richReadingFixture();
    if (! $rich) {
        $section->update(['body_format' => 'plain', 'body' => 'First']);
        $lesson->sections()->create(['type' => 'text', 'body' => 'Second', 'position' => 1]);
    }
    $file = $lesson->sections()->create(['type' => 'file', 'file_path' => 'lesson-files/keep.pdf', 'position' => 2]);
    Storage::disk('local')->put($file->file_path, 'Keep private file');
    $before = $lesson->sections()->get()->toArray();
    $this->actingAs(pathwiseUser('super_admin'))->put(route('lessons.update', $lesson), richReadingFields([
        'course_id' => $course->id, 'title' => 'Must roll back', 'content' => 'Flattened replacement',
    ]))->assertSessionHasErrors('content');
    expect($lesson->fresh()->title)->toBe($lesson->title)->and($lesson->fresh()->content)->toBe('Original mirror')
        ->and($lesson->sections()->get()->toArray())->toBe($before);
    Storage::disk('local')->assertExists($file->file_path);
})->with([false, true]);

test('rich metadata saves and Teacher round trips preserve format ordering captions and private files', function () {
    fakeFileDisks();
    [$teacher, $course, $lesson, $section] = richReadingFixture();
    $file = $lesson->sections()->create(['type' => 'file', 'file_path' => 'lesson-files/rich-keep.pdf', 'original_name' => 'guide.pdf', 'caption' => 'Guide', 'position' => 1]);
    Storage::disk('local')->put($file->file_path, '%PDF-1.4');
    $this->actingAs(pathwiseUser('super_admin'))->put(route('lessons.update', $lesson), richReadingFields([
        'course_id' => $course->id, 'content' => $lesson->content, 'title' => 'Metadata only',
    ]))->assertSessionHasNoErrors();
    expect($section->fresh()->body_format)->toBe('html')->and($lesson->sections()->count())->toBe(2);
    $this->actingAs($teacher)->get(route('teacher.lessons.edit', $lesson))->assertOk()
        ->assertSee('data-section-body_format', false)->assertSee('data-reading-editor', false)
        ->assertSee('data-editor-html=', false)->assertDontSee('Formatted content is read-only');
    $this->put(route('teacher.lessons.update', $lesson), richReadingFields([
        'sections' => [
            ['type' => 'file', 'id' => $file->id, 'caption' => 'Guide'],
            ['type' => 'text', 'id' => $section->id, 'body_format' => 'html', 'body' => $section->body, 'caption' => 'Kept heading'],
        ],
    ]))->assertSessionHasNoErrors();
    expect($lesson->fresh()->sections->pluck('id')->all())->toBe([$file->id, $section->id])
        ->and($section->fresh()->body_format)->toBe('html')->and($section->fresh()->caption)->toBe('Kept heading')
        ->and($file->fresh()->file_path)->toBe($file->file_path);
    Storage::disk('local')->assertExists($file->file_path);
});

test('legacy Teacher clients cannot change rich bodies downgrade format or convert lesson types', function () {
    [$teacher, $course, $lesson, $section] = richReadingFixture();
    $this->actingAs($teacher)->put(route('teacher.lessons.update', $lesson), richReadingFields([
        'sections' => [['id' => $section->id, 'type' => 'text', 'body' => 'Changed without format']],
    ]))->assertSessionHasErrors('sections.0.body_format');
    $this->put(route('teacher.lessons.update', $lesson), richReadingFields([
        'sections' => [['id' => $section->id, 'type' => 'text', 'body_format' => 'plain', 'body' => 'Downgraded']],
    ]))->assertSessionHasErrors('sections.0.body_format');
    $this->put(route('teacher.lessons.update', $lesson), richReadingFields([
        'reading_builder' => 0, 'content' => 'Legacy overwrite',
    ]))->assertSessionHasErrors('content');
    $this->put(route('teacher.lessons.update', $lesson), richReadingFields([
        'lesson_type' => 'video', 'video_url' => 'https://example.test/video',
    ]))->assertSessionHasErrors('lesson_type');
    $this->actingAs(pathwiseUser('super_admin'))->put(route('lessons.update', $lesson), richReadingFields([
        'course_id' => $course->id, 'lesson_type' => 'video', 'content' => 'Overwrite',
    ]))->assertSessionHasErrors('lesson_type');
    expect($section->fresh()->body)->toBe('<p>Original <strong>meaning</strong></p>')
        ->and($lesson->fresh()->lesson_type)->toBe('text');
});

test('rich storage refuses excessive input without sanitizer truncation or partial persistence', function () {
    $teacher = pathwiseApprovedTeacher();
    $course = pathwiseCourse($teacher);
    $this->actingAs($teacher)->post(route('teacher.lessons.store', $course), richReadingFields([
        'sections' => [['type' => 'text', 'body_format' => 'html', 'body' => '<p>'.str_repeat('x', 120001).'</p>']],
    ]))->assertSessionHasErrors('sections.0.body');
    expect($course->lessons()->count())->toBe(0);
    $this->post(route('teacher.lessons.store', $course), richReadingFields([
        'sections' => [['type' => 'text', 'body_format' => 'html', 'body' => '<script>evil()</script>']],
    ]))->assertSessionHasErrors('sections.0.body');
    $this->post(route('teacher.lessons.store', $course), richReadingFields([
        'sections' => [['type' => 'text', 'body_format' => 'markdown', 'body' => 'Text']],
    ]))->assertSessionHasErrors('sections.0.body_format');
});

test('body format migration preserves old literal data and refuses rollback before SQL', function () {
    $previous = DB::getDefaultConnection();
    config(['database.connections.reading_format_upgrade' => [
        'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true,
    ]]);
    DB::setDefaultConnection('reading_format_upgrade');
    Schema::clearResolvedInstance('db.schema');
    try {
        Schema::create('lesson_sections', function (Blueprint $table) {
            $table->id(); $table->string('type'); $table->longText('body')->nullable();
            $table->string('file_path')->nullable(); $table->integer('position');
        });
        $original = "  <b>Old literal text</b>\n\nOriginal whitespace  ";
        DB::table('lesson_sections')->insert([
            ['id' => 10, 'type' => 'text', 'body' => $original, 'file_path' => null, 'position' => 3],
            ['id' => 20, 'type' => 'file', 'body' => null, 'file_path' => 'private/keep.pdf', 'position' => 4],
        ]);
        $migration = require database_path('migrations/2026_11_22_090000_add_body_format_to_lesson_sections.php');
        $migration->up();
        expect(DB::table('lesson_sections')->where('id', 10)->value('body'))->toBe($original)
            ->and(DB::table('lesson_sections')->pluck('body_format')->all())->toBe(['plain', 'plain'])
            ->and(DB::table('lesson_sections')->where('id', 20)->value('file_path'))->toBe('private/keep.pdf');
        DB::table('lesson_sections')->where('id', 10)->update(['body_format' => 'html']);
        $db = DB::connection();
        $db->enableQueryLog(); $db->flushQueryLog();
        expect(fn () => $migration->down())->toThrow(RuntimeException::class, 'forward-only');
        expect($db->getQueryLog())->toBe([])
            ->and(DB::table('lesson_sections')->where('id', 10)->value('body_format'))->toBe('html')
            ->and(DB::table('lesson_sections')->count())->toBe(2);
    } finally {
        DB::setDefaultConnection($previous);
        DB::purge('reading_format_upgrade');
        Schema::clearResolvedInstance('db.schema');
    }
});

test('single plain legacy edits stay supported without losing media while headed sections are protected', function () {
    fakeFileDisks();
    [$teacher, $course, $lesson, $section] = richReadingFixture();
    $section->update(['body_format' => 'plain', 'body' => 'Old plain body']);
    $file = $lesson->sections()->create(['type' => 'file', 'file_path' => 'lesson-files/single.pdf', 'position' => 1]);
    Storage::disk('local')->put($file->file_path, 'Kept file');
    $admin = pathwiseUser('super_admin');
    $this->actingAs($admin)->put(route('lessons.update', $lesson), richReadingFields([
        'course_id' => $course->id, 'content' => 'New plain body',
    ]))->assertSessionHasNoErrors();
    expect($section->fresh()->body)->toBe('New plain body')
        ->and($lesson->sections()->pluck('id')->all())->toBe([$section->id, $file->id]);
    $section->update(['caption' => 'Do not flatten this heading']);
    $this->put(route('lessons.update', $lesson), richReadingFields([
        'course_id' => $course->id, 'content' => 'Would flatten the caption',
    ]))->assertSessionHasErrors('content');
    expect($section->fresh()->caption)->toBe('Do not flatten this heading')
        ->and($section->fresh()->body)->toBe('New plain body');
    Storage::disk('local')->assertExists($file->file_path);
});

test('plain section lesson conversion is rejected without deleting sections or their order', function () {
    [$teacher, $course, $lesson, $section] = richReadingFixture();
    $section->update(['body_format' => 'plain', 'body' => 'Old plain body']);
    $second = $lesson->sections()->create(['type' => 'text', 'body' => 'Subsequent section', 'position' => 1]);
    foreach ([$teacher, pathwiseUser('super_admin')] as $user) {
        $route = $user->hasRole('teacher') ? route('teacher.lessons.update', $lesson) : route('lessons.update', $lesson);
        $this->actingAs($user)->put($route, richReadingFields([
            'course_id' => $course->id, 'lesson_type' => 'video', 'video_url' => 'https://example.test/video',
        ]))->assertSessionHasErrors('lesson_type');
    }
    expect($lesson->fresh()->lesson_type)->toBe('text')
        ->and($lesson->sections()->pluck('id')->all())->toBe([$section->id, $second->id]);
});

test('unchanged rich section from an old client retains its format without an implicit downgrade', function () {
    [$teacher, $course, $lesson, $section] = richReadingFixture();
    $this->actingAs($teacher)->put(route('teacher.lessons.update', $lesson), richReadingFields([
        'sections' => [['id' => $section->id, 'type' => 'text', 'body' => $section->body]],
    ]))->assertSessionHasNoErrors();
    expect($section->fresh()->body_format)->toBe('html')
        ->and($section->fresh()->body)->toBe($section->body)
        ->and($lesson->fresh()->content)->toBe('Original meaning');
});

test('rich parsing rejects invalid encoding and excessive nesting instead of truncating content', function () {
    $service = new ReadingContent;
    expect(fn () => $service->sanitize("\xff"))->toThrow(InvalidArgumentException::class)
        ->and(fn () => $service->sanitize(str_repeat('<strong>', 70).'Text'.str_repeat('</strong>', 70)))
        ->toThrow(InvalidArgumentException::class, 'complex');
});

test('rich content never bypasses teacher ownership or section ownership', function () {
    [$teacher, $course, $lesson, $section] = richReadingFixture();
    $fields = richReadingFields([
        'sections' => [['id' => $section->id, 'type' => 'text', 'body_format' => 'html', 'body' => '<p>Unauthorized edit</p>']],
    ]);
    $this->actingAs(pathwiseApprovedTeacher())->put(route('teacher.lessons.update', $lesson), $fields)->assertForbidden();
    $otherLesson = $course->lessons()->create(richReadingFields(['title' => 'Another lesson', 'lesson_order' => 2]));
    $this->actingAs($teacher)->put(route('teacher.lessons.update', $otherLesson), $fields)->assertSessionHasErrors('sections');
    expect($section->fresh()->body)->toBe($section->body)
        ->and($otherLesson->sections()->count())->toBe(0);
});

test('AI quiz generation receives the rich Reading plain-text mirror through mocked HTTP only', function () {
    config(['services.gemini.api_key' => 'test-key', 'services.gemini.model' => 'gemini-2.5-flash']);
    $teacher = pathwiseApprovedTeacher();
    $course = pathwiseCourse($teacher);
    $this->actingAs($teacher)->post(route('teacher.lessons.store', $course), richReadingFields([
        'sections' => [['type' => 'text', 'body_format' => 'html', 'body' => '<h2>Glacial valleys</h2><p>Moving glaciers carve <strong>U-shaped valleys</strong>.</p>']],
    ]))->assertSessionHasNoErrors();
    $quizLesson = $course->lessons()->create(['title' => 'Glaciers quiz', 'lesson_type' => 'quiz', 'lesson_order' => 2]);
    $quiz = \App\Models\Quiz::create(['course_id' => $course->id, 'lesson_id' => $quizLesson->id, 'title' => 'Glaciers quiz', 'passing_score' => 75]);
    $questions = array_map(fn ($question) => [
        'question' => $question, 'option_a' => 'First', 'option_b' => 'Second',
        'option_c' => 'Third', 'option_d' => 'Fourth', 'correct_answer' => 'A',
    ], ['What shape are glacial valleys?', 'How do glaciers move?', 'What forms a valley?']);
    \Illuminate\Support\Facades\Http::fake(['*' => \Illuminate\Support\Facades\Http::response(['steps' => [[
        'type' => 'model_output',
        'content' => [['type' => 'text', 'text' => json_encode(['questions' => $questions])]],
    ]]])]);
    $revision = $this->get(route('teacher.quiz.builder', $quizLesson))->assertOk()->viewData('quizRevision');
    $this->post(route('teacher.quiz.questions.generate', $quiz), [
        'question_count' => 1, 'difficulty' => 'beginner', 'revision' => $revision,
    ])->assertSessionHasNoErrors()->assertSessionHas('success');
    \Illuminate\Support\Facades\Http::assertSent(fn ($request) =>
        str_contains($request->body(), 'Glacial valleys')
        && str_contains($request->body(), 'Moving glaciers carve U-shaped valleys.')
        && ! str_contains($request->body(), '<strong>')
        && ! str_contains($request->body(), '<h2>'));
    expect($quiz->questions()->count())->toBe(1);
});

test('first builder conversion preserves an unchanged legacy lesson body byte for byte', function () {
    $teacher = pathwiseApprovedTeacher();
    $course = pathwiseCourse($teacher);
    $original = "  <h2>Literal legacy markup</h2>\n\n  spaced code\n\nLast paragraph.  ";
    $lesson = $course->lessons()->create(richReadingFields(['content' => $original]));
    $this->actingAs($teacher)->get(route('teacher.lessons.edit', $lesson))->assertOk();
    expect($lesson->fresh()->content)->toBe($original)->and($lesson->sections()->count())->toBe(0);
    $this->put(route('teacher.lessons.update', $lesson), richReadingFields([
        'sections' => [['type' => 'text', 'body' => $original]],
    ]))->assertSessionHasNoErrors();
    expect($lesson->fresh()->content)->toBe($original)
        ->and($lesson->sections()->sole()->body)->toBe($original)
        ->and($lesson->sections()->sole()->body_format)->toBe('plain');
});
