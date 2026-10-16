<?php

use App\Models\Certificate;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Quiz;
use App\Models\QuizQuestion;

/*
| Failing a quiz retake must not undo a completed course (Manuscript:
| Functional Requirements "Progress Tracking" and "Certificate Generation").
*/

function quizRetakeSetup(string $enrollmentStatus): array
{
    $teacher = pathwiseUser('teacher');
    $student = pathwiseUser('student');
    $course = pathwiseCourse($teacher, ['status' => 'published']);

    $lesson = Lesson::create([
        'course_id' => $course->id,
        'title' => 'Lesson 1',
        'content' => 'Content',
        'lesson_type' => 'text',
        'lesson_order' => 1,
        'is_preview' => false,
        'is_published' => true,
    ]);

    Lesson::create([
        'course_id' => $course->id,
        'title' => 'Lesson 2',
        'content' => 'Content',
        'lesson_type' => 'text',
        'lesson_order' => 2,
        'is_preview' => false,
        'is_published' => true,
    ]);

    LessonProgress::create([
        'student_id' => $student->id,
        'lesson_id' => $lesson->id,
        'status' => 'completed',
        'completed_at' => now(),
    ]);

    $enrollment = Enrollment::create([
        'student_id' => $student->id,
        'course_id' => $course->id,
        'status' => $enrollmentStatus,
        'enrolled_at' => now(),
        'progress_percentage' => $enrollmentStatus === 'completed' ? 100 : 0,
        'completed_at' => $enrollmentStatus === 'completed' ? now() : null,
    ]);

    $quiz = Quiz::create([
        'course_id' => $course->id,
        'title' => 'Final Quiz',
        'passing_score' => 50,
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

    return [$student, $course, $enrollment, $quiz, $question];
}

test('failing a quiz retake does not downgrade a completed enrollment', function () {
    [$student, $course, $enrollment, $quiz, $question] = quizRetakeSetup('completed');

    Certificate::create([
        'student_id' => $student->id,
        'course_id' => $course->id,
        'certificate_number' => 'PATH-'.now()->year.'-00001',
        'issued_date' => now()->toDateString(),
        'status' => 'issued',
    ]);

    $this->actingAs($student)
        ->post(route('student.quiz.submit', $quiz), ['question_'.$question->id => 'B'])
        ->assertRedirect();

    $enrollment->refresh();

    expect($enrollment->status)->toBe('completed')
        ->and((float) $enrollment->progress_percentage)->toBe(100.0)
        ->and($enrollment->completed_at)->not->toBeNull()
        ->and(Certificate::where('student_id', $student->id)->value('status'))->toBe('issued');
});

test('failing a quiz still updates the progress of an active enrollment', function () {
    [$student, , $enrollment, $quiz, $question] = quizRetakeSetup('active');

    $this->actingAs($student)
        ->post(route('student.quiz.submit', $quiz), ['question_'.$question->id => 'B'])
        ->assertRedirect();

    $enrollment->refresh();

    expect($enrollment->status)->toBe('active')
        ->and((float) $enrollment->progress_percentage)->toBe(50.0);
});
