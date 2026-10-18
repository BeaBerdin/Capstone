<?php

use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\QuizResult;
use Illuminate\Support\Facades\Http;

/*
| Functional Requirement g and the Quiz_Questions `points` field (MR-8):
| each correct answer counts as one point, whatever points the question
| has; the percentage is correct answers ÷ number of questions, and the
| result passes at the quiz's passing score.
*/

beforeEach(function () {
    config(['services.gemini.api_key' => '']);
    Http::fake();
});

/**
 * A quiz with three questions worth 1, 5 and 10 points (correct answer A).
 */
function scoringQuiz(int $passingScore = 60): array
{
    $student = pathwiseUser('student');
    $course = pathwiseCourse(pathwiseUser('teacher'), ['status' => 'published']);

    Enrollment::create([
        'student_id' => $student->id,
        'course_id' => $course->id,
        'status' => 'active',
        'enrolled_at' => now(),
        'progress_percentage' => 0,
    ]);

    $lesson = Lesson::create([
        'course_id' => $course->id,
        'title' => 'Checkpoint',
        'lesson_type' => 'quiz',
        'lesson_order' => 1,
        'is_preview' => false,
        'is_published' => true,
    ]);

    $quiz = Quiz::create([
        'course_id' => $course->id,
        'lesson_id' => $lesson->id,
        'title' => 'Checkpoint',
        'passing_score' => $passingScore,
        'is_published' => true,
    ]);

    $questions = collect([1, 5, 10])->map(fn (int $points, int $index) => QuizQuestion::create([
        'quiz_id' => $quiz->id,
        'question' => 'Question '.($index + 1),
        'option_a' => 'Right',
        'option_b' => 'Wrong',
        'correct_answer' => 'A',
        'points' => $points,
    ]));

    return [$student, $quiz, $questions];
}

function submitAnswers($test, $student, Quiz $quiz, array $answers): QuizResult
{
    $test->actingAs($student)->post(route('student.quiz.submit', $quiz), $answers);

    return QuizResult::where('quiz_id', $quiz->id)->where('student_id', $student->id)->latest('id')->first();
}

test('each correct answer counts one point, whatever the question\'s points', function () {
    [$student, $quiz, $questions] = scoringQuiz();

    // Only the 10-point question is answered correctly.
    $result = submitAnswers($this, $student, $quiz, [
        'question_'.$questions[0]->id => 'B',
        'question_'.$questions[1]->id => 'B',
        'question_'.$questions[2]->id => 'A',
    ]);

    expect($result->score)->toBe(1)                  // not 10
        ->and($result->total_items)->toBe(3)         // not 16
        ->and((float) $result->percentage)->toBe(33.33)
        ->and($result->remarks)->toBe('failed');
});

test('the percentage is correct answers over the number of questions', function () {
    [$student, $quiz, $questions] = scoringQuiz();

    // The 1- and 5-point questions are right, the 10-point one is wrong.
    $result = submitAnswers($this, $student, $quiz, [
        'question_'.$questions[0]->id => 'A',
        'question_'.$questions[1]->id => 'A',
        'question_'.$questions[2]->id => 'B',
    ]);

    expect($result->score)->toBe(2)
        ->and((float) $result->percentage)->toBe(66.67)   // points would give 6 / 16 = 37.5%
        ->and($result->remarks)->toBe('passed');           // passing score 60
});

test('an unanswered question counts as wrong', function () {
    [$student, $quiz, $questions] = scoringQuiz();

    $result = submitAnswers($this, $student, $quiz, [
        'question_'.$questions[0]->id => 'A',
    ]);

    expect($result->score)->toBe(1)
        ->and($result->total_items)->toBe(3);
});

test('a result exactly at the passing score passes', function () {
    [$student, $quiz, $questions] = scoringQuiz(passingScore: 100);

    $result = submitAnswers($this, $student, $quiz, collect($questions)
        ->mapWithKeys(fn ($question) => ['question_'.$question->id => 'A'])
        ->all());

    expect((float) $result->percentage)->toBe(100.0)
        ->and($result->remarks)->toBe('passed');
});
