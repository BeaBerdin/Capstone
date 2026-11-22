<?php

use App\Models\Role;
use App\Models\StudentApplication;
use App\Models\User;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Notification::fake();
});

function phase6aUser(string $role, array $attributes = []): User
{
    $user = User::factory()->create($attributes);
    $user->roles()->attach(Role::firstOrCreate(['name' => $role])->id);

    return $user;
}

test('teacher approval is role and current email bound and does not replace student application history', function () {
    $teacher = phase6aUser('teacher', ['email' => 'teacher@example.test']);
    $student = phase6aUser('student', ['email' => 'student@example.test']);

    $teacherReview = StudentApplication::submitForTeacher($teacher, 'registration');
    $studentLoad = $student->studentApplications()->create([
        'submission_no' => 1,
        'study_load_path' => 'study-loads/student.pdf',
        'study_load_original_name' => 'student.pdf',
        'study_load_mime' => 'application/pdf',
        'study_load_size' => 128,
        'status' => StudentApplication::APPROVED,
        'submitted_at' => now(),
    ]);

    expect($teacher->currentTeacherApplication()->is($teacherReview))->toBeTrue()
        ->and($teacher->approvedTeacherApplication())->toBeNull()
        ->and($teacher->applicationStatus())->toBe(StudentApplication::PENDING)
        ->and($student->latestStudentApplication->is($studentLoad))->toBeTrue()
        ->and($student->awaitingStudentApproval())->toBeFalse()
        ->and($teacherReview->hasStudyLoad())->toBeFalse()
        ->and($teacherReview->reviewsEmail('teacher@example.test'))->toBeTrue()
        ->and($teacherReview->reviewsEmail('other@example.test'))->toBeFalse();

    $teacher->forceFill(['email_verified_at' => now()])->save();
    $teacher->updateAccountIdentity(['email' => 'new@example.test']);

    expect($teacher->fresh()->hasVerifiedEmail())->toBeFalse()
        ->and($teacher->fresh()->currentTeacherApplication()->review_email)->toBe('new@example.test')
        ->and($teacher->fresh()->currentTeacherApplication()->review_origin)->toBe('email_change')
        ->and($teacherReview->fresh()->status)->toBe(StudentApplication::PENDING);
});

test('legacy teacher approval without a reviewed email does not grant access or alter student applications', function () {
    $teacher = phase6aUser('teacher');
    $student = phase6aUser('student');

    $history = $teacher->teacherApplications()->create([
        'submission_no' => 1,
        'applicant_role' => StudentApplication::TEACHER,
        'review_email' => null,
        'review_origin' => 'legacy_history',
        'study_load_path' => '',
        'study_load_original_name' => '',
        'study_load_mime' => '',
        'study_load_size' => 0,
        'status' => StudentApplication::APPROVED,
        'reviewed_by' => $student->id,
        'reviewed_at' => now(),
        'submitted_at' => now(),
    ]);

    $studentLoad = $student->studentApplications()->create([
        'submission_no' => 1,
        'study_load_path' => 'study-loads/kept.pdf',
        'study_load_original_name' => 'kept.pdf',
        'study_load_mime' => 'application/pdf',
        'study_load_size' => 42,
        'status' => StudentApplication::PENDING,
        'submitted_at' => now(),
    ]);

    expect($history->isTeacherApplication())->toBeTrue()
        ->and($teacher->currentTeacherApplication())->toBeNull()
        ->and($teacher->approvedTeacherApplication())->toBeNull()
        ->and($teacher->awaitingTeacherApproval())->toBeTrue()
        ->and($student->latestStudentApplication->is($studentLoad))->toBeTrue()
        ->and($student->awaitingStudentApproval())->toBeTrue()
        ->and($studentLoad->study_load_path)->toBe('study-loads/kept.pdf');
});
