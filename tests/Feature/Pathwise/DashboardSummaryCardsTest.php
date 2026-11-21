<?php

use App\Models\Certificate;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\QuizResult;
use App\Models\User;

/*
| Dashboard summary cards as listed in the manuscript:
| Fig. 37 (Teacher): My Courses, Total Lessons, Enrolled Students, Avg. Quiz Score.
| Fig. 38 (Student): Enrolled Courses, Quizzes Taken, Certificates Earned,
| Average Score (plus Average Progress).
*/

function cardsLesson(App\Models\Course $course, int $order): Lesson
{
    return Lesson::create([
        'course_id' => $course->id,
        'title' => 'Lesson '.$order,
        'content' => 'Content',
        'lesson_type' => 'text',
        'lesson_order' => $order,
        'is_preview' => false,
        'is_published' => true,
    ]);
}

function cardsQuizResult(User $student, App\Models\Course $course, float $percentage): void
{
    $quiz = Quiz::firstOrCreate(
        ['course_id' => $course->id, 'title' => 'Checkpoint'],
        ['passing_score' => 75, 'is_published' => true]
    );

    QuizResult::create([
        'student_id' => $student->id,
        'quiz_id' => $quiz->id,
        'score' => (int) $percentage,
        'total_items' => 100,
        'percentage' => $percentage,
        'remarks' => $percentage >= 75 ? 'passed' : 'failed',
        'attempt_number' => 1,
        'completed_at' => now(),
    ]);
}

function cardsEnroll(User $student, App\Models\Course $course, string $status = 'active'): void
{
    Enrollment::create([
        'student_id' => $student->id,
        'course_id' => $course->id,
        'status' => $status,
        'enrolled_at' => now(),
        'progress_percentage' => $status === 'completed' ? 100 : 50,
    ]);
}

/**
 * Card label => value, read from the dashboard HTML.
 */
function cardValues(string $html, array $labels): array
{
    $values = [];

    foreach ($labels as $label) {
        $pattern = '/'.preg_quote($label, '/').'\s*<\/(?:p|div)>\s*(?:<[^>]+>\s*)*?([\d.,]+%?)\s*</s';

        $values[$label] = preg_match($pattern, $html, $match) ? $match[1] : null;
    }

    return $values;
}

test('the teacher dashboard shows the Fig. 37 cards with the teacher\'s own figures', function () {
    $teacher = pathwiseApprovedTeacher();
    $web = pathwiseCourse($teacher, ['status' => 'published']);
    $java = pathwiseCourse($teacher, ['status' => 'draft']);

    cardsLesson($web, 1);
    cardsLesson($web, 2);
    cardsLesson($java, 1);

    [$ana, $ben] = [pathwiseUser('student'), pathwiseUser('student')];
    cardsEnroll($ana, $web);
    cardsEnroll($ana, $java);
    cardsEnroll($ben, $web);

    cardsQuizResult($ana, $web, 80);
    cardsQuizResult($ben, $web, 60);

    // Another teacher's course is not counted.
    $other = pathwiseCourse(pathwiseApprovedTeacher(), ['status' => 'published']);
    cardsLesson($other, 1);
    cardsEnroll(pathwiseUser('student'), $other);
    cardsQuizResult(pathwiseUser('student'), $other, 10);

    $html = $this->actingAs($teacher)
        ->get(route('teacher.dashboard'))
        ->assertOk()
        ->assertSeeInOrder(['My Courses', 'Total Lessons', 'Enrolled Students', 'Avg. Quiz Score'])
        ->assertDontSee('Avg. Class Progress')
        ->assertDontSee('need grading')
        ->getContent();

    expect(cardValues($html, ['My Courses', 'Total Lessons', 'Enrolled Students', 'Avg. Quiz Score']))->toBe([
        'My Courses' => '2',
        'Total Lessons' => '3',
        'Enrolled Students' => '2',      // Ana is counted once
        'Avg. Quiz Score' => '70%',      // (80 + 60) / 2
    ]);
});

test('the teacher dashboard cards link to lessons, students and quiz results', function () {
    $this->actingAs(pathwiseApprovedTeacher())
        ->get(route('teacher.dashboard'))
        ->assertOk()
        ->assertSee('href="'.route('teacher.lessons.index').'"', false)
        ->assertSee('href="'.route('teacher.student-progress.index').'"', false)
        ->assertSee('href="'.route('teacher.quiz-results.index').'"', false);
});

test('the student dashboard shows the Fig. 38 cards including Quizzes Taken', function () {
    $student = pathwiseUser('student');
    $teacher = pathwiseApprovedTeacher();

    $web = pathwiseCourse($teacher, ['status' => 'published']);
    $java = pathwiseCourse($teacher, ['status' => 'published']);

    cardsEnroll($student, $web, 'completed');
    cardsEnroll($student, $java);

    cardsQuizResult($student, $web, 90);
    cardsQuizResult($student, $web, 70);
    cardsQuizResult($student, $java, 80);

    Certificate::create([
        'student_id' => $student->id,
        'course_id' => $web->id,
        'certificate_number' => Certificate::generateNumber(),
        'issued_date' => now()->toDateString(),
        'status' => 'issued',
    ]);

    $html = $this->actingAs($student)
        ->get(route('student.dashboard'))
        ->assertOk()
        ->assertSeeInOrder(['Enrolled Courses', 'Quizzes Taken', 'Certificates Earned', 'Average Score', 'Average Progress'])
        ->assertDontSee('View earned achievements')
        ->getContent();

    expect(cardValues($html, ['Enrolled Courses', 'Quizzes Taken', 'Certificates Earned', 'Average Score']))->toBe([
        'Enrolled Courses' => '2',
        'Quizzes Taken' => '3',
        'Certificates Earned' => '1',
        'Average Score' => '80.0%',
    ]);
});

test('a new student sees zeros on the dashboard cards', function () {
    $html = $this->actingAs(pathwiseUser('student'))
        ->get(route('student.dashboard'))
        ->assertOk()
        ->getContent();

    expect(cardValues($html, ['Enrolled Courses', 'Quizzes Taken', 'Certificates Earned', 'Average Score']))->toBe([
        'Enrolled Courses' => '0',
        'Quizzes Taken' => '0',
        'Certificates Earned' => '0',
        'Average Score' => '0.0%',
    ]);
});
