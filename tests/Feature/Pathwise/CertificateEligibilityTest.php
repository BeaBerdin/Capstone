<?php

use App\Models\Assignment;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\Submission;
use App\Models\User;
use App\Support\CertificateEligibility;
use Illuminate\Support\Facades\Notification;

/*
| A certificate is issued only when all published lessons are completed, the
| required quizzes are passed, AND every published activity/assignment has a
| FINAL teacher grade (never the AI suggested score) of at least 75% of its
| max score (assignments have no passing-score setting).
*/

beforeEach(function () {
    $this->withoutVite();
    Notification::fake();
});

/**
 * A published course with one reading lesson and one activity (max 100),
 * and an enrolled student who completed the activity lesson.
 *
 * @return array{0: User, 1: User, 2: Course, 3: Lesson, 4: Assignment}
 */
function certCourse(): array
{
    $teacher = pathwiseApprovedTeacher();
    $student = pathwiseUser('student');
    $course = pathwiseCourse($teacher, ['status' => 'published', 'certificate_available' => true]);

    $reading = Lesson::create([
        'course_id' => $course->id, 'title' => 'Reading 1', 'content' => 'Read me.', 'lesson_type' => 'text',
        'lesson_order' => 1, 'is_preview' => false, 'is_published' => true,
    ]);

    $activityLesson = Lesson::create([
        'course_id' => $course->id, 'title' => 'Activity 2', 'content' => 'Answer this.', 'lesson_type' => 'activity',
        'lesson_order' => 2, 'is_preview' => false, 'is_published' => true,
    ]);

    $activity = $activityLesson->assignments()->create([
        'course_id' => $course->id, 'title' => 'Activity 2', 'description' => 'Answer this.',
        'submission_type' => 'essay', 'answer_format' => 'either', 'max_score' => 100,
        'is_activity' => true, 'is_published' => true,
    ]);

    Enrollment::create([
        'course_id' => $course->id, 'student_id' => $student->id, 'status' => 'active',
        'enrolled_at' => now(), 'progress_percentage' => 0,
    ]);

    LessonProgress::create([
        'student_id' => $student->id, 'lesson_id' => $activityLesson->id,
        'status' => 'completed', 'completed_at' => now(),
    ]);

    return [$teacher, $student, $course, $reading, $activity];
}

function certSubmission(User $student, Assignment $assignment, string $status, ?int $score = null, ?int $aiScore = null): Submission
{
    return Submission::create([
        'assignment_id' => $assignment->id,
        'student_id' => $student->id,
        'answer_text' => 'My answer',
        'status' => $status,
        'score' => $score,
        'ai_score' => $aiScore,
        'ai_evaluation_status' => $aiScore !== null ? 'completed' : 'not_requested',
        'submitted_at' => now(),
    ]);
}

function completeReading(User $student, Lesson $reading)
{
    return test()->actingAs($student)->post(route('student.lesson.complete', $reading));
}

/*
|--------------------------------------------------------------------------
| The rule
|--------------------------------------------------------------------------
*/

test('the passing score is 75% of the max score', function () {
    [, , , , $activity] = certCourse();

    expect(CertificateEligibility::passingScore($activity))->toBe(75.0)
        ->and(CertificateEligibility::passes($activity, 75))->toBeTrue()
        ->and(CertificateEligibility::passes($activity, 74))->toBeFalse()
        ->and(CertificateEligibility::passes($activity, null))->toBeFalse();
});

test('no certificate while an activity has only an AI suggested score', function () {
    [, $student, $course, $reading, $activity] = certCourse();
    certSubmission($student, $activity, 'submitted', null, 95);

    completeReading($student, $reading);

    expect(Certificate::count())->toBe(0)
        ->and(CertificateEligibility::activityBlockers($course, $student->id))
        ->toBe(['Waiting for your teacher to grade: Activity 2']);
});

test('no certificate while a final grade is below the passing score', function () {
    [, $student, $course, $reading, $activity] = certCourse();
    certSubmission($student, $activity, 'graded', 60);

    completeReading($student, $reading);

    expect(Certificate::count())->toBe(0)
        ->and(CertificateEligibility::activityBlockers($course, $student->id))
        ->toBe(['Activity 2: score 60%, passing 75% — resubmit to improve']);
});

test('no certificate while an activity was returned for revision or never submitted', function (string $state, string $message) {
    [, $student, $course, $reading, $activity] = certCourse();

    if ($state === 'returned') {
        certSubmission($student, $activity, 'returned');
    }

    completeReading($student, $reading);

    expect(Certificate::count())->toBe(0)
        ->and(CertificateEligibility::activityBlockers($course, $student->id))->toBe([$message]);
})->with([
    'returned' => ['returned', 'Waiting for your teacher to grade: Activity 2'],
    'never submitted' => ['none', 'Submit: Activity 2'],
]);

test('the certificate is issued when every activity has a passing final grade', function () {
    [, $student, $course, $reading, $activity] = certCourse();
    certSubmission($student, $activity, 'graded', 75);

    completeReading($student, $reading);

    $certificate = Certificate::sole();
    expect($certificate->student_id)->toBe($student->id)
        ->and($certificate->course_id)->toBe($course->id)
        ->and($certificate->status)->toBe('issued');
});

test('standalone published assignments count too; unpublished ones do not', function () {
    [, $student, $course, $reading, $activity] = certCourse();
    certSubmission($student, $activity, 'graded', 90);

    $standalone = Assignment::create([
        'course_id' => $course->id, 'title' => 'Final Essay', 'description' => 'Write.',
        'submission_type' => 'essay', 'answer_format' => 'either', 'max_score' => 50, 'is_published' => true,
    ]);
    Assignment::create([
        'course_id' => $course->id, 'title' => 'Draft Assignment', 'description' => 'Hidden.',
        'submission_type' => 'essay', 'answer_format' => 'either', 'max_score' => 50, 'is_published' => false,
    ]);

    completeReading($student, $reading);
    expect(Certificate::count())->toBe(0)
        ->and(CertificateEligibility::activityBlockers($course, $student->id))->toBe(['Submit: Final Essay']);

    // 38 of 50 = 76% passes (passing is 37.5).
    certSubmission($student, $standalone, 'graded', 38);
    expect(CertificateEligibility::issueIfEligible($course, $student->id))->not->toBeNull();
});

/*
|--------------------------------------------------------------------------
| Every issuing path
|--------------------------------------------------------------------------
*/

test('the teacher\'s passing final grade issues the certificate when it was the last requirement', function () {
    [$teacher, $student, $course, $reading, $activity] = certCourse();
    $submission = certSubmission($student, $activity, 'submitted', null, 98);
    completeReading($student, $reading);
    expect(Certificate::count())->toBe(0);

    // A failing grade does not issue it.
    $this->actingAs($teacher)
        ->put(route('teacher.submissions.grade', $submission), ['status' => 'graded', 'score' => 70])
        ->assertSessionHas('success');
    expect(Certificate::count())->toBe(0);

    // A passing grade does.
    $this->actingAs($teacher)
        ->put(route('teacher.submissions.grade', $submission), ['status' => 'graded', 'score' => 80])
        ->assertSessionHas('success');

    expect(Certificate::where('student_id', $student->id)->where('course_id', $course->id)->where('status', 'issued')->exists())->toBeTrue();
});

test('passing the quiz does not issue the certificate while an activity is not graded', function () {
    [, $student, $course, $reading, $activity] = certCourse();
    LessonProgress::create(['student_id' => $student->id, 'lesson_id' => $reading->id, 'status' => 'completed', 'completed_at' => now()]);
    certSubmission($student, $activity, 'submitted');

    $quizLesson = Lesson::create([
        'course_id' => $course->id, 'title' => 'Checkpoint', 'lesson_type' => 'quiz',
        'lesson_order' => 3, 'is_preview' => false, 'is_published' => true,
    ]);
    $quiz = Quiz::create(['course_id' => $course->id, 'lesson_id' => $quizLesson->id, 'title' => 'Checkpoint', 'passing_score' => 50, 'is_published' => true]);
    $question = QuizQuestion::create(['quiz_id' => $quiz->id, 'question' => 'Q?', 'option_a' => 'Right', 'option_b' => 'Wrong', 'correct_answer' => 'A', 'points' => 1]);

    $this->actingAs($student)->post(route('student.quiz.submit', $quiz), ['question_'.$question->id => 'A']);

    expect(LessonProgress::where('student_id', $student->id)->where('lesson_id', $quizLesson->id)->where('status', 'completed')->exists())->toBeTrue()
        ->and(Certificate::count())->toBe(0);

    // Once the teacher grades it passing, the certificate follows.
    Submission::where('student_id', $student->id)->update(['status' => 'graded', 'score' => 100]);
    expect(CertificateEligibility::issueIfEligible($course, $student->id))->not->toBeNull();
});

test('an administrator cannot issue a certificate manually while an activity is not passed', function () {
    [, $student, $course, , $activity] = certCourse();
    Enrollment::where('student_id', $student->id)->update(['status' => 'completed', 'progress_percentage' => 100, 'completed_at' => now()]);
    certSubmission($student, $activity, 'graded', 50);

    $this->actingAs(pathwiseUser('super_admin'))
        ->post(route('certificates.store'), [
            'student_id' => $student->id,
            'course_id' => $course->id,
            'certificate_number' => 'PATH-MANUAL-1',
            'issued_date' => now()->toDateString(),
            'status' => 'issued',
        ])
        ->assertSessionHasErrors(['status' => 'The certificate cannot be issued yet: Activity 2: score 50%, passing 75% — resubmit to improve.']);

    expect(Certificate::count())->toBe(0);
});

test('an administrator cannot re-issue a revoked certificate while an activity is not passed', function () {
    [, $student, $course, , $activity] = certCourse();
    Enrollment::where('student_id', $student->id)->update(['status' => 'completed', 'progress_percentage' => 100, 'completed_at' => now()]);
    certSubmission($student, $activity, 'submitted', null, 90);
    $certificate = Certificate::create([
        'student_id' => $student->id, 'course_id' => $course->id, 'certificate_number' => 'PATH-OLD-1',
        'issued_date' => now()->toDateString(), 'status' => 'revoked',
    ]);

    $this->actingAs(pathwiseUser('super_admin'))
        ->put(route('certificates.update', $certificate), [
            'student_id' => $student->id,
            'course_id' => $course->id,
            'certificate_number' => 'PATH-OLD-1',
            'issued_date' => now()->toDateString(),
            'status' => 'issued',
        ])
        ->assertSessionHasErrors('status');

    expect($certificate->fresh()->status)->toBe('revoked');
});

test('certificates that are not issued cannot be viewed or downloaded by the student', function () {
    [, $student, $course] = certCourse();
    Enrollment::where('student_id', $student->id)->update(['status' => 'completed', 'progress_percentage' => 100]);
    $certificate = Certificate::create([
        'student_id' => $student->id, 'course_id' => $course->id, 'certificate_number' => 'PATH-REV-1',
        'issued_date' => now()->toDateString(), 'status' => 'revoked',
    ]);

    $this->actingAs($student)->get(route('student.certificate.view', $certificate))->assertNotFound();
    $this->actingAs($student)->get(route('student.certificate.download', $certificate))->assertNotFound();
});

/*
|--------------------------------------------------------------------------
| Resubmission after a failing grade
|--------------------------------------------------------------------------
*/

test('a student may resubmit after a failing final grade, but not after a passing one; the latest graded attempt counts', function () {
    [$teacher, $student, $course, $reading, $activity] = certCourse();
    LessonProgress::create(['student_id' => $student->id, 'lesson_id' => $reading->id, 'status' => 'completed', 'completed_at' => now()]);
    $submission = certSubmission($student, $activity, 'graded', 60);

    $this->actingAs($student)
        ->post(route('student.assignments.submit', $activity), ['answer_text' => 'Improved answer'])
        ->assertSessionHasNoErrors();

    $submission = $submission->fresh();
    expect($submission->status)->toBe('submitted')
        ->and($submission->score)->toBeNull()
        ->and($submission->answer_text)->toBe('Improved answer');

    $this->actingAs($teacher)
        ->put(route('teacher.submissions.grade', $submission), ['status' => 'graded', 'score' => 85])
        ->assertSessionHas('success');

    expect(Certificate::where('student_id', $student->id)->exists())->toBeTrue();

    // Passed: resubmitting is refused again.
    $this->actingAs($student)
        ->post(route('student.assignments.submit', $activity), ['answer_text' => 'Another try'])
        ->assertSessionHasErrors('submission');

    expect($submission->fresh()->score)->toBe(85);
});

/*
|--------------------------------------------------------------------------
| Course page
|--------------------------------------------------------------------------
*/

test('the course page says why the certificate is locked', function () {
    [, $student, $course, $reading, $activity] = certCourse();
    LessonProgress::create(['student_id' => $student->id, 'lesson_id' => $reading->id, 'status' => 'completed', 'completed_at' => now()]);
    certSubmission($student, $activity, 'graded', 60);

    $this->actingAs($student)
        ->get(route('student.learn.course', $course))
        ->assertOk()
        ->assertSee('data-test="certificate-locked"', false)
        ->assertSee('Activity 2: score 60%, passing 75% — resubmit to improve')
        ->assertDontSee('View Certificate');
});

test('the course page shows the certificate link once it is issued', function () {
    [, $student, $course, $reading, $activity] = certCourse();
    certSubmission($student, $activity, 'graded', 100);
    completeReading($student, $reading);

    $this->actingAs($student)
        ->get(route('student.learn.course', $course))
        ->assertOk()
        ->assertSee('View Certificate')
        ->assertDontSee('data-test="certificate-locked"', false);
});

/*
|--------------------------------------------------------------------------
| Super Admin report for certificates already issued
|--------------------------------------------------------------------------
*/

test('already-issued certificates are not changed, and the Super Admin report lists the ones to review', function () {
    [, $student, $course, , $activity] = certCourse();
    certSubmission($student, $activity, 'submitted', null, 90);
    $certificate = Certificate::create([
        'student_id' => $student->id, 'course_id' => $course->id, 'certificate_number' => 'PATH-EARLY-1',
        'issued_date' => now()->toDateString(), 'status' => 'issued',
    ]);

    // An unaffected certificate (all activities passed) is not listed.
    [, $goodStudent, $goodCourse, , $goodActivity] = certCourse();
    certSubmission($goodStudent, $goodActivity, 'graded', 90);
    Certificate::create([
        'student_id' => $goodStudent->id, 'course_id' => $goodCourse->id, 'certificate_number' => 'PATH-GOOD-1',
        'issued_date' => now()->toDateString(), 'status' => 'issued',
    ]);

    // Re-running issuance never changes an existing certificate.
    CertificateEligibility::issueIfEligible($course, $student->id);
    expect($certificate->fresh()->status)->toBe('issued');

    $html = $this->actingAs(pathwiseUser('super_admin'))
        ->get(route('super-admin.certificate-review.index'))
        ->assertOk()
        ->assertSee('PATH-EARLY-1')
        ->assertSee('Waiting for your teacher to grade: Activity 2')
        ->assertDontSee('PATH-GOOD-1')
        ->getContent();

    expect(substr_count($html, 'data-test="certificate-review-row"'))->toBe(1);
});

test('only the Super Admin can open the certificate review report', function (string $role) {
    $user = $role === 'teacher' ? pathwiseApprovedTeacher() : pathwiseUser($role);

    $this->actingAs($user)->get(route('super-admin.certificate-review.index'))->assertForbidden();
})->with(['admin', 'teacher', 'student']);
