<?php

use App\Models\Assignment;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Submission;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

/*
| A11: payment receipts, assignment submissions and lesson files are kept on
| the private disk and served by PrivateFileController after an access
| check. Course thumbnails and profile photos stay public.
*/

function privateReceipt(array $attributes = []): array
{
    $student = pathwiseUser('student');
    $course = pathwiseCourse(pathwiseUser('teacher'), ['status' => 'published', 'price' => 499]);

    $transaction = Transaction::create(array_merge([
        'student_id' => $student->id,
        'course_id' => $course->id,
        'transaction_no' => 'TXN-PRIVATE-'.uniqid(),
        'amount' => 499,
        'payment_method' => 'Bank Transfer',
        'status' => 'pending',
        'payment_proof' => 'payment-proofs/receipt.png',
    ], $attributes));

    Storage::disk('local')->put('payment-proofs/receipt.png', 'PNG-RECEIPT');

    return [$student, $transaction];
}

function privateSubmission(string $fileName = 'Main.java'): array
{
    $teacher = pathwiseUser('teacher');
    $student = pathwiseUser('student');
    $course = pathwiseCourse($teacher, ['status' => 'published']);

    $assignment = Assignment::create([
        'course_id' => $course->id,
        'title' => 'Hello World',
        'submission_type' => 'code',
        'programming_language' => 'java',
        'max_score' => 100,
        'is_published' => true,
    ]);

    $path = 'assignment-submissions/'.$assignment->id.'/'.$fileName;
    Storage::disk('local')->put($path, 'class Main {}');

    $submission = Submission::create([
        'assignment_id' => $assignment->id,
        'student_id' => $student->id,
        'file_path' => $path,
        'status' => 'submitted',
        'submitted_at' => now(),
    ]);

    return [$teacher, $student, $submission];
}

function privateLesson(array $courseAttributes = [], array $lessonAttributes = []): array
{
    $teacher = pathwiseUser('teacher');
    $course = pathwiseCourse($teacher, array_merge(['status' => 'published'], $courseAttributes));

    $lesson = Lesson::create(array_merge([
        'course_id' => $course->id,
        'title' => 'Reading',
        'lesson_type' => 'document',
        'file_path' => 'lesson-files/'.$course->id.'/notes.pdf',
        'lesson_order' => 1,
        'is_preview' => false,
        'is_published' => true,
    ], $lessonAttributes));

    Storage::disk('local')->put($lesson->file_path, '%PDF-1.4 notes');

    return [$teacher, $course, $lesson];
}

function privateFileEnroll($course, string $status = 'active'): User
{
    $student = pathwiseUser('student');

    Enrollment::create([
        'student_id' => $student->id,
        'course_id' => $course->id,
        'status' => $status,
        'enrolled_at' => now(),
        'progress_percentage' => 0,
    ]);

    return $student;
}

/*
|--------------------------------------------------------------------------
| Receipts
|--------------------------------------------------------------------------
*/

test('the student who paid and the Super Admin can open a receipt', function () {
    fakeFileDisks();
    [$student, $transaction] = privateReceipt();

    $this->actingAs($student)->get(route('files.receipt', $transaction))->assertOk();
    $this->actingAs(pathwiseUser('super_admin'))->get(route('files.receipt', $transaction))->assertOk();
});

test('other users cannot open a receipt', function (string $role) {
    fakeFileDisks();
    [, $transaction] = privateReceipt();

    $this->actingAs(pathwiseUser($role))
        ->get(route('files.receipt', $transaction))
        ->assertForbidden();
})->with(['another student' => 'student', 'teacher' => 'teacher', 'admin' => 'admin']);

test('guests are sent to the login page', function () {
    fakeFileDisks();
    [, $transaction] = privateReceipt();
    [, , $submission] = privateSubmission();
    [, , $lesson] = privateLesson();

    $this->get(route('files.receipt', $transaction))->assertRedirect(route('login'));
    $this->get(route('files.submission', $submission))->assertRedirect(route('login'));
    $this->get(route('files.lesson', $lesson))->assertRedirect(route('login'));
});

/*
|--------------------------------------------------------------------------
| Submissions
|--------------------------------------------------------------------------
*/

test('the student, the course teacher and administrators can open a submission', function () {
    fakeFileDisks();
    [$teacher, $student, $submission] = privateSubmission();

    foreach ([$student, $teacher, pathwiseUser('admin')] as $user) {
        $this->actingAs($user)->get(route('files.submission', $submission))->assertOk();
    }
});

test('other users cannot open a submission', function (string $role) {
    fakeFileDisks();
    [, , $submission] = privateSubmission();

    $this->actingAs(pathwiseUser($role))
        ->get(route('files.submission', $submission))
        ->assertForbidden();
})->with(['another student' => 'student', 'another teacher' => 'teacher', 'Super Admin' => 'super_admin']);

/*
|--------------------------------------------------------------------------
| Lesson files
|--------------------------------------------------------------------------
*/

test('students with an active or completed enrollment can open a lesson file', function (string $status) {
    fakeFileDisks();
    [, $course, $lesson] = privateLesson();

    $this->actingAs(privateFileEnroll($course, $status))
        ->get(route('files.lesson', $lesson))
        ->assertOk();
})->with(['active', 'completed']);

test('the course teacher and administrators can open a lesson file of an unpublished course', function () {
    fakeFileDisks();
    [$teacher, , $lesson] = privateLesson(['status' => 'draft'], ['is_published' => false]);

    $this->actingAs($teacher)->get(route('files.lesson', $lesson))->assertOk();
    $this->actingAs(pathwiseUser('admin'))->get(route('files.lesson', $lesson))->assertOk();
});

test('a lesson file is closed to students without access', function (string $case) {
    fakeFileDisks();

    [, $course, $lesson] = match ($case) {
        'unpublished course' => privateLesson(['status' => 'draft']),
        'unpublished lesson' => privateLesson([], ['is_published' => false]),
        default => privateLesson(),
    };

    $student = match ($case) {
        'not enrolled' => pathwiseUser('student'),
        'cancelled enrollment' => privateFileEnroll($course, 'cancelled'),
        default => privateFileEnroll($course),
    };

    $this->actingAs($student)
        ->get(route('files.lesson', $lesson))
        ->assertForbidden();
})->with(['not enrolled', 'cancelled enrollment', 'unpublished course', 'unpublished lesson']);

test('the enrolled lesson-file endpoint still requires enrollment for preview lessons', function () {
    fakeFileDisks();
    [, , $lesson] = privateLesson([], ['is_preview' => true]);

    $this->actingAs(pathwiseUser('student'))
        ->get(route('files.lesson', $lesson))
        ->assertForbidden();
});

test('the Super Admin and other teachers cannot open a lesson file', function (string $role) {
    fakeFileDisks();
    [, , $lesson] = privateLesson();

    $this->actingAs(pathwiseUser($role))
        ->get(route('files.lesson', $lesson))
        ->assertForbidden();
})->with(['Super Admin' => 'super_admin', 'another teacher' => 'teacher']);

/*
|--------------------------------------------------------------------------
| Responses
|--------------------------------------------------------------------------
*/

test('PDFs and images open in the browser and are not cached', function () {
    fakeFileDisks();
    [$student, $transaction] = privateReceipt();
    [$teacher, , $lesson] = privateLesson();

    $receipt = $this->actingAs($student)->get(route('files.receipt', $transaction));

    $receipt->assertOk()
        ->assertHeader('Content-Type', 'image/png')
        ->assertHeader('X-Content-Type-Options', 'nosniff');
    expect($receipt->headers->get('Content-Disposition'))->toStartWith('inline')
        ->and($receipt->headers->get('Cache-Control'))->toContain('no-store')->toContain('private')
        ->and($receipt->streamedContent())->toBe('PNG-RECEIPT');

    $pdf = $this->actingAs($teacher)->get(route('files.lesson', $lesson));

    $pdf->assertHeader('Content-Type', 'application/pdf');
    expect($pdf->headers->get('Content-Disposition'))->toStartWith('inline');
});

test('other file types are downloaded, never shown in the browser', function (string $fileName) {
    fakeFileDisks();
    [$teacher, , $submission] = privateSubmission($fileName);

    $response = $this->actingAs($teacher)->get(route('files.submission', $submission));

    $response->assertOk()
        ->assertHeader('Content-Type', 'application/octet-stream')
        ->assertHeader('X-Content-Type-Options', 'nosniff');
    expect($response->headers->get('Content-Disposition'))->toStartWith('attachment')
        ->and($response->headers->get('Cache-Control'))->toContain('no-store');
})->with(['Main.java', 'page.html', 'drawing.svg', 'report.docx']);

test('a file not moved yet is still served from the public disk', function () {
    fakeFileDisks();
    [$teacher, , $submission] = privateSubmission();

    Storage::disk('local')->delete($submission->file_path);
    Storage::disk('public')->put($submission->file_path, 'OLD-PUBLIC-COPY');

    $response = $this->actingAs($teacher)->get(route('files.submission', $submission));

    $response->assertOk();
    expect($response->streamedContent())->toBe('OLD-PUBLIC-COPY');
});

test('a missing file gives 404 after the access check', function () {
    fakeFileDisks();
    [$teacher, $student, $submission] = privateSubmission();

    Storage::disk('local')->delete($submission->file_path);

    $this->actingAs($teacher)->get(route('files.submission', $submission))->assertNotFound();
    $this->actingAs(pathwiseUser('student'))->get(route('files.submission', $submission))->assertForbidden();

    [$owner, $transaction] = privateReceipt(['payment_proof' => null]);
    $this->actingAs($owner)->get(route('files.receipt', $transaction))->assertNotFound();
});

test('the private disk is not served by Laravel directly', function () {
    expect(config('filesystems.disks.local.serve'))->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| Uploads go to the private disk
|--------------------------------------------------------------------------
*/

test('an uploaded receipt is stored on the private disk', function () {
    fakeFileDisks();
    config(['services.paymongo.payment_methods' => 'gcash,card']);

    [$student, $transaction] = privateReceipt(['payment_proof' => null, 'payment_method' => 'Manual']);

    $this->actingAs($student)
        ->post(route('student.transactions.upload-proof', $transaction), [
            'payment_method' => 'GCash',
            'payment_proof' => UploadedFile::fake()->image('receipt.png'),
        ])
        ->assertSessionHasNoErrors();

    $path = $transaction->fresh()->payment_proof;

    expect($path)->toStartWith('payment-proofs/');
    Storage::disk('local')->assertExists($path);
    Storage::disk('public')->assertMissing($path);
});

test('an uploaded submission is stored on the private disk and replacing it removes the old file from both disks', function () {
    fakeFileDisks();
    Notification::fake();

    [, $student, $submission] = privateSubmission();
    $assignment = $submission->assignment;
    Enrollment::create([
        'student_id' => $student->id,
        'course_id' => $assignment->course_id,
        'status' => 'active',
        'enrolled_at' => now(),
        'progress_percentage' => 0,
    ]);

    // The earlier file was uploaded before A11 and is still public.
    $oldPath = $submission->file_path;
    Storage::disk('public')->put($oldPath, 'class Old {}');

    $this->actingAs($student)
        ->post(route('student.assignments.submit', $assignment), [
            'submission_file' => UploadedFile::fake()->createWithContent('Main.java', 'class New {}'),
        ])
        ->assertSessionHasNoErrors();

    $newPath = $submission->fresh()->file_path;

    expect($newPath)->not->toBe($oldPath);
    Storage::disk('local')->assertExists($newPath);
    Storage::disk('public')->assertMissing($newPath);
    Storage::disk('local')->assertMissing($oldPath);
    Storage::disk('public')->assertMissing($oldPath);
});

test('an uploaded lesson file is stored on the private disk and deleting the lesson removes it', function () {
    fakeFileDisks();

    $teacher = pathwiseUser('teacher');
    $course = pathwiseCourse($teacher, ['status' => 'draft']);

    $this->actingAs($teacher)
        ->post(route('teacher.lessons.store', $course), [
            'title' => 'Reading',
            'lesson_type' => 'document',
            'lesson_file' => UploadedFile::fake()->create('notes.pdf', 10, 'application/pdf'),
            'lesson_order' => 1,
            'is_published' => true,
        ])
        ->assertSessionHasNoErrors();

    $lesson = Lesson::sole();

    expect($lesson->file_path)->toStartWith('lesson-files/'.$course->id.'/');
    Storage::disk('local')->assertExists($lesson->file_path);
    Storage::disk('public')->assertMissing($lesson->file_path);

    $this->actingAs($teacher)->delete(route('teacher.lessons.delete', $lesson));

    expect(Lesson::count())->toBe(0);
    Storage::disk('local')->assertMissing($lesson->file_path);
});

/*
|--------------------------------------------------------------------------
| Links in the pages
|--------------------------------------------------------------------------
*/

test('pages link to the private file routes, not to /storage', function () {
    fakeFileDisks();
    [$student, $transaction] = privateReceipt();
    [$teacher, , $submission] = privateSubmission();
    [$lessonTeacher, $course, $lesson] = privateLesson();

    $this->actingAs($student)
        ->get(route('student.transactions.show', $transaction))
        ->assertSee(route('files.receipt', $transaction))
        ->assertDontSee('storage/payment-proofs');

    $this->actingAs(pathwiseUser('super_admin'))
        ->get(route('super_admin.transactions.index'))
        ->assertSee(route('files.receipt', $transaction))
        ->assertDontSee('storage/payment-proofs');

    $this->actingAs($teacher)
        ->get(route('teacher.submissions.show', $submission))
        ->assertSee(route('files.submission', $submission))
        ->assertDontSee('storage/assignment-submissions');

    $this->actingAs(pathwiseUser('admin'))
        ->get(route('submissions.show', $submission))
        ->assertSee(route('files.submission', $submission))
        ->assertDontSee('storage/assignment-submissions');

    $this->actingAs(privateFileEnroll($course))
        ->get(route('student.lesson.view', $lesson))
        ->assertSee(route('files.lesson', $lesson))
        ->assertDontSee('storage/lesson-files');

    [$draftTeacher, , $draftLesson] = privateLesson(['status' => 'draft']);

    $this->actingAs($draftTeacher)
        ->get(route('teacher.lessons.edit', $draftLesson))
        ->assertSee(route('files.lesson', $draftLesson))
        ->assertDontSee('storage/lesson-files');
});
