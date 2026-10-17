<?php

use App\Models\Assignment;
use App\Models\Certificate;
use App\Models\Enrollment;
use App\Models\Submission;
use App\Models\Transaction;

/*
| The administrator's certificate and submission pages and the student's
| payment-proof (receipt) page, restyled to the PathWise design.
*/

function recordPagesSubmission(): Submission
{
    $teacher = pathwiseUser('teacher');
    $course = pathwiseCourse($teacher, ['status' => 'published', 'title' => 'Record Course']);

    $assignment = Assignment::create([
        'course_id' => $course->id,
        'title' => 'Record Essay',
        'submission_type' => 'essay',
        'max_score' => 100,
        'is_published' => true,
    ]);

    return Submission::create([
        'assignment_id' => $assignment->id,
        'student_id' => pathwiseUser('student', ['name' => 'Rita Record'])->id,
        'answer_text' => "line one\n    indented line",
        'status' => 'submitted',
        'submitted_at' => now(),
    ]);
}

test('the administrator can open the submission create and edit forms', function () {
    $submission = recordPagesSubmission();
    $admin = pathwiseUser('admin');
    $teacher = pathwiseUser('teacher', ['name' => 'Tom Teacher']);

    // These pages used to fail: the student list was never passed to them.
    $this->actingAs($admin)
        ->get(route('submissions.create'))
        ->assertOk()
        ->assertSee('Create Submission')
        ->assertSee('Rita Record')
        ->assertDontSee('Tom Teacher');   // only students are offered

    $this->actingAs($admin)
        ->get(route('submissions.edit', $submission))
        ->assertOk()
        ->assertSee('Edit Submission')
        ->assertSee('value="'.$submission->student_id.'" selected', false);
});

test('editing a submission keeps its student even without the student role', function () {
    $submission = recordPagesSubmission();
    $submission->student->roles()->detach();

    $this->actingAs(pathwiseUser('admin'))
        ->get(route('submissions.edit', $submission))
        ->assertOk()
        ->assertSee('value="'.$submission->student_id.'" selected', false);
});

test('the submission list and details pages render', function () {
    $submission = recordPagesSubmission();
    $admin = pathwiseUser('admin');

    $this->actingAs($admin)
        ->get(route('submissions.index'))
        ->assertOk()
        ->assertSee('Rita Record')
        ->assertSee('Record Essay')
        ->assertSee(route('submissions.show', $submission), false)
        ->assertSee(route('submissions.edit', $submission), false);

    $this->actingAs($admin)
        ->get(route('submissions.show', $submission))
        ->assertOk()
        ->assertSee('Record Course')
        ->assertSee("line one\n    indented line", false);
});

test('the submission form shows validation errors and keeps the input', function () {
    $submission = recordPagesSubmission();

    $this->actingAs(pathwiseUser('admin'))
        ->from(route('submissions.create'))
        ->followingRedirects()
        ->post(route('submissions.store'), [
            'assignment_id' => $submission->assignment_id,
            'student_id' => $submission->student_id,
            'answer_text' => 'Kept answer',
            'score' => 150,
            'status' => 'graded',
        ])
        ->assertOk()
        ->assertSee('Please fix the following:')
        ->assertSee('Score cannot exceed the assignment maximum score.')
        ->assertSee('Kept answer');
});

test('the certificate pages render and the form shows validation errors', function () {
    $student = pathwiseUser('student', ['name' => 'Cora Certified']);
    $course = pathwiseCourse(pathwiseUser('teacher'), ['status' => 'published', 'title' => 'Cert Course']);

    Enrollment::create([
        'student_id' => $student->id,
        'course_id' => $course->id,
        'status' => 'completed',
        'enrolled_at' => now()->subMonth(),
        'completed_at' => now(),
        'progress_percentage' => 100,
    ]);

    $certificate = Certificate::create([
        'student_id' => $student->id,
        'course_id' => $course->id,
        'certificate_number' => 'PW-TEST-0001',
        'issued_date' => '2026-06-01',
        'status' => 'issued',
    ]);

    $admin = pathwiseUser('admin');

    $this->actingAs($admin)
        ->get(route('certificates.index'))
        ->assertOk()
        ->assertSee('PW-TEST-0001')
        ->assertSee('Cora Certified')
        ->assertSee('Issued');

    $this->actingAs($admin)
        ->get(route('certificates.edit', $certificate))
        ->assertOk()
        ->assertSee('value="PW-TEST-0001"', false)
        ->assertSee('value="2026-06-01"', false);

    // A second certificate number that is already used is refused, and the
    // form says why instead of failing silently.
    $this->actingAs($admin)
        ->from(route('certificates.create'))
        ->followingRedirects()
        ->post(route('certificates.store'), [
            'student_id' => $student->id,
            'course_id' => $course->id,
            'certificate_number' => 'PW-TEST-0001',
            'issued_date' => '2026-06-02',
            'status' => 'issued',
        ])
        ->assertOk()
        ->assertSee('Please fix the following:')
        ->assertSee('value="PW-TEST-0001"', false);
});

test('the certificate list says so when it is empty', function () {
    $this->actingAs(pathwiseUser('admin'))
        ->get(route('certificates.index'))
        ->assertOk()
        ->assertSee('No certificates found.');
});

test('the receipt page uses the light PathWise style', function () {
    $student = pathwiseUser('student');
    $course = pathwiseCourse(pathwiseUser('teacher'), ['status' => 'published', 'price' => 499]);

    $transaction = Transaction::create([
        'student_id' => $student->id,
        'course_id' => $course->id,
        'transaction_no' => 'TXN-RECEIPT-1',
        'amount' => 499,
        'payment_method' => 'Manual',
        'status' => 'pending',
    ]);

    $html = $this->actingAs($student)
        ->get(route('student.transactions.show', $transaction))
        ->assertOk()
        ->assertSee('TXN-RECEIPT-1')
        ->assertSee('Pending Verification')
        ->assertSee('Upload / Replace Payment Proof')
        ->assertSee('name="payment_proof"', false)
        ->assertSee(route('student.transactions.upload-proof', $transaction), false)
        ->getContent();

    expect($html)->not->toContain('bg-neutral-900')
        ->not->toContain('bg-neutral-800');
});
