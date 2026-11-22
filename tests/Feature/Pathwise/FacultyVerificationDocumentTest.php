<?php

use App\Models\StudentApplication;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

/*
| Teacher registration requires one "School-Issued Faculty Verification
| Document" (Faculty ID or any official school-issued document confirming
| faculty status): PDF, JPG or PNG up to 5 MB, real MIME checked. It is kept
| on the private disk in the Study Load file columns of the teacher's review
| (no migration) and served only to the Super Admin (EDP). The EDP cannot
| approve a teacher review without one; rejecting is still allowed.
*/

beforeEach(function () {
    $this->seed(\Database\Seeders\RoleSeeder::class);
    $this->withoutVite();
    fakeFileDisks();
    Notification::fake();
});

function facultyDocRegistration(array $overrides = []): array
{
    return array_merge([
        'first_name' => 'Maria',
        'last_name' => 'Santos',
        'email' => 'maria.teacher@example.org',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'teacher',
        'teacher_id' => teacherIdUpload(),
    ], $overrides);
}

function facultyDocRegisteredReview(): StudentApplication
{
    test()->post(route('register.store'), facultyDocRegistration())->assertSessionHasNoErrors();
    auth()->guard('web')->logout();

    return StudentApplication::where('applicant_role', StudentApplication::TEACHER)->sole();
}

function facultyDocReject(StudentApplication $review): void
{
    test()->actingAs(pathwiseUser('super_admin'))
        ->post(route('super-admin.student-applications.reject', $review), [
            'rejection_reason' => 'The photo of the document is too blurry to read.',
        ])->assertSessionHas('success');
}

/*
|--------------------------------------------------------------------------
| Registration
|--------------------------------------------------------------------------
*/

test('the registration form shows the manager-approved label and help text, only for Teacher', function () {
    $html = $this->get(route('register'))->getContent();

    preg_match('/<input[^>]*name="teacher_id"[^>]*>/s', $html, $input);

    expect($html)->toContain('Upload School-Issued Faculty Verification Document')
        ->and($html)->toContain('Upload your Faculty ID or any official school-issued document confirming your faculty status.')
        ->and($html)->toContain('PDF, JPG or PNG, up to 5 MB.')
        ->and($html)->toContain('var maxBytes = 5120 * 1024;')
        ->and($html)->toContain('data-teacher-privacy-notice')
        ->and($html)->not->toContain('Teacher ID')
        ->and($html)->toMatch('/data-teacher-id-section\s+hidden/')
        ->and($input[0])->toContain('disabled')
        ->and($html)->not->toContain('type="checkbox"');

    $teacherHtml = $this->withSession(['_old_input' => ['role' => 'teacher']])->get(route('register'))->getContent();
    preg_match('/<input[^>]*name="teacher_id"[^>]*>/s', $teacherHtml, $teacherInput);

    expect($teacherInput[0])->toContain('required')->and($teacherInput[0])->not->toContain('disabled');
});

test('teacher registration fails without a valid document and nothing is saved', function (array $overrides, string $message) {
    $this->post(route('register.store'), facultyDocRegistration($overrides))
        ->assertSessionHasErrors(['teacher_id' => $message]);

    $this->assertGuest();
    expect(User::count())->toBe(0)
        ->and(StudentApplication::count())->toBe(0)
        ->and(Storage::disk('local')->allFiles())->toBe([])
        ->and(Storage::disk('public')->allFiles())->toBe([]);
})->with([
    'missing' => [['teacher_id' => null], 'Please upload your Faculty Verification Document (PDF, JPG or PNG).'],
    'Word file' => [['teacher_id' => UploadedFile::fake()->create('doc.docx', 100, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document')], 'The Faculty Verification Document must be a PDF, JPG or PNG file.'],
    'renamed .jpg that is not an image' => [['teacher_id' => UploadedFile::fake()->create('doc.jpg', 100, 'application/x-msdownload')], 'The Faculty Verification Document must be a PDF, JPG or PNG file.'],
    'GIF image' => [['teacher_id' => UploadedFile::fake()->create('doc.gif', 100, 'image/gif')], 'The Faculty Verification Document must be a PDF, JPG or PNG file.'],
    'over 5 MB' => [['teacher_id' => UploadedFile::fake()->create('doc.pdf', 5121, 'application/pdf')], StudentApplication::FACULTY_DOCUMENT_TOO_LARGE_MESSAGE],
]);

test('a document of exactly 5 MB, a PDF or a PNG is accepted', function (UploadedFile $file) {
    $this->post(route('register.store'), facultyDocRegistration(['teacher_id' => $file]))
        ->assertSessionHasNoErrors();

    expect(StudentApplication::sole()->hasStudyLoad())->toBeTrue();
})->with([
    '5 MB PDF' => [fn () => UploadedFile::fake()->create('doc.pdf', 5120, 'application/pdf')],
    'PNG' => [fn () => UploadedFile::fake()->image('doc.png')],
]);

test('the document is stored privately with a random name in the Study Load columns', function () {
    $review = facultyDocRegisteredReview();
    $teacher = $review->user;

    expect($review->isTeacherApplication())->toBeTrue()
        ->and($review->review_origin)->toBe('registration')
        ->and($review->study_load_path)->toStartWith('teacher-ids/'.$teacher->id.'/')
        ->and($review->study_load_path)->not->toContain('faculty-id')
        ->and($review->study_load_original_name)->toBe('faculty-id.jpg')
        ->and($review->study_load_mime)->toBe('image/jpeg')
        ->and($review->documentLabel())->toBe('Faculty Verification Document');

    Storage::disk('local')->assertExists($review->study_load_path);
    expect(Storage::disk('public')->allFiles())->toBe([]);
    $this->get('/storage/'.$review->study_load_path)->assertNotFound();
});

test('student registration is unchanged: 10 MB Study Load, no document needed', function () {
    $this->post(route('register.store'), [
        'first_name' => 'Juan',
        'last_name' => 'Dela Cruz',
        'email' => 'juan.student@example.org',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'student',
        'study_load' => UploadedFile::fake()->create('load.pdf', 10240, 'application/pdf'),
        'teacher_id' => teacherIdUpload('sneaky.jpg'),
    ])->assertSessionHasNoErrors();

    $files = Storage::disk('local')->allFiles();

    expect(StudentApplication::sole()->isTeacherApplication())->toBeFalse()
        ->and($files)->toHaveCount(1)
        ->and($files[0])->toStartWith('study-loads/');
});

/*
|--------------------------------------------------------------------------
| Access: Super Admin only
|--------------------------------------------------------------------------
*/

test('only the Super Admin can open the document; everyone else gets 403', function () {
    $review = facultyDocRegisteredReview();
    $url = route('super-admin.student-applications.study-load', $review);

    foreach ([pathwiseApprovedTeacher(), pathwiseUser('student'), pathwiseUser('admin')] as $user) {
        $this->actingAs($user)->get($url)->assertForbidden();
    }

    $this->actingAs($review->user)->get($url)->assertRedirect(route('student-application.status'));

    auth()->guard('web')->logout();
    $this->get($url)->assertRedirect(route('login'));

    $this->actingAs(pathwiseUser('super_admin'))->get($url)
        ->assertOk()
        ->assertHeader('Content-Type', 'image/jpeg')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Content-Disposition', 'inline; filename=faculty-verification-document-'.$review->user_id.'-submission-'.$review->submission_no.'.jpg');
});

/*
|--------------------------------------------------------------------------
| EDP review: no approval without a document
|--------------------------------------------------------------------------
*/

test('the EDP page previews the document and allows approval', function () {
    $review = facultyDocRegisteredReview();
    $url = route('super-admin.student-applications.study-load', $review);

    $this->actingAs(pathwiseUser('super_admin'))
        ->get(route('super-admin.student-applications.show', $review))
        ->assertOk()
        ->assertSee('Faculty Verification Document · Submission #'.$review->submission_no)
        ->assertSee('<img src="'.$url.'"', false)
        ->assertSee('Open Faculty Verification Document')
        ->assertSee('data-test="approve-button"', false)
        ->assertDontSee('Teacher ID')
        ->assertDontSee('data-test="approve-needs-document"', false);
});

test('a pending teacher review without a document cannot be approved, but can be rejected', function (string $origin, string $note) {
    $teacher = pathwiseUser('teacher', ['email_verified_at' => null]);
    $review = StudentApplication::submitForTeacher($teacher, $origin);
    $edp = pathwiseUser('super_admin');

    $this->actingAs($edp)
        ->get(route('super-admin.student-applications.show', $review))
        ->assertOk()
        ->assertSee($note)
        ->assertSee('data-test="approve-needs-document"', false)
        ->assertDontSee('data-test="approve-button"', false)
        ->assertSee('data-test="reject-button"', false);

    // Refused on the server too, even with the faculty confirmation.
    $this->actingAs($edp)
        ->post(route('super-admin.student-applications.approve', $review), ['faculty_confirmed' => '1'])
        ->assertRedirect(route('super-admin.student-applications.show', $review))
        ->assertSessionHas('error', 'This teacher application cannot be approved yet: no Faculty Verification Document has been uploaded. The teacher can upload it from their PathWise status page. You can still reject the application.');

    expect($review->fresh()->status)->toBe(StudentApplication::PENDING)
        ->and($teacher->fresh()->approvedTeacherApplication())->toBeNull();

    $this->actingAs($edp)
        ->post(route('super-admin.student-applications.reject', $review), ['rejection_reason' => 'Please upload your faculty document first.'])
        ->assertSessionHas('success');

    expect($review->fresh()->status)->toBe(StudentApplication::REJECTED);
})->with([
    'registered before the requirement' => ['registration', 'No Faculty Verification Document uploaded yet (registered before this requirement).'],
    'created by the EDP' => ['edp_created', 'No Faculty Verification Document uploaded yet (this review was started by the EDP office).'],
    'role change' => ['role_change', 'No Faculty Verification Document uploaded yet (this review was started by the EDP office).'],
]);

test('a teacher whose pending review has no document uploads it from the status page, then the EDP can approve', function () {
    $edp = pathwiseUser('super_admin');

    $this->actingAs($edp)->post(route('users.store'), [
        'first_name' => 'Faculty',
        'last_name' => 'Member',
        'email' => 'faculty@uclm.edu.ph',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
        'role' => 'teacher',
    ])->assertSessionHasNoErrors();

    $teacher = User::where('email', 'faculty@uclm.edu.ph')->sole();
    $review = $teacher->currentTeacherApplication();

    $this->actingAs($teacher)
        ->get(route('student-application.status'))
        ->assertOk()
        ->assertSee('data-test="faculty-document-needed"', false)
        ->assertSee('data-test="faculty-document-upload-form"', false)
        ->assertSee('Upload School-Issued Faculty Verification Document');

    $this->actingAs($teacher)
        ->post(route('student-application.resubmit'), ['teacher_id' => teacherIdUpload('appointment.png')])
        ->assertSessionHas('success', 'Your Faculty Verification Document was added. The EDP office can now review your teacher registration.');

    // Added to the same pending review (no new submission).
    $review = $review->fresh();
    expect($teacher->teacherApplications()->count())->toBe(1)
        ->and($review->status)->toBe(StudentApplication::PENDING)
        ->and($review->review_origin)->toBe('edp_created')
        ->and($review->study_load_original_name)->toBe('appointment.png');

    // A second upload while it has a document is refused.
    $this->actingAs($teacher)
        ->post(route('student-application.resubmit'), ['teacher_id' => teacherIdUpload()])
        ->assertSessionHas('error');

    $this->actingAs($edp)
        ->post(route('super-admin.student-applications.approve', $review), ['faculty_confirmed' => '1'])
        ->assertSessionHas('success');

    expect($review->fresh()->status)->toBe(StudentApplication::APPROVED);
});

test('a legacy teacher with no review must include the document when requesting review', function () {
    $teacher = pathwiseUser('teacher');

    $this->actingAs($teacher)
        ->get(route('student-application.status'))
        ->assertOk()
        ->assertSee('data-test="teacher-review-request"', false)
        ->assertSee('name="teacher_id"', false);

    $this->actingAs($teacher)
        ->post(route('student-application.resubmit'))
        ->assertSessionHasErrors('teacher_id');

    $this->actingAs($teacher)
        ->post(route('student-application.resubmit'), ['teacher_id' => teacherIdUpload()])
        ->assertSessionHas('success');

    $review = $teacher->fresh()->currentTeacherApplication();

    expect($review->review_origin)->toBe('legacy_request')
        ->and($review->hasStudyLoad())->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| Rejection and resubmission
|--------------------------------------------------------------------------
*/

test('a rejected teacher uploads a new document and the old file is removed', function () {
    $review = facultyDocRegisteredReview();
    $teacher = $review->user;
    $oldPath = $review->study_load_path;
    facultyDocReject($review);

    $this->actingAs($teacher)
        ->get(route('student-application.status'))
        ->assertOk()
        ->assertSee('The photo of the document is too blurry to read.')
        ->assertSee('data-test="teacher-id-resubmit-form"', false)
        ->assertSee('New School-Issued Faculty Verification Document')
        ->assertDontSee('Teacher ID');

    $this->actingAs($teacher)
        ->post(route('student-application.resubmit'), ['teacher_id' => teacherIdUpload('clear-doc.png')])
        ->assertRedirect(route('student-application.status'))
        ->assertSessionHas('success', 'Your new Faculty Verification Document was submitted. The EDP office will review your teacher registration again.');

    $latest = $teacher->fresh()->currentTeacherApplication();

    expect($latest->review_origin)->toBe('resubmission')
        ->and($latest->status)->toBe(StudentApplication::PENDING)
        ->and($latest->study_load_original_name)->toBe('clear-doc.png');

    Storage::disk('local')->assertMissing($oldPath);
    Storage::disk('local')->assertExists($latest->study_load_path);

    $old = $review->fresh();
    expect($old->status)->toBe(StudentApplication::REJECTED)
        ->and($old->hasStudyLoad())->toBeFalse();

    $this->actingAs(pathwiseUser('super_admin'))
        ->get(route('super-admin.student-applications.show', $old))
        ->assertOk()
        ->assertSee('Faculty Verification Document replaced by a newer upload; this older file was removed.');
});

test('a rejected teacher must upload a valid document (5 MB max) to resubmit', function (array $payload) {
    $review = facultyDocRegisteredReview();
    facultyDocReject($review);

    $this->actingAs($review->user)
        ->post(route('student-application.resubmit'), $payload)
        ->assertSessionHasErrors('teacher_id');

    expect(StudentApplication::count())->toBe(1)
        ->and(Storage::disk('local')->allFiles())->toHaveCount(1);
})->with([
    'no file' => [[]],
    'wrong type' => [fn () => ['teacher_id' => UploadedFile::fake()->create('doc.txt', 5, 'text/plain')]],
    'over 5 MB' => [fn () => ['teacher_id' => UploadedFile::fake()->create('doc.pdf', 5121, 'application/pdf')]],
]);

/*
|--------------------------------------------------------------------------
| Unchanged rules
|--------------------------------------------------------------------------
*/

test('approved teachers keep their access and are never asked for a document', function () {
    $teacher = pathwiseApprovedTeacher();

    $this->actingAs($teacher)->get(route('teacher.dashboard'))->assertOk();
    $this->actingAs($teacher)->get(route('student-application.status'))->assertDontSee('name="teacher_id"', false);
});

test('Mark verified still refuses teachers', function () {
    $review = facultyDocRegisteredReview();

    $this->actingAs(pathwiseUser('super_admin'))
        ->patch(route('users.verify-email', $review->user))
        ->assertSessionHas('error');

    expect($review->user->fresh()->hasVerifiedEmail())->toBeFalse();
});
