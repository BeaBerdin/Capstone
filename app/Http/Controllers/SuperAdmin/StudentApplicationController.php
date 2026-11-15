<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\StudentApplication;
use App\Notifications\StudentApplicationApproved;
use App\Notifications\StudentApplicationRejected;
use App\Support\StudyLoads;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Student applications for the Super Admin (EDP). The routes sit inside
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

        $counts = StudentApplication::query()
            ->latestPerStudent()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $applications = StudentApplication::query()
            ->latestPerStudent()
            ->where('status', $tab)
            ->with(['user', 'reviewer'])
            // Pending: oldest first (first come, first served).
            ->orderBy('submitted_at', $tab === StudentApplication::PENDING ? 'asc' : 'desc')
            ->paginate(20)
            ->withQueryString();

        return view('super-admin.student-applications.index', [
            'tab' => $tab,
            'tabs' => self::TABS,
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
            'isLatest' => $history->first()?->is($studentApplication) ?? false,
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
            'study-load-%d-submission-%d.%s',
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
        if (! $this->decide($studentApplication, StudentApplication::APPROVED, $request->user()->id)) {
            return $this->alreadyDecided($studentApplication);
        }

        $student = $studentApplication->user()->first();
        $redirect = redirect()->route('super-admin.student-applications.show', $studentApplication);

        if ($student->hasVerifiedEmail()) {
            return $redirect->with('success', 'Application approved. '.$student->name.'\'s email is already verified, so they can use PathWise now.');
        }

        try {
            $student->notify(new StudentApplicationApproved);
        } catch (Throwable $e) {
            Log::warning('PATHWISE approval email could not be sent; the approval is saved.', [
                'application_id' => $studentApplication->id,
                'error' => $e->getMessage(),
            ]);

            return $redirect
                ->with('success', 'Application approved.')
                ->with('warning', 'The acceptance email to '.$student->email.' could not be sent. The approval is saved. The student can log in and choose "Resend verification email".');
        }

        return $redirect->with('success', 'Application approved. An acceptance email with a verification link (valid for '.StudentApplicationApproved::EXPIRES_HOURS.' hours) was sent to '.$student->email.'.');
    }

    public function reject(Request $request, StudentApplication $studentApplication)
    {
        $request->merge(['rejection_reason' => trim((string) $request->input('rejection_reason'))]);

        $validated = $request->validate(
            ['rejection_reason' => ['required', 'string', 'min:10', 'max:2000']],
            [
                'rejection_reason.required' => 'Please write the reason for rejecting this Study Load.',
                'rejection_reason.min' => 'The reason must be at least 10 characters so the student knows what to correct.',
            ],
        );

        if (! $this->decide($studentApplication, StudentApplication::REJECTED, $request->user()->id, $validated['rejection_reason'])) {
            return $this->alreadyDecided($studentApplication);
        }

        $studentApplication->refresh();
        $student = $studentApplication->user()->first();
        $redirect = redirect()->route('super-admin.student-applications.show', $studentApplication);

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

        return $redirect->with('success', 'Application rejected. '.$student->name.' was emailed the reason and how to resubmit.');
    }

    /**
     * Records a decision only if the application is still pending, in a
     * single conditional update. Two reviewers deciding at the same time
     * cannot both succeed, and a decided application is never overwritten.
     */
    private function decide(StudentApplication $application, string $status, int $reviewerId, ?string $reason = null): bool
    {
        return StudentApplication::query()
            ->whereKey($application->id)
            ->where('status', StudentApplication::PENDING)
            ->update([
                'status' => $status,
                'reviewed_by' => $reviewerId,
                'reviewed_at' => now(),
                'rejection_reason' => $status === StudentApplication::REJECTED ? $reason : null,
                'updated_at' => now(),
            ]) === 1;
    }

    private function alreadyDecided(StudentApplication $application)
    {
        $current = $application->fresh(['reviewer']);

        return redirect()
            ->route('super-admin.student-applications.show', $application)
            ->with('error', 'This application was already '.$current->status
                .($current->reviewer ? ' by '.$current->reviewer->name : '')
                .'. The earlier decision was kept.');
    }
}
