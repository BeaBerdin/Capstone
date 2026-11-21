<?php

use App\Models\Assignment;
use App\Models\Certificate;
use App\Models\CourseInvitation;
use App\Models\Enrollment;
use App\Models\LearningPath;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\QuizResult;
use App\Models\Submission;
use App\Models\Transaction;

/*
| Finalization (Phase 4): one teacher or student must never reach another
| user's records by changing an ID in the URL, and lower roles must never
| reach Administrator or Super Admin pages and actions.
|
| A request is blocked when it answers 403 or 404, or redirects without
| changing anything. Every test also checks that the record is unchanged.
*/

function crossAccountFixture(): array
{
    fakeFileDisks();

    $owner = pathwiseApprovedTeacher();
    $student = pathwiseUser('student');

    $course = pathwiseCourse($owner, ['status' => 'published', 'price' => 0]);

    $lesson = Lesson::create([
        'course_id' => $course->id,
        'title' => 'Owner lesson',
        'content' => 'Text',
        'lesson_type' => 'text',
        'lesson_order' => 1,
        'duration_minutes' => 5,
        'is_preview' => false,
        'is_published' => true,
    ]);

    $quiz = Quiz::create([
        'course_id' => $course->id,
        'lesson_id' => $lesson->id,
        'title' => 'Owner quiz',
        'passing_score' => 50,
        'is_published' => true,
    ]);

    $question = QuizQuestion::create([
        'quiz_id' => $quiz->id,
        'question' => 'Pick A',
        'option_a' => 'A',
        'option_b' => 'B',
        'option_c' => 'C',
        'option_d' => 'D',
        'correct_answer' => 'A',
        'points' => 1,
    ]);

    $enrollment = Enrollment::create([
        'student_id' => $student->id,
        'course_id' => $course->id,
        'status' => 'active',
        'enrolled_at' => now(),
        'progress_percentage' => 0,
    ]);

    $result = QuizResult::create([
        'student_id' => $student->id,
        'quiz_id' => $quiz->id,
        'score' => 1,
        'total_items' => 1,
        'percentage' => 100,
        'remarks' => 'passed',
        'attempt_number' => 1,
        'completed_at' => now(),
    ]);

    $assignment = Assignment::create([
        'course_id' => $course->id,
        'title' => 'Owner assignment',
        'submission_type' => 'file',
        'max_score' => 100,
        'is_published' => true,
    ]);

    $submission = Submission::create([
        'assignment_id' => $assignment->id,
        'student_id' => $student->id,
        'status' => 'submitted',
        'submitted_at' => now(),
    ]);

    $certificate = Certificate::create([
        'student_id' => $student->id,
        'course_id' => $course->id,
        'certificate_number' => 'PW-TEST-'.uniqid(),
        'issued_date' => now()->toDateString(),
        'status' => 'issued',
    ]);

    $transaction = Transaction::create([
        'student_id' => $student->id,
        'course_id' => $course->id,
        'transaction_no' => 'TXN-X-'.uniqid(),
        'amount' => 100,
        'payment_method' => 'Bank Transfer',
        'status' => 'pending',
    ]);

    $invitation = CourseInvitation::create([
        'course_id' => $course->id,
        'created_by' => $owner->id,
        'code' => 'CODE'.strtoupper(uniqid()),
        'expires_at' => now()->addWeek(),
    ]);

    $path = LearningPath::create([
        'name' => 'Owner path',
        'student_id' => $student->id,
        'difficulty_level' => 'beginner',
        'is_generated' => true,
    ]);

    return compact(
        'owner', 'student', 'course', 'lesson', 'quiz', 'question', 'enrollment',
        'result', 'assignment', 'submission', 'certificate', 'transaction',
        'invitation', 'path'
    );
}

function expectBlocked($response): void
{
    $status = $response->getStatusCode();

    expect(in_array($status, [302, 403, 404], true))
        ->toBeTrue("Expected 302/403/404, got {$status}");
}

test('another teacher cannot open or change the owner teacher\'s course pages', function () {
    $f = crossAccountFixture();
    $other = pathwiseApprovedTeacher();

    $this->actingAs($other);

    foreach ([
        route('teacher.courses.edit', $f['course']),
        route('teacher.course.students', $f['course']),
        route('teacher.student.progress', [$f['course'], $f['student']]),
        route('teacher.lessons', $f['course']),
        route('teacher.lessons.create', $f['course']),
        route('teacher.lessons.edit', $f['lesson']),
        route('teacher.quiz.builder', $f['lesson']),
        route('teacher.assignments.create', $f['course']),
        route('teacher.assignments.edit', $f['assignment']),
        route('teacher.submissions.index', $f['assignment']),
        route('teacher.submissions.show', $f['submission']),
        route('teacher.courses.reviews', $f['course']),
        route('files.submission', $f['submission']),
        route('files.receipt', $f['transaction']),
    ] as $url) {
        expect($this->get($url)->getStatusCode())
            ->toBeIn([403, 404], "GET {$url} was not blocked");
    }

    expectBlocked($this->put(route('teacher.courses.update', $f['course']), ['title' => 'Hijacked']));
    expectBlocked($this->post(route('teacher.courses.submit', $f['course'])));
    expectBlocked($this->delete(route('teacher.courses.destroy', $f['course'])));
    expectBlocked($this->post(route('teacher.lessons.store', $f['course']), ['title' => 'X', 'lesson_type' => 'text', 'content' => 'x']));
    expectBlocked($this->put(route('teacher.lessons.update', $f['lesson']), ['title' => 'Hijacked', 'lesson_type' => 'text', 'content' => 'x']));
    expectBlocked($this->delete(route('teacher.lessons.delete', $f['lesson'])));
    expectBlocked($this->post(route('teacher.quiz.save', $f['lesson']), ['title' => 'Hijacked', 'passing_score' => 10]));
    expectBlocked($this->post(route('teacher.quiz.question.store', $f['quiz']), ['question' => 'Q', 'option_a' => 'a', 'option_b' => 'b', 'option_c' => 'c', 'option_d' => 'd', 'correct_answer' => 'A']));
    expectBlocked($this->put(route('teacher.quiz.question.update', [$f['quiz'], $f['question']]), ['question' => 'Hijacked', 'option_a' => 'a', 'option_b' => 'b', 'option_c' => 'c', 'option_d' => 'd', 'correct_answer' => 'B']));
    expectBlocked($this->delete(route('teacher.quiz.question.delete', [$f['quiz'], $f['question']])));
    expectBlocked($this->post(route('teacher.quiz.questions.generate', $f['quiz'])));
    expectBlocked($this->put(route('teacher.assignments.update', $f['assignment']), ['title' => 'Hijacked']));
    expectBlocked($this->delete(route('teacher.assignments.destroy', $f['assignment'])));
    expectBlocked($this->put(route('teacher.submissions.grade', $f['submission']), ['score' => 0, 'feedback' => 'x']));
    expectBlocked($this->post(route('teacher.submissions.ai-evaluate', $f['submission'])));
    expectBlocked($this->delete(route('course-invitations.destroy', $f['invitation'])));
    expectBlocked($this->post(route('course-invitations.send', $f['invitation']), ['email' => 'x@example.com']));
    expectBlocked($this->post(route('teacher.student-removal-requests.store', $f['course']), ['student_id' => $f['student']->id, 'reason' => 'x']));
    expectBlocked($this->post(route('course-invitations.store'), ['course_id' => $f['course']->id]));

    expect($f['course']->fresh())->not->toBeNull()
        ->and($f['course']->fresh()->title)->not->toBe('Hijacked')
        ->and($f['lesson']->fresh())->not->toBeNull()
        ->and($f['lesson']->fresh()->title)->toBe('Owner lesson')
        ->and($f['quiz']->fresh()->title)->toBe('Owner quiz')
        ->and($f['question']->fresh())->not->toBeNull()
        ->and($f['question']->fresh()->question)->toBe('Pick A')
        ->and($f['assignment']->fresh())->not->toBeNull()
        ->and($f['assignment']->fresh()->title)->toBe('Owner assignment')
        ->and($f['submission']->fresh()->score)->toBeNull()
        ->and((int) $f['result']->fresh()->score)->toBe(1)
        ->and($f['invitation']->fresh())->not->toBeNull()
        ->and(CourseInvitation::where('created_by', $other->id)->count())->toBe(0)
        ->and(App\Models\StudentRemovalRequest::count())->toBe(0)
        ->and(Lesson::where('course_id', $f['course']->id)->count())->toBe(1);
});

test('another student cannot open the owner student\'s records', function () {
    $f = crossAccountFixture();
    $other = pathwiseUser('student');

    $this->actingAs($other);

    foreach ([
        route('student.certificate.view', $f['certificate']),
        route('student.certificate.download', $f['certificate']),
        route('student.transactions.show', $f['transaction']),
        route('student.transactions.success', $f['transaction']),
        route('student.transactions.cancel', $f['transaction']),
        route('student.learning-paths.show', $f['path']),
        route('student.lesson.view', $f['lesson']),
        route('student.learn.course', $f['course']),
        route('student.quiz.take', $f['quiz']),
        route('files.receipt', $f['transaction']),
        route('files.submission', $f['submission']),
        route('files.lesson', $f['lesson']),
    ] as $url) {
        $status = $this->get($url)->getStatusCode();

        expect(in_array($status, [302, 403, 404], true))
            ->toBeTrue("GET {$url} answered {$status}");
    }

    expectBlocked($this->post(route('student.quiz.submit', $f['quiz']), ['answers' => [$f['question']->id => 'A']]));
    expectBlocked($this->post(route('student.lesson.complete', $f['lesson'])));
    expectBlocked($this->post(route('student.transactions.upload-proof', $f['transaction']), ['payment_method' => 'Bank Transfer']));

    expect(QuizResult::where('student_id', $other->id)->count())->toBe(0)
        ->and(App\Models\LessonProgress::where('student_id', $other->id)->count())->toBe(0)
        ->and(Enrollment::where('student_id', $other->id)->count())->toBe(0)
        ->and($f['transaction']->fresh()->status)->toBe('pending');
});

test('teachers and students cannot reach administrator or super admin actions', function () {
    $f = crossAccountFixture();

    foreach (['teacher', 'student'] as $role) {
        $this->actingAs(pathwiseRoleBoundaryUser($role));

        foreach ([
            route('admin.dashboard'),
            route('super_admin.dashboard'),
            route('users.index'),
            route('users.create'),
            route('reports.index'),
            route('reports.export.pdf'),
            route('super_admin.transactions.index'),
            route('course-categories.index'),
            route('courses.index'),
            route('enrollments.index'),
            route('quiz-results.index'),
            route('certificate-template.edit'),
            route('ai-recommendations.index'),
            route('learning-paths.index'),
            route('student-progress.index'),
            route('departments.index'),
        ] as $url) {
            expect($this->get($url)->getStatusCode())->toBe(403, "{$role} reached {$url}");
        }

        expect($this->post(route('courses.approve', $f['course']))->getStatusCode())->toBe(403)
            ->and($this->post(route('courses.reject', $f['course']), ['remarks' => 'x'])->getStatusCode())->toBe(403)
            ->and($this->post(route('super_admin.transactions.approve', $f['transaction']))->getStatusCode())->toBe(403)
            ->and($this->post(route('super_admin.transactions.reject', $f['transaction']), ['remarks' => 'x'])->getStatusCode())->toBe(403)
            ->and($this->put(route('users.update-role', $f['student']), ['role' => 'super_admin'])->getStatusCode())->toBe(403)
            ->and($this->patch(route('users.update-status', $f['owner']))->getStatusCode())->toBe(403)
            ->and($this->post(route('users.store'), [
                'name' => 'Sneaky', 'email' => 'sneaky@example.com', 'role' => 'super_admin',
                'password' => 'Password123!', 'password_confirmation' => 'Password123!',
            ])->getStatusCode())->toBe(403)
            ->and($this->delete(route('enrollments.destroy', $f['enrollment']))->getStatusCode())->toBe(403);
    }

    expect($f['transaction']->fresh()->status)->toBe('pending')
        ->and($f['enrollment']->fresh())->not->toBeNull()
        ->and(App\Models\User::where('email', 'sneaky@example.com')->exists())->toBeFalse()
        ->and($f['student']->fresh()->hasRole('super_admin'))->toBeFalse()
        ->and((bool) $f['owner']->fresh()->is_active)->toBeTrue();
});

test('an administrator cannot reach super admin pages or actions', function () {
    $f = crossAccountFixture();

    $this->actingAs(pathwiseUser('admin'));

    foreach ([
        route('super_admin.dashboard'),
        route('users.index'),
        route('users.create'),
        route('reports.index'),
        route('super_admin.transactions.index'),
        route('departments.index'),
    ] as $url) {
        expect($this->get($url)->getStatusCode())->toBe(403, "admin reached {$url}");
    }

    expect($this->post(route('super_admin.transactions.approve', $f['transaction']))->getStatusCode())->toBe(403)
        ->and($this->put(route('users.update-role', $f['student']), ['role' => 'admin'])->getStatusCode())->toBe(403)
        ->and($f['transaction']->fresh()->status)->toBe('pending');
});

test('the super admin create account page opens and keeps input after a validation error', function () {
    $superAdmin = pathwiseUser('super_admin');

    $this->actingAs($superAdmin)
        ->get(route('users.create'))
        ->assertOk()
        ->assertSee('Create Account')
        ->assertSee(route('users.store'), false);

    $this->actingAs($superAdmin)
        ->from(route('users.create'))
        ->post(route('users.store'), [
            'name' => 'New Head',
            'email' => 'not-an-email',
            'role' => 'admin',
            'password' => 'x',
            'password_confirmation' => 'y',
        ])
        ->assertRedirect(route('users.create'))
        ->assertSessionHasErrors(['email', 'password']);

    expect(App\Models\User::where('name', 'New Head')->exists())->toBeFalse();
});
