<?php

use App\Models\Certificate;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Support\Facades\Notification;

/*
| One rule for every certificate button a student sees (dashboard, My
| Courses, course pages): issued -> "View Certificate"; completed but not
| issued -> disabled "Certificate pending" (with the reason); not completed
| -> no certificate button.
*/

beforeEach(function () {
    $this->withoutVite();
    Notification::fake();
});

/**
 * @return array{0: User, 1: Course, 2: Enrollment, 3: App\Models\Assignment}
 */
function statusCourse(string $enrollmentStatus = 'completed', string $title = 'Networking Basics'): array
{
    $teacher = pathwiseApprovedTeacher();
    $student = pathwiseUser('student');
    $course = pathwiseCourse($teacher, ['status' => 'published', 'certificate_available' => true, 'title' => $title]);

    $lesson = Lesson::create([
        'course_id' => $course->id, 'title' => 'Activity 2', 'content' => 'Answer this.', 'lesson_type' => 'activity',
        'lesson_order' => 1, 'is_preview' => false, 'is_published' => true,
    ]);
    $activity = $lesson->assignments()->create([
        'course_id' => $course->id, 'title' => 'Activity 2', 'description' => 'Answer this.',
        'submission_type' => 'essay', 'answer_format' => 'either', 'max_score' => 100,
        'is_activity' => true, 'is_published' => true,
    ]);

    $enrollment = Enrollment::create([
        'course_id' => $course->id, 'student_id' => $student->id, 'status' => $enrollmentStatus,
        'enrolled_at' => now(), 'progress_percentage' => $enrollmentStatus === 'completed' ? 100 : 40,
        'completed_at' => $enrollmentStatus === 'completed' ? now() : null,
    ]);

    if ($enrollmentStatus === 'completed') {
        LessonProgress::create(['student_id' => $student->id, 'lesson_id' => $lesson->id, 'status' => 'completed', 'completed_at' => now()]);
    }

    return [$student, $course, $enrollment, $activity];
}

function issueCertificateFor(User $student, Course $course, string $status = 'issued'): Certificate
{
    return Certificate::create([
        'student_id' => $student->id, 'course_id' => $course->id,
        'certificate_number' => 'PATH-STATUS-'.$student->id.'-'.$course->id,
        'issued_date' => now()->toDateString(), 'status' => $status,
    ]);
}

/*
|--------------------------------------------------------------------------
| Dashboard "Continue Learning" card: the three states
|--------------------------------------------------------------------------
*/

test('dashboard: an issued certificate shows a green "View Certificate" linking to it', function () {
    [$student, $course] = statusCourse('completed');
    $certificate = issueCertificateFor($student, $course);

    $this->actingAs($student)
        ->get(route('student.dashboard'))
        ->assertOk()
        ->assertSee('data-test="certificate-issued"', false)
        ->assertSee('href="'.route('student.certificate.view', $certificate).'"', false)
        ->assertSee('View Certificate')
        ->assertDontSee('data-test="certificate-pending"', false);
});

test('dashboard: a completed course without an issued certificate shows "Certificate pending" with the reason, not a link', function () {
    [$student, $course, , $activity] = statusCourse('completed');
    Submission::create([
        'assignment_id' => $activity->id, 'student_id' => $student->id, 'answer_text' => 'Answer',
        'status' => 'submitted', 'ai_score' => 95, 'ai_evaluation_status' => 'completed', 'submitted_at' => now(),
    ]);

    $html = $this->actingAs($student)
        ->get(route('student.dashboard'))
        ->assertOk()
        ->assertSee('data-test="certificate-pending"', false)
        ->assertSee('Certificate pending')
        ->assertSee('title="Waiting for your teacher to grade: Activity 2"', false)
        ->assertDontSee('data-test="certificate-issued"', false)
        ->getContent();

    // The old green "Certificate" button linking to the certificates page is gone.
    preg_match('/<article.*?<\/article>/s', $html, $card);
    expect($card[0])->not->toContain('href="'.route('student.certificates').'"')
        ->and($card[0])->toContain('Completed');
});

test('dashboard: a revoked certificate counts as not issued', function () {
    [$student, $course] = statusCourse('completed');
    issueCertificateFor($student, $course, 'revoked');

    $this->actingAs($student)
        ->get(route('student.dashboard'))
        ->assertOk()
        ->assertSee('data-test="certificate-pending"', false)
        ->assertDontSee('data-test="certificate-issued"', false)
        ->assertViewHas('certificatesEarned', 0);
});

test('dashboard: a course in progress shows no certificate button, only Continue', function () {
    [$student, $course] = statusCourse('active');

    $this->actingAs($student)
        ->get(route('student.dashboard'))
        ->assertOk()
        ->assertSee('In progress')
        ->assertSee('href="'.route('student.learn.course', $course).'"', false)
        ->assertDontSee('data-test="certificate-pending"', false)
        ->assertDontSee('data-test="certificate-issued"', false);
});

test('dashboard: in-progress courses are listed before completed ones, completed ones are labelled "Completed"', function () {
    [$student, $completedCourse] = statusCourse('completed', 'Finished Course');

    $inProgress = pathwiseCourse(pathwiseApprovedTeacher(), ['status' => 'published', 'title' => 'Ongoing Course']);
    Enrollment::create([
        'course_id' => $inProgress->id, 'student_id' => $student->id, 'status' => 'active',
        'enrolled_at' => now()->subDay(), 'progress_percentage' => 30,
    ]);

    $response = $this->actingAs($student)->get(route('student.dashboard'))->assertOk();

    $response->assertSeeInOrder(['Ongoing Course', 'Finished Course']);
    expect(substr_count($response->getContent(), 'data-test="course-progress-label">Completed<'))->toBe(1);
});

/*
|--------------------------------------------------------------------------
| The same answer on My Courses and the course pages
|--------------------------------------------------------------------------
*/

test('My Courses uses the same rule: pending with the reason, then View Certificate once issued', function () {
    [$student, $course] = statusCourse('completed');

    $this->actingAs($student)
        ->get(route('student.my-courses'))
        ->assertOk()
        ->assertSee('data-test="certificate-pending"', false)
        ->assertSee('title="Submit: Activity 2"', false);

    $certificate = issueCertificateFor($student, $course);

    $this->actingAs($student)
        ->get(route('student.my-courses'))
        ->assertOk()
        ->assertSee('data-test="certificate-issued"', false)
        ->assertSee('href="'.route('student.certificate.view', $certificate).'"', false);
});

test('the course detail page shows "Certificate pending" until issued', function () {
    [$student, $course] = statusCourse('completed');

    $this->actingAs($student)
        ->get(route('student.course.show', $course))
        ->assertOk()
        ->assertSee('data-test="certificate-pending"', false)
        ->assertDontSee('data-test="certificate-issued"', false);

    $certificate = issueCertificateFor($student, $course);

    $this->actingAs($student)
        ->get(route('student.course.show', $course))
        ->assertOk()
        ->assertSee('href="'.route('student.certificate.view', $certificate).'"', false);
});

test('the learning page never shows "View Certificate" before one is issued', function () {
    [$student, $course, , $activity] = statusCourse('completed');
    Submission::create([
        'assignment_id' => $activity->id, 'student_id' => $student->id, 'answer_text' => 'Answer',
        'status' => 'graded', 'score' => 90, 'submitted_at' => now(),
    ]);

    // All requirements met but no certificate issued yet: pending, not a link.
    $this->actingAs($student)
        ->get(route('student.learn.course', $course))
        ->assertOk()
        ->assertSee('data-test="certificate-pending"', false)
        ->assertDontSee('data-test="certificate-issued"', false);

    $certificate = issueCertificateFor($student, $course);

    $this->actingAs($student)
        ->get(route('student.learn.course', $course))
        ->assertOk()
        ->assertSee('href="'.route('student.certificate.view', $certificate).'"', false);
});

test('server access is unchanged: a certificate that is not issued cannot be viewed', function () {
    [$student, $course] = statusCourse('completed');
    $revoked = issueCertificateFor($student, $course, 'revoked');

    $this->actingAs($student)->get(route('student.certificate.view', $revoked))->assertNotFound();
});
