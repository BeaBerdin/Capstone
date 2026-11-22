<?php

use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\QuizResult;

/*
| Create Quiz (List of Modules): the teacher saves the quiz settings and adds
| questions by hand in the Quiz Builder, without Gemini.
*/

function quizLessonFor($teacher, string $status = 'draft'): Lesson
{
    $course = pathwiseCourse($teacher, ['status' => $status]);

    return Lesson::create([
        'course_id' => $course->id,
        'title' => 'Web Basics Quiz',
        'lesson_type' => 'quiz',
        'lesson_order' => 1,
        'duration_minutes' => 15,
        'is_preview' => false,
        'is_published' => true,
    ]);
}

function savedQuizFor(Lesson $lesson): Quiz
{
    return Quiz::create([
        'course_id' => $lesson->course_id,
        'lesson_id' => $lesson->id,
        'title' => 'Web Basics Quiz',
        'passing_score' => 70,
        'time_limit_minutes' => 15,
        'is_published' => true,
    ]);
}

function validQuestion(array $overrides = []): array
{
    return array_merge([
        'question' => 'Which HTML element holds the page title shown in the browser tab?',
        'option_a' => 'body',
        'option_b' => 'title',
        'option_c' => 'h1',
        'option_d' => 'footer',
        'correct_answer' => 'B',
        'points' => 1,
    ], $overrides);
}

function quizBuilderSettings($test, Lesson $lesson, array $overrides = []): array
{
    $revision = $test->get(route('teacher.quiz.builder', $lesson))->assertOk()->viewData('quizRevision');

    return array_replace([
        'revision' => $revision,
        'title' => 'Web Basics Quiz',
        'passing_score' => 70,
    ], $overrides);
}

test('a teacher can save the quiz settings of a quiz lesson', function () {
    $teacher = pathwiseApprovedTeacher();
    $lesson = quizLessonFor($teacher);

    $this->actingAs($teacher);
    $this->post(route('teacher.quiz.save', $lesson), quizBuilderSettings($this, $lesson, [
            'title' => 'Web Basics Quiz',
            'description' => 'Five questions about HTML and CSS.',
            'passing_score' => 70,
            'time_limit_minutes' => 15,
            'is_published' => 1,
        ]))
        ->assertRedirect(route('teacher.quiz.builder', $lesson))
        ->assertSessionHas('quiz_saved', true);

    $quiz = Quiz::where('lesson_id', $lesson->id)->firstOrFail();

    expect($quiz->title)->toBe('Web Basics Quiz')
        ->and($quiz->passing_score)->toBe(70)
        ->and($quiz->time_limit_minutes)->toBe(15)
        ->and((bool) $quiz->is_published)->toBeFalse();
});

test('the quiz settings refuse an invalid passing score', function ($score) {
    $teacher = pathwiseApprovedTeacher();
    $lesson = quizLessonFor($teacher);

    $this->actingAs($teacher);
    $this->post(route('teacher.quiz.save', $lesson), quizBuilderSettings($this, $lesson, [
            'title' => 'Web Basics Quiz',
            'passing_score' => $score,
            'time_limit_minutes' => 15,
        ]))
        ->assertSessionHasErrors('passing_score');

    expect(Quiz::where('lesson_id', $lesson->id)->exists())->toBeFalse();
})->with([0, 101, 'seventy', null]);

test('the quiz settings refuse an invalid time limit', function ($minutes) {
    $teacher = pathwiseApprovedTeacher();
    $lesson = quizLessonFor($teacher);

    $this->actingAs($teacher);
    $this->post(route('teacher.quiz.save', $lesson), quizBuilderSettings($this, $lesson, [
            'title' => 'Web Basics Quiz',
            'passing_score' => 70,
            'time_limit_minutes' => $minutes,
        ]))
        ->assertSessionHasErrors('time_limit_minutes');

    expect(Quiz::where('lesson_id', $lesson->id)->exists())->toBeFalse();
})->with([0, -5, 'fifteen']);

test('the quiz settings require a title', function () {
    $teacher = pathwiseApprovedTeacher();
    $lesson = quizLessonFor($teacher);

    $this->actingAs($teacher);
    $this->post(route('teacher.quiz.save', $lesson), quizBuilderSettings($this, $lesson, ['title' => '', 'passing_score' => 70]))
        ->assertSessionHasErrors('title');
});

test('another teacher cannot save the quiz settings', function () {
    $lesson = quizLessonFor(pathwiseApprovedTeacher());

    $this->actingAs(pathwiseApprovedTeacher())
        ->post(route('teacher.quiz.save', $lesson), ['title' => 'Web Basics Quiz', 'passing_score' => 70])
        ->assertForbidden();

    expect(Quiz::where('lesson_id', $lesson->id)->exists())->toBeFalse();
});

test('a teacher can add a question by hand', function () {
    $teacher = pathwiseApprovedTeacher();
    $quiz = savedQuizFor(quizLessonFor($teacher));

    $this->actingAs($teacher);
    $settings = quizBuilderSettings($this, $quiz->lesson);
    $this->post(route('teacher.quiz.question.store', $quiz), validQuestion() + ['revision' => $settings['revision']])
        ->assertRedirect(route('teacher.quiz.builder', $quiz->lesson_id))
        ->assertSessionHas('success', 'Question added successfully.');

    $question = QuizQuestion::where('quiz_id', $quiz->id)->firstOrFail();

    expect($question->option_b)->toBe('title')
        ->and($question->correct_answer)->toBe('B')
        ->and($question->points)->toBe(1);
});

test('a question needs options A and B', function (string $missing) {
    $teacher = pathwiseApprovedTeacher();
    $quiz = savedQuizFor(quizLessonFor($teacher));

    $this->actingAs($teacher);
    $settings = quizBuilderSettings($this, $quiz->lesson);
    $this->post(route('teacher.quiz.question.store', $quiz), validQuestion([$missing => '']) + ['revision' => $settings['revision']])
        ->assertSessionHasErrors($missing);

    expect(QuizQuestion::where('quiz_id', $quiz->id)->count())->toBe(0);
})->with(['option_a', 'option_b']);

test('a question needs a correct answer of A, B, C or D', function ($answer) {
    $teacher = pathwiseApprovedTeacher();
    $quiz = savedQuizFor(quizLessonFor($teacher));

    $this->actingAs($teacher);
    $settings = quizBuilderSettings($this, $quiz->lesson);
    $this->post(route('teacher.quiz.question.store', $quiz), validQuestion(['correct_answer' => $answer]) + ['revision' => $settings['revision']])
        ->assertSessionHasErrors('correct_answer');

    expect(QuizQuestion::where('quiz_id', $quiz->id)->count())->toBe(0);
})->with(['', 'E']);

test('the correct answer must point to an option that has text', function () {
    $teacher = pathwiseApprovedTeacher();
    $quiz = savedQuizFor(quizLessonFor($teacher));

    $this->actingAs($teacher);
    $settings = quizBuilderSettings($this, $quiz->lesson);
    $this->post(route('teacher.quiz.question.store', $quiz), validQuestion(['option_d' => '', 'correct_answer' => 'D']) + ['revision' => $settings['revision']])
        ->assertSessionHasErrors('correct_answer');

    expect(QuizQuestion::where('quiz_id', $quiz->id)->count())->toBe(0);
});

test('questions cannot be added while the course is pending approval', function () {
    $teacher = pathwiseApprovedTeacher();
    $quiz = savedQuizFor(quizLessonFor($teacher, 'pending'));

    $this->actingAs($teacher)
        ->post(route('teacher.quiz.question.store', $quiz), validQuestion())
        ->assertForbidden();

    expect(QuizQuestion::where('quiz_id', $quiz->id)->count())->toBe(0);
});

test('another teacher cannot add a question', function () {
    $quiz = savedQuizFor(quizLessonFor(pathwiseApprovedTeacher()));

    $this->actingAs(pathwiseApprovedTeacher())
        ->post(route('teacher.quiz.question.store', $quiz), validQuestion())
        ->assertForbidden();

    expect(QuizQuestion::where('quiz_id', $quiz->id)->count())->toBe(0);
});

test('Save Changes persists settings, existing edits and a new question in the same form', function () {
    $teacher = pathwiseApprovedTeacher();
    $lesson = quizLessonFor($teacher);
    $this->actingAs($teacher);
    $settings = quizBuilderSettings($this, $lesson, ['new_question' => validQuestion()]);
    $this->post(route('teacher.quiz.save', $lesson), $settings)->assertSessionHas('quiz_saved', true);

    $quiz = Quiz::where('lesson_id', $lesson->id)->sole();
    $original = $quiz->questions()->sole();
    $view = $this->get(route('teacher.quiz.builder', $lesson))->assertOk()
        ->assertSee('Save Changes')->assertSee('Publish Quiz')->assertSee('All changes saved')
        ->assertDontSee('name="is_published"', false);
    $document = new DOMDocument();
    $document->loadHTML($view->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
    $dom = new DOMXPath($document);
    expect($dom->query('//*[@form="quiz-edit-form" and @name="questions['.$original->id.'][question]"]')->length)->toBe(1)
        ->and($dom->query('//*[@form="quiz-edit-form" and @name="new_question[question]"]')->length)->toBe(1)
        ->and($dom->query('//button[@form="quiz-edit-form" and @name="intent"]')->item(0)->getAttribute('value'))->toBe('save');

    $this->post(route('teacher.quiz.save', $lesson), [
        'revision' => $view->viewData('quizRevision'),
        'intent' => 'save',
        'title' => 'Updated quiz title',
        'description' => 'Updated quiz description.',
        'passing_score' => 85,
        'time_limit_minutes' => 10,
        'questions' => [$original->id => validQuestion(['question' => 'Updated question?', 'points' => 5, 'option_d' => ''])],
        'new_question' => validQuestion(['question' => 'Second question?']),
        'is_published' => 1,
    ])->assertRedirect(route('teacher.quiz.builder', $lesson))->assertSessionHas('quiz_saved', true);

    $this->get(route('teacher.quiz.builder', $lesson))->assertOk()
        ->assertSee('Updated quiz title')->assertSee('Updated question?')->assertSee('Second question?');
    expect($quiz->fresh()->passing_score)->toBe(85)
        ->and($quiz->fresh()->time_limit_minutes)->toBe(10)
        ->and((bool) $quiz->fresh()->is_published)->toBeFalse()
        ->and($original->fresh()->points)->toBe(5)
        ->and($original->fresh()->option_d)->toBeNull()
        ->and($quiz->questions()->count())->toBe(2);
});

test('publishing requires a complete question and only explicit actions change publication', function () {
    $teacher = pathwiseApprovedTeacher();
    $lesson = quizLessonFor($teacher);
    $this->actingAs($teacher);
    $url = route('teacher.quiz.save', $lesson);
    $this->post($url, quizBuilderSettings($this, $lesson, ['intent' => 'publish']))->assertSessionHasErrors('quiz');
    expect(Quiz::count())->toBe(0);

    $this->post($url, quizBuilderSettings($this, $lesson, [
        'intent' => 'publish',
        'new_question' => validQuestion(),
    ]))->assertSessionHas('success', 'Quiz published.');

    $quiz = Quiz::sole();
    $question = $quiz->questions()->sole();
    expect((bool) $quiz->fresh()->is_published)->toBeTrue();
    $this->get(route('teacher.quiz.builder', $lesson))->assertOk()->assertSee('Return to Draft')->assertSee('Published');
    $this->post($url, quizBuilderSettings($this, $lesson, [
        'intent' => 'save', 'questions' => [$question->id => validQuestion()], 'is_published' => 0,
    ]))->assertSessionHas('quiz_saved', true);
    expect((bool) $quiz->fresh()->is_published)->toBeTrue();
    $this->delete(route('teacher.quiz.question.delete', [$quiz, $question]), [
        'revision' => quizBuilderSettings($this, $lesson)['revision'],
    ])->assertSessionHasErrors('quiz');
    expect($quiz->questions()->count())->toBe(1);

    $this->post($url, quizBuilderSettings($this, $lesson, [
        'intent' => 'unpublish', 'questions' => [$question->id => validQuestion()],
    ]))->assertSessionHas('success', 'Quiz returned to draft.');
    expect((bool) $quiz->fresh()->is_published)->toBeFalse();
});

test('stale and tampered edits leave the entire quiz unchanged', function () {
    $teacher = pathwiseApprovedTeacher();
    $lesson = quizLessonFor($teacher);
    $quiz = savedQuizFor($lesson);
    $first = $quiz->questions()->create(validQuestion());
    $otherQuiz = savedQuizFor(quizLessonFor($teacher));
    $foreignQuestion = $otherQuiz->questions()->create(validQuestion(['question' => 'Other quiz']));
    $url = route('teacher.quiz.save', $lesson);
    $this->actingAs($teacher);
    $loaded = quizBuilderSettings($this, $lesson, [
        'title' => 'Stale title', 'questions' => [$first->id => validQuestion()],
    ]);

    $this->put(route('teacher.quiz.question.update', [$quiz, $first]), [
        'revision' => $loaded['revision'],
    ] + validQuestion(['question' => 'An edit from another tab']))->assertSessionHasNoErrors();
    $this->post($url, $loaded + ['intent' => 'unpublish'])->assertSessionHasErrors('quiz');

    $this->post($url, quizBuilderSettings($this, $lesson, [
        'title' => 'Tampered title',
        'questions' => [$foreignQuestion->id => validQuestion()],
    ]))->assertSessionHasErrors('quiz');
    $this->post($url, quizBuilderSettings($this, $lesson, [
        'title' => 'Invalid content',
        'questions' => [$first->id => validQuestion(['correct_answer' => 'D', 'option_d' => ''])],
    ]))->assertSessionHasErrors('questions.'.$first->id.'.correct_answer');
    $this->post($url, ['title' => 'No revision', 'passing_score' => 100])->assertSessionHasErrors('revision');

    expect($quiz->fresh()->title)->toBe('Web Basics Quiz')
        ->and((bool) $quiz->fresh()->is_published)->toBeTrue()
        ->and($first->fresh()->question)->toBe('An edit from another tab')
        ->and($foreignQuestion->fresh()->question)->toBe('Other quiz');
});

test('a linked quiz opens and grades only for an enrolled student with a published lesson and quiz', function () {
    $teacher = pathwiseApprovedTeacher();
    $student = pathwiseUser('student');
    $lesson = quizLessonFor($teacher, 'published');
    $quiz = savedQuizFor($lesson);
    $quiz->update(['is_published' => false, 'time_limit_minutes' => null]);
    $question = $quiz->questions()->create(validQuestion());
    $course = $lesson->course;
    Enrollment::create(['course_id' => $course->id, 'student_id' => $student->id,
        'status' => 'active', 'enrolled_at' => now(), 'progress_percentage' => 0]);
    $take = route('student.quiz.take', $quiz);
    $submit = route('student.quiz.submit', $quiz);
    $answers = ['question_'.$question->id => 'B'];

    $this->actingAs($student)->get($take)->assertForbidden();
    $this->post($submit, $answers)->assertForbidden();
    $this->get(route('student.learn.course', $course))->assertOk()->assertDontSee($take, false);

    $quiz->update(['is_published' => true]);
    $lesson->update(['is_published' => false]);
    $this->get($take)->assertForbidden();
    $this->post($submit, $answers)->assertForbidden();
    expect(QuizResult::count())->toBe(0);

    $lesson->update(['is_published' => true]);
    $this->actingAs(pathwiseUser('student'))->get($take)->assertForbidden();
    $this->actingAs($student)->get($take)->assertOk()->assertSee($question->question);
    $this->get(route('student.learn.course', $course))->assertOk()->assertSee($take, false);
    $this->post($submit, $answers)->assertRedirect(route('student.learn.course', $course));
    expect((float) QuizResult::sole()->percentage)->toBe(100.0);

    $this->actingAs($teacher)->get(route('teacher.quiz.builder', $lesson))->assertOk()
        ->assertDontSee('Save Changes')->assertDontSee('Return to Draft');
    $this->post(route('teacher.quiz.save', $lesson), ['intent' => 'unpublish'])->assertForbidden();
});

test('student quiz access rechecks the publication state after route binding', function (string $action) {
    $teacher = pathwiseApprovedTeacher();
    $student = pathwiseUser('student');
    $lesson = quizLessonFor($teacher, 'published');
    $quiz = savedQuizFor($lesson);
    $quiz->update(['time_limit_minutes' => null]);
    $question = $quiz->questions()->create(validQuestion());
    Enrollment::create(['course_id' => $quiz->course_id, 'student_id' => $student->id,
        'status' => 'active', 'enrolled_at' => now(), 'progress_percentage' => 0]);

    $changed = false;
    Quiz::retrieved(function (Quiz $boundQuiz) use ($quiz, &$changed) {
        if (! $changed && $boundQuiz->id === $quiz->id) {
            $changed = true;
            Quiz::whereKey($quiz->id)->update(['is_published' => false]);
        }
    });

    $this->actingAs($student);
    $response = $action === 'take'
        ? $this->get(route('student.quiz.take', $quiz))
        : $this->post(route('student.quiz.submit', $quiz), ['question_'.$question->id => 'B']);
    $response->assertForbidden();
    expect($changed)->toBeTrue()->and(QuizResult::count())->toBe(0);
})->with(['take', 'submit']);
