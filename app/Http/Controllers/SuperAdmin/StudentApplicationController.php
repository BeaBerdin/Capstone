<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\StudentApplication;
use App\Models\User;
use App\Notifications\StudentApplicationApproved;
use App\Notifications\StudentApplicationRejected;
use App\Support\StudyLoads;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Student and teacher applications for the Super Admin (EDP). Teacher
 * registrations use the same rows without a Study Load. The routes sit inside
 * `role:super_admin`, so the Admin (Dean), teachers and students are refused
 * before reaching any method here.
 */
class StudentApplicationController extends Controller
{
    public const TABS = [
        StudentApplication::PENDING => 'Pending',
        StudentApplication::APPROVED => 'Approved',
        StudentApplication::REJECTED => 'Rejected',
    ];

    /** Applicant filter: everyone, students only or teachers only. */
    public const ROLE_FILTERS = [
        'all' => 'All',
        'student' => 'Students',
        'teacher' => 'Teachers',
    ];

    /** Study Load types the browser may show inline. */
    private const INLINE_TYPES = [
        'application/pdf' => 'pdf',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
    ];

    /**
     * One row per student (their newest submission), grouped by status.
     */
    public function index(Request $request)
    {
        $tab = array_key_exists((string) $request->query('status'), self::TABS)
            ? (string) $request->query('status')
            : StudentApplication::PENDING;

        $role = array_key_exists((string) $request->query('role'), self::ROLE_FILTERS)
            ? (string) $request->query('role')
            : 'all';

        $counts = StudentApplication::query()
            ->latestPerStudent()
            ->forApplicantRole($role)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $applications = StudentApplication::query()
            ->latestPerStudent()
            ->forApplicantRole($role)
            ->where('status', $tab)
            ->with(['user.roles', 'reviewer'])
            // Pending: oldest first (first come, first served).
            ->orderBy('submitted_at', $tab === StudentApplication::PENDING ? 'asc' : 'desc')
            ->paginate(20)
            ->withQueryString();

        return view('super-admin.student-applications.index', [
            'tab' => $tab,
            'tabs' => self::TABS,
            'role' => $role,
            'roleFilters' => self::ROLE_FILTERS,
            'counts' => $counts,
            'applications' => $applications,
        ]);
    }

    public function show(StudentApplication $studentApplication)
    {
        $studentApplication->load(['user.roles', 'reviewer']);

        $history = StudentApplication::query()
            ->where('user_id', $studentApplication->user_id)
            ->with('reviewer')
            ->orderByDesc('submission_no')
            ->get();

        return view('super-admin.student-applications.show', [
            'application' => $studentApplication,
            'student' => $studentApplication->user,
            'history' => $history,
            'isLatest' => $history->firstWhere('applicant_role', $studentApplication->applicant_role)?->is($studentApplication) ?? false,
            'canReview' => $this->canReview($studentApplication, $studentApplication->user),
        ]);
    }

    /**
     * The Study Load file, from the private disk only. PDF and images open
     * in the browser; the stored type was checked when it was uploaded.
     */
    public function studyLoad(StudentApplication $studentApplication)
    {
        $disk = Storage::disk(StudyLoads::DISK);
        $path = $studentApplication->study_load_path;

        abort_unless(filled($path) && $disk->exists($path), 404);

        $type = array_key_exists($studentApplication->study_load_mime, self::INLINE_TYPES)
            ? $studentApplication->study_load_mime
            : null;

        $name = sprintf(
            '%s-%d-submission-%d.%s',
            $studentApplication->isTeacherApplication() ? 'teacher-id' : 'study-load',
            $studentApplication->user_id,
            $studentApplication->submission_no,
            $type ? self::INLINE_TYPES[$type] : 'bin',
        );

        return $disk->response(
            $path,
            $name,
            [
                'Content-Type' => $type ?? 'application/octet-stream',
                'X-Content-Type-Options' => 'nosniff',
                'Cache-Control' => 'private, no-store',
                'Referrer-Policy' => 'no-referrer',
            ],
            $type ? 'inline' : 'attachment',
        );
    }

    public function approve(Request $request, StudentApplication $studentApplication)
    {
        if ($studentApplication->isTeacherApplication() && $studentApplication->isPending()) {
            $request->validate(['faculty_confirmed' => ['accepted']], [
                'faculty_confirmed.accepted' => 'Confirm the faculty member and reviewed email with the department first.',
            ]);
        }

        if (! $this->decide($studentApplication, StudentApplication::APPROVED, $request->user()->id)) {
            return $this->alreadyDecided($studentApplication);
        }

        $student = $studentApplication->user()->first();
        $redirect = redirect()->route('super-admin.student-applications.show', $studentApplication);

        AuditLog::record(
            AuditLog::APPLICATION_APPROVED,
            $student,
            StudentApplication::PENDING,
            StudentApplication::APPROVED,
            ($studentApplication->isTeacherApplication() ? 'Teacher' : 'Student').' application #'.$studentApplication->id,
        );

        if ($student->hasVerifiedEmail()) {
            return $redirect->with('success', 'Application approved. '.$student->name.'\'s email is already verified, so they can use PathWise now.');
        }

        try {
            $student->notify(new StudentApplicationApproved(
                $studentApplication->isTeacherApplication() ? $studentApplication : null
            ));
        } catch (Throwable $e) {
            Log::warning('PATHWISE approval email could not be sent; the approval is saved.', [
                'application_id' => $studentApplication->id,
                'error' => $e->getMessage(),
            ]);

            return $redirect
                ->with('success', 'Application approved.')
                ->with('warning', 'The acceptance email to '.$student->email.' could not be sent. The approval is saved. The '.($student->hasRole('teacher') ? 'teacher' : 'student').' can log in and choose "Resend verification email".');
        }

        return $redirect->with('success', 'Application approved. An acceptance email with a verification link (valid for '.StudentApplicationApproved::EXPIRES_HOURS.' hours) was sent to '.$student->email.'.');
    }

    public function reject(Request $request, StudentApplication $studentApplication)
    {
        $request->merge(['rejection_reason' => trim((string) $request->input('rejection_reason'))]);

        $validated = $request->validate(
            ['rejection_reason' => ['required', 'string', 'min:10', 'max:2000']],
            [
                'rejection_reason.required' => $studentApplication->isTeacherApplication()
                    ? 'Please write the reason for rejecting this teacher registration.'
                    : 'Please write the reason for rejecting this Study Load.',
                'rejection_reason.min' => 'The reason must be at least 10 characters so the '
                    .($studentApplication->isTeacherApplication() ? 'teacher' : 'student').' knows what to correct.',
            ],
        );

        if (! $this->decide($studentApplication, StudentApplication::REJECTED, $request->user()->id, $validated['rejection_reason'])) {
            return $this->alreadyDecided($studentApplication);
        }

        $studentApplication->refresh();
        $student = $studentApplication->user()->first();
        $redirect = redirect()->route('super-admin.student-applications.show', $studentApplication);

        AuditLog::record(
            AuditLog::APPLICATION_REJECTED,
            $student,
            StudentApplication::PENDING,
            StudentApplication::REJECTED,
            $validated['rejection_reason'],
        );

        try {
            $student->notify(new StudentApplicationRejected($studentApplication));
        } catch (Throwable $e) {
            Log::warning('PATHWISE rejection email could not be sent; the rejection is saved.', [
                'application_id' => $studentApplication->id,
                'error' => $e->getMessage(),
            ]);

            return $redirect
                ->with('success', 'Application rejected.')
                ->with('warning', 'The rejection email to '.$student->email.' could not be sent. The rejection and its reason are saved and shown to the student when they log in.');
        }

        return $redirect->with('success', 'Application rejected. '.$student->name.' was emailed the reason'
            .($student->hasRole('teacher') ? '.' : ' and how to resubmit.'));
    }

    /**
     * Serialize with email edits, promotions, resubmissions and link redemption.
     */
    private function decide(StudentApplication $application, string $status, int $reviewerId, ?string $reason = null): bool
    {
        return DB::transaction(function () use ($application, $status, $reviewerId, $reason) {
            $user = User::query()->lockForUpdate()->findOrFail($application->user_id);
            $application = StudentApplication::query()->lockForUpdate()->findOrFail($application->id);

            if (! $this->canReview($application, $user)
                || ($application->isTeacherApplication() && $user->id === $reviewerId)) {
                return false;
            }

            $application->forceFill([
                'status' => $status,
                'reviewed_by' => $reviewerId,
                'reviewed_at' => now(),
                'rejection_reason' => $status === StudentApplication::REJECTED ? $reason : null,
            ])->save();

            if ($application->isTeacherApplication() && $status === StudentApplication::APPROVED) {
                // Earlier verification may predate this EDP review or have been set manually.
                $user->forceFill(['email_verified_at' => null])->save();
            }

            return true;
        });
    }

    private function canReview(StudentApplication $application, User $user): bool
    {
        if (! $application->isPending()) {
            return false;
        }

        if ($application->isTeacherApplication()) {
            return $user->hasRole('teacher') && $user->currentTeacherApplication()?->is($application);
        }

        return $user->hasRole('student') && $user->latestStudentApplication?->is($application);
    }

    private function alreadyDecided(StudentApplication $application)
    {
        $current = $application->fresh(['reviewer']);

        return redirect()
            ->route('super-admin.student-applications.show', $application)
            ->with('error', $current->isPending()
                ? 'This application cannot be reviewed. Check the current role and email, and use the latest application.'
                : 'This application was already '.$current->status
                .($current->reviewer ? ' by '.$current->reviewer->name : '')
                .'. The earlier decision was kept.');
    }
}
