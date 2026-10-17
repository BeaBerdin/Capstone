<?php

use App\Models\Certificate;
use App\Models\Enrollment;
use App\Models\User;

/*
| My Courses: In Progress, Completed and Cancelled sections with counts.
| Completed courses show the completion date and the certificate; cancelled
| ones show no way into the course (cancelled enrollments give no access).
| The clock is frozen at 2026-06-15 10:00 UTC in TestCase.
*/

function myCoursesEnrollment(User $student, string $title, string $status, array $attributes = []): Enrollment
{
    $course = pathwiseCourse(pathwiseUser('teacher'), array_merge([
        'status' => 'published',
        'title' => $title,
    ], $attributes));

    return Enrollment::create([
        'student_id' => $student->id,
        'course_id' => $course->id,
        'status' => $status,
        'enrolled_at' => now()->subMonth(),
        'completed_at' => $status === 'completed' ? now()->subDays(5) : null,
        'progress_percentage' => $status === 'completed' ? 100 : 40,
    ]);
}

function myCoursesCertificate(Enrollment $enrollment, string $status = 'issued'): Certificate
{
    return Certificate::create([
        'student_id' => $enrollment->student_id,
        'course_id' => $enrollment->course_id,
        'certificate_number' => Certificate::generateNumber(),
        'issued_date' => now()->toDateString(),
        'status' => $status,
    ]);
}

/**
 * The HTML of one My Courses section.
 */
function myCoursesSection(string $html, string $key): string
{
    $start = strpos($html, 'data-course-section="'.$key.'"');

    expect($start)->not->toBeFalse();

    $end = strpos($html, 'data-course-section="', $start + 1);

    return substr($html, $start, $end === false ? null : $end - $start);
}

function myCoursesPage($test, User $student): string
{
    return $test->actingAs($student)
        ->get(route('student.my-courses'))
        ->assertOk()
        ->getContent();
}

test('enrollments are grouped into In Progress, Completed and Cancelled with counts', function () {
    $student = pathwiseUser('student');

    myCoursesEnrollment($student, 'Active One', 'active');
    myCoursesEnrollment($student, 'Active Two', 'active');
    myCoursesEnrollment($student, 'Finished Course', 'completed');
    myCoursesEnrollment($student, 'Dropped Course', 'cancelled');

    // Another student's enrollment is not shown.
    myCoursesEnrollment(pathwiseUser('student'), 'Someone Else', 'active');

    $this->actingAs($student)
        ->get(route('student.my-courses'))
        ->assertOk()
        ->assertSeeInOrder(['In Progress', 'Completed', 'Cancelled'])
        ->assertDontSee('Someone Else')
        ->assertViewHas('sections', fn ($sections) => $sections['active']->count() === 2
            && $sections['completed']->count() === 1
            && $sections['cancelled']->count() === 1);

    $html = myCoursesPage($this, $student);

    expect(myCoursesSection($html, 'active'))
        ->toContain('Active One')
        ->toContain('Active Two')
        ->toMatch('/data-section-count\s*>\s*2\s*</')
        ->not->toContain('Finished Course')
        ->and(myCoursesSection($html, 'completed'))
        ->toContain('Finished Course')
        ->toMatch('/data-section-count\s*>\s*1\s*</')
        ->and(myCoursesSection($html, 'cancelled'))
        ->toContain('Dropped Course')
        ->toMatch('/data-section-count\s*>\s*1\s*</')
        ->not->toContain('Active One');
});

test('a completed course shows its completion date and links to its certificate', function () {
    $student = pathwiseUser('student');
    $enrollment = myCoursesEnrollment($student, 'Finished Course', 'completed', ['certificate_available' => true]);
    $certificate = myCoursesCertificate($enrollment);

    $completed = myCoursesSection(myCoursesPage($this, $student), 'completed');

    expect($completed)
        ->toContain('Jun 10, 2026')   // completed_at: 5 days before the frozen clock
        ->toContain(route('student.certificate.view', $certificate))
        ->toContain('View Certificate')
        ->toContain(route('student.learn.course', $enrollment->course_id));
});

test('a completed course without an issued certificate says the certificate is pending', function () {
    $student = pathwiseUser('student');
    $pending = myCoursesEnrollment($student, 'Pending Certificate', 'completed', ['certificate_available' => true]);
    $revoked = myCoursesEnrollment($student, 'Revoked Certificate', 'completed', ['certificate_available' => true]);
    $revokedCertificate = myCoursesCertificate($revoked, 'revoked');

    $completed = myCoursesSection(myCoursesPage($this, $student), 'completed');

    expect(substr_count($completed, 'Certificate pending'))->toBe(2)
        ->and($completed)->not->toContain('data-certificate-link')
        ->and($completed)->not->toContain(route('student.certificate.view', $revokedCertificate));
});

test('a completed course that offers no certificate links to the course details', function () {
    $student = pathwiseUser('student');
    $enrollment = myCoursesEnrollment($student, 'No Certificate Course', 'completed', ['certificate_available' => false]);

    $completed = myCoursesSection(myCoursesPage($this, $student), 'completed');

    expect($completed)
        ->toContain(route('student.course.show', $enrollment->course_id))
        ->not->toContain('Certificate pending')
        ->not->toContain('View Certificate');
});

test('a cancelled course has no buttons into the course', function () {
    $student = pathwiseUser('student');
    $enrollment = myCoursesEnrollment($student, 'Dropped Course', 'cancelled');

    $html = myCoursesPage($this, $student);
    $cancelled = myCoursesSection($html, 'cancelled');

    expect($cancelled)
        ->toContain('Dropped Course')
        ->toContain('data-enrollment-cancelled')
        ->toContain('Your enrollment was cancelled')
        ->not->toContain('<a ')
        ->and($html)->not->toContain(route('student.learn.course', $enrollment->course_id));
});

test('an empty Completed section shows a friendly message', function () {
    $student = pathwiseUser('student');

    myCoursesEnrollment($student, 'Active One', 'active');

    $html = myCoursesPage($this, $student);

    expect(myCoursesSection($html, 'active'))->not->toContain('data-section-empty')
        ->and(myCoursesSection($html, 'completed'))
        ->toContain('data-section-empty')
        ->toContain('No completed courses yet');
});

test('the Cancelled section is hidden when no enrollment is cancelled', function () {
    $student = pathwiseUser('student');

    myCoursesEnrollment($student, 'Active One', 'active');
    myCoursesEnrollment($student, 'Finished Course', 'completed');

    $html = myCoursesPage($this, $student);

    expect($html)->toContain('data-course-section="active"')
        ->toContain('data-course-section="completed"')
        ->not->toContain('data-course-section="cancelled"');
});

test('an empty In Progress section points to the marketplace', function () {
    $student = pathwiseUser('student');

    myCoursesEnrollment($student, 'Finished Course', 'completed');

    expect(myCoursesSection(myCoursesPage($this, $student), 'active'))
        ->toContain('No courses in progress')
        ->toContain(route('student.marketplace'));
});

test('a student with no enrollments sees the welcome message instead of sections', function () {
    $html = myCoursesPage($this, pathwiseUser('student'));

    expect($html)->toContain("You haven't enrolled in a course yet")
        ->toContain(route('student.marketplace'))
        ->not->toContain('data-course-section=');
});

test('the summary counts in-progress and completed courses', function () {
    $student = pathwiseUser('student');

    myCoursesEnrollment($student, 'Active One', 'active');
    myCoursesEnrollment($student, 'Finished Course', 'completed');
    myCoursesEnrollment($student, 'Dropped Course', 'cancelled');

    $this->actingAs($student)
        ->get(route('student.my-courses'))
        ->assertOk()
        ->assertViewHas('enrollments', fn ($enrollments) => $enrollments->count() === 3)
        ->assertSeeInOrder(['Total Courses', '3', 'In Progress', '1', 'Completed', '1']);
});
