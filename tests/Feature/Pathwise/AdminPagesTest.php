<?php

use App\Models\Department;
use App\Models\Quiz;
use App\Models\QuizResult;

/*
| Manuscript Fig. 36 (Department Head: manage quizzes, certificates and
| learning paths). Admin pages must be reachable from the sidebar, show
| real data, and never answer with a server error.
*/

function adminQuizResultSetup(): array
{
    $teacher = pathwiseUser('teacher');
    $student = pathwiseUser('student', ['name' => 'Quiz Taker Student']);
    $course = pathwiseCourse($teacher, ['status' => 'published']);

    $quiz = Quiz::create([
        'course_id' => $course->id,
        'title' => 'HTML Basics Checkpoint',
        'passing_score' => 50,
        'is_published' => true,
    ]);

    QuizResult::create([
        'student_id' => $student->id,
        'quiz_id' => $quiz->id,
        'score' => 1,
        'total_items' => 1,
        'percentage' => 100,
        'remarks' => 'passed',
        'attempt_number' => 1,
        'completed_at' => now(),
    ]);

    return [$teacher, $student, $course, $quiz];
}

test('the admin quiz results page lists attempts from every teacher\'s courses', function () {
    adminQuizResultSetup();

    $this->actingAs(pathwiseUser('admin'))
        ->get(route('quiz-results.index'))
        ->assertOk()
        ->assertSee('HTML Basics Checkpoint')
        ->assertSee('Quiz Taker Student')
        ->assertSee(route('lessons.index'), false);
});

test('a teacher still sees only quiz results from their own courses', function () {
    adminQuizResultSetup();

    $this->actingAs(pathwiseUser('teacher'))
        ->get(route('teacher.quiz-results.index'))
        ->assertOk()
        ->assertDontSee('HTML Basics Checkpoint');
});

test('the course owner still sees their own quiz results', function () {
    [$teacher] = adminQuizResultSetup();

    $this->actingAs($teacher)
        ->get(route('teacher.quiz-results.index'))
        ->assertOk()
        ->assertSee('HTML Basics Checkpoint');
});

test('admin resource URLs without a page no longer cause a server error', function () {
    [, , $course, $quiz] = adminQuizResultSetup();
    $admin = pathwiseUser('admin');

    $urls = [
        '/course-categories/'.$course->category_id,
        '/courses/'.$course->id,
        '/lessons/1',
        '/quizzes/'.$quiz->id,
        '/quiz-questions/1',
        '/quiz-results/1',
        '/enrollments/1',
        '/certificates/1',
        '/ai-recommendations/1',
        '/ai-recommendations/1/edit',
    ];

    // 404 when nothing matches the URL; 405 when the URL only exists for
    // PUT/DELETE (update/destroy). Before, these answered with a 500.
    foreach ($urls as $url) {
        expect($this->actingAs($admin)->get($url)->status())
            ->toBeIn([404, 405], "GET {$url}");
    }
});

test('the admin sidebar links to the admin pages and each one opens', function () {
    // Removal requests are scoped to the Department Head's department.
    $department = Department::create(['name' => 'College of Computing']);
    $admin = pathwiseUser('admin', ['department_id' => $department->id]);

    $pages = [
        'quiz-results.index',
        'enrollments.index',
        'learning-paths.index',
        'certificates.index',
        'admin.student-removal-requests.index',
    ];

    $dashboard = $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();

    foreach ($pages as $page) {
        $dashboard->assertSee('href="'.route($page).'"', false);

        $this->actingAs($admin)->get(route($page))->assertOk();
    }
});
