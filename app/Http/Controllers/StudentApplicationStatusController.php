<?php

namespace App\Http\Controllers;

use App\Models\StudentApplication;
use App\Models\User;
use App\Support\StudyLoads;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * The student's own application page: status, submission history with the
 * EDP's rejection reasons, the resubmission form (only while the newest
 * application is rejected) and, once approved, the verification reminder.
 *
 * A student can only see and add to their own applications; there is no
 * route that lets a student change a status.
 */
class StudentApplicationStatusController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();

        $applications = $user->studentApplications()->get()->sortByDesc('submission_no')->values();

        // Accounts without an application (teachers, admins, existing
        // students) have nothing to show here.
        if ($applications->isEmpty() || ! $user->hasRole('student')) {
            return redirect()->route('dashboard');
        }

        return view('student-application.status', [
            'user' => $user,
            'applications' => $applications,
            'latest' => $applications->first(),
        ]);
    }

    public function resubmit(Request $request)
    {
        $request->validate(
            ['study_load' => StudentApplication::studyLoadRules()],
            StudentApplication::studyLoadMessages(),
        );

        /** @var User $user */
        $user = $request->user();
        $storedPath = null;

        try {
            $application = DB::transaction(function () use ($request, $user, &$storedPath) {
                // One resubmission at a time per account (also stops a
                // double-click from creating two pending applications).
                User::whereKey($user->id)->lockForUpdate()->first();

                $latest = StudentApplication::where('user_id', $user->id)
                    ->orderByDesc('submission_no')
                    ->lockForUpdate()
                    ->first();

                if (! $latest || ! $user->hasRole('student') || $latest->status !== StudentApplication::REJECTED) {
                    return null;
                }

                return StudyLoads::submit(
                    $user,
                    $request->file('study_load'),
                    $latest->submission_no + 1,
                    $storedPath,
                );
            });
        } catch (Throwable $e) {
            StudyLoads::discard($storedPath);

            throw $e;
        }

        if (! $application) {
            return redirect()
                ->route('student-application.status')
                ->with('error', 'A corrected Study Load can only be uploaded after the EDP rejects your application.');
        }

        StudyLoads::notifyEdp($application, resubmission: true);

        return redirect()
            ->route('student-application.status')
            ->with('success', 'Your corrected Study Load was submitted. The EDP office will review it again.');
    }
}
