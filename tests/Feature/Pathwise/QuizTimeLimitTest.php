<?php

use App\Models\Enrollment;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\QuizResult;

/*
| Manuscript Table 13: quizzes.time_limit_minutes.
| The time limit is enforced by the server, not only by the browser.
*/

function timedQuizSetup(?int $minutes = 10): array
{
    $teacher = pathwiseUser('teacher');
    $student = pathwiseUser('student');
    $course = pathwiseCourse($teacher, ['status' => 'published']);

    Enrollment::create([
        'student_id' => $student->id,
        'course_id' => $course->id,
        'status' => 'active',
        'enrolled_at' => now(),
        'progress_percentage' => 0,
    ]);

    $quiz = Quiz::create([
        'course_id' => $course->id,
        'title' => 'Timed Quiz',
        'passing_score' => 50,
        'time_limit_minutes' => $minutes,
        'is_published' => true,
    ]);

    $question = QuizQuestion::create([
        'quiz_id' => $quiz->id,
        'question' => 'What does HTML stand for?',
        'option_a' => 'HyperText Markup Language',
        'option_b' => 'High Tech Machine Language',
        'correct_answer' => 'A',
        'points' => 1,
    ]);

    return [$student, $quiz, $question];
}

function quizStartKey(Quiz $quiz): string
{
    return 'quiz_attempts.'.$quiz->id.'.started_at';
}

test('opening a timed quiz shows the countdown and records the start time', function () {
    [$student, $quiz] = timedQuizSetup(10);

    $this->actingAs($student)
        ->get(route('student.quiz.take', $quiz))
        ->assertOk()
        ->assertSee('Time remaining')
        ->assertSee('10:00');

    expect(session(quizStartKey($quiz)))->toBeInt();
});

test('empty options C and D are not shown', function () {
    [$student, $quiz] = timedQuizSetup(10);

    $this->actingAs($student)
        ->get(route('student.quiz.take', $quiz))
        ->assertOk()
        ->assertSee('HyperText Markup Language')
        ->assertDontSee('value="C"', false);
});

test('refreshing the quiz page does not reset the timer', function () {
    [$student, $quiz] = timedQuizSetup(10);

    $this->actingAs($student)->get(route('student.quiz.take', $quiz));
    $firstStart = session(quizStartKey($quiz));

    $this->travel(3)->minutes();

    $this->actingAs($student)
        ->get(route('student.quiz.take', $quiz))
        ->assertSee('07:00');

    expect(session(quizStartKey($quiz)))->toBe($firstStart);
});

test('a submission within the time limit is graded', function () {
    [$student, $quiz, $question] = timedQuizSetup(10);

    $this->actingAs($student)->get(route('student.quiz.take', $quiz));

    $this->travel(5)->minutes();

    $this->actingAs($student)
        ->post(route('student.quiz.submit', $quiz), ['question_'.$question->id => 'A'])
        ->assertRedirect(route('student.learn.course', $quiz->course));

    $result = QuizResult::where('quiz_id', $quiz->id)->where('student_id', $student->id)->first();

    expect($result)->not->toBeNull()
        ->and((float) $result->percentage)->toBe(100.0)
        ->and(session(quizStartKey($quiz)))->toBeNull();
});

test('a submission after the time limit is not recorded', function () {
    [$student, $quiz, $question] = timedQuizSetup(10);

    $this->actingAs($student)->get(route('student.quiz.take', $quiz));

    $this->travel(11)->minutes();

    $this->actingAs($student)
        ->post(route('student.quiz.submit', $quiz), ['question_'.$question->id => 'A'])
        ->assertRedirect(route('student.quiz.take', $quiz))
        ->assertSessionHas('error');

    expect(QuizResult::count())->toBe(0);
});

test('a timed quiz cannot be submitted without opening it first', function () {
    [$student, $quiz, $question] = timedQuizSetup(10);

    $this->actingAs($student)
        ->post(route('student.quiz.submit', $quiz), ['question_'.$question->id => 'A'])
        ->assertRedirect(route('student.quiz.take', $quiz));

    expect(QuizResult::count())->toBe(0);
});

test('a quiz without a time limit works as before', function () {
    [$student, $quiz, $question] = timedQuizSetup(null);

    $this->actingAs($student)
        ->get(route('student.quiz.take', $quiz))
        ->assertOk()
        ->assertDontSee('Time remaining');

    $this->actingAs($student)
        ->post(route('student.quiz.submit', $quiz), ['question_'.$question->id => 'B'])
        ->assertRedirect(route('student.learn.course', $quiz->course));

    expect(QuizResult::count())->toBe(1)
        ->and(QuizResult::first()->remarks)->toBe('failed');
});
