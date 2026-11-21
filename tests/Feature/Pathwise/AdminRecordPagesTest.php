<?php

use App\Models\Department;
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
    $teacher = pathwiseApprovedTeacher();
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

test('the submission list and details pages render, view only', function () {
    $submission = recordPagesSubmission();
    $department = Department::create(['name' => 'Computing']);
    $submission->assignment->course->teacher->update(['department_id' => $department->id]);
    $admin = pathwiseUser('admin', ['department_id' => $department->id]);

    $this->actingAs($admin)
        ->get(route('submissions.index'))
        ->assertOk()
        ->assertSee('Rita Record')
        ->assertSee('Record Essay')
        ->assertSee(route('submissions.show', $submission), false)
        // No create, edit or delete controls.
        ->assertDontSee('Add Submission')
        ->assertDontSee('/submissions/'.$submission->id.'/edit', false)
        ->assertDontSee('Delete submission?', false);

    $this->actingAs($admin)
        ->get(route('submissions.show', $submission))
        ->assertOk()
        ->assertSee('Record Course')
        ->assertSee("line one\n    indented line", false)
        ->assertDontSee('/submissions/'.$submission->id.'/edit', false);
});

test('an administrator cannot create, edit, grade or delete a submission', function () {
    $submission = recordPagesSubmission();
    $submission->update(['file_path' => 'assignment-submissions/1/original.txt', 'score' => 40, 'status' => 'graded']);
    $admin = pathwiseUser('admin');
    $before = $submission->fresh()->getAttributes();

    $payload = [
        'assignment_id' => $submission->assignment_id,
        'student_id' => pathwiseUser('student')->id,
        'answer_text' => 'Injected answer',
        'score' => 100,
        'status' => 'graded',
        // The administrator must never be able to set or change the file path.
        'file_path' => 'payment-proofs/someone-elses-receipt.jpg',
    ];

    // The create and edit pages are gone.
    $this->actingAs($admin)->get('/submissions/create')->assertNotFound();
    $this->actingAs($admin)->get('/submissions/'.$submission->id.'/edit')->assertNotFound();

    // Crafted write requests are refused: these routes no longer exist.
    $this->actingAs($admin)->post('/submissions', $payload)->assertStatus(405);
    $this->actingAs($admin)->put('/submissions/'.$submission->id, $payload)->assertStatus(405);
    $this->actingAs($admin)->patch('/submissions/'.$submission->id, $payload)->assertStatus(405);
    $this->actingAs($admin)->delete('/submissions/'.$submission->id)->assertStatus(405);

    expect(Submission::count())->toBe(1)
        ->and($submission->fresh()->getAttributes())->toBe($before)
        ->and($submission->fresh()->file_path)->toBe('assignment-submissions/1/original.txt');
});

test('the course teacher can still grade a submission', function () {
    $submission = recordPagesSubmission();
    $teacher = $submission->assignment->course->teacher;

    $this->actingAs($teacher)
        ->put(route('teacher.submissions.grade', $submission), ['status' => 'graded', 'score' => 88, 'feedback' => 'Good work.'])
        ->assertRedirect(route('teacher.submissions.show', $submission));

    expect($submission->fresh()->score)->toBe(88)
        ->and($submission->fresh()->status)->toBe('graded');
});

test('the certificate pages render and the form shows validation errors', function () {
    $student = pathwiseUser('student', ['name' => 'Cora Certified']);
    $course = pathwiseCourse(pathwiseApprovedTeacher(), ['status' => 'published', 'title' => 'Cert Course']);

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

    $department = Department::create(['name' => 'Computing']);
    $course->teacher->update(['department_id' => $department->id]);
    $admin = pathwiseUser('admin', ['department_id' => $department->id]);

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
        ->assertSee('Please correct the following:')
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
    $course = pathwiseCourse(pathwiseApprovedTeacher(), ['status' => 'published', 'price' => 499]);

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
