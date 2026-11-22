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

        $isTeacher = $user->hasRole('teacher');
        $applications = ($isTeacher ? $user->teacherApplications() : $user->studentApplications())
            ->get()->sortByDesc('submission_no')->values();

        if (! $isTeacher && (! $user->hasRole('student') || $applications->isEmpty())) {
            return redirect()->route('dashboard');
        }

        return view('student-application.status', [
            'user' => $user,
            'applications' => $applications,
            'latest' => $isTeacher ? $user->currentTeacherApplication() : $applications->first(),
            'status' => $user->applicationStatus(),
            'isTeacher' => $isTeacher,
        ]);
    }

    /**
     * Polled by the status page every few seconds so the student's original
     * browser follows the EDP's decision and the email verification (which
     * often happens in another browser, e.g. the Gmail app) by itself.
     *
     * It only ever describes the signed-in account: it takes no user id, so
     * it cannot reveal another student's status. It never logs anyone in or
     * switches accounts; "ready" is true only when this account is approved
     * and verified, i.e. exactly when /dashboard will let it in.
     */
    public function check(Request $request)
    {
        // Fresh from the database: the decision/verification happened in
        // another request, possibly another browser.
        $user = $request->user()->fresh();

        $status = $user->applicationStatus();
        $verified = $user->hasVerifiedEmail();

        return response()
            ->json([
                'user_id' => $user->id,
                'status' => $status,
                'verified' => $verified,
                'ready' => $verified && ! $user->awaitingApproval(),
                'dashboard_url' => route('dashboard'),
            ])
            ->header('Cache-Control', 'no-store, private, max-age=0');
    }

    public function resubmit(Request $request)
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->hasRole('teacher')) {
            return $this->requestTeacherReview($request, $user);
        }

        $request->validate(
            ['study_load' => StudentApplication::studyLoadRules()],
            StudentApplication::studyLoadMessages(),
        );

        $storedPath = null;

        try {
            $application = DB::transaction(function () use ($request, $user, &$storedPath) {
                // One resubmission at a time per account (also stops a
                // double-click from creating two pending applications).
                $user = User::query()->lockForUpdate()->findOrFail($user->id);
                $latest = $user->latestStudentApplication;

                if (! $latest || ! $user->hasRole('student') || $latest->status !== StudentApplication::REJECTED) {
                    return null;
                }

                return StudyLoads::submit(
                    $user,
                    $request->file('study_load'),
                    ((int) StudentApplication::where('user_id', $user->id)->max('submission_no')) + 1,
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

    /**
     * A teacher sends their Faculty Verification Document:
     *  - no current review (legacy teacher): starts a review with it;
     *  - review rejected: a new review with a new document; the old file is
     *    deleted once the new one is saved;
     *  - review pending without a document (for example one started by the
     *    EDP office): the document is added to that same pending review.
     * The EDP cannot approve a teacher review without a document, so every
     * path requires the upload.
     */
    private function requestTeacherReview(Request $request, User $user)
    {
        $request->validate(
            ['teacher_id' => StudentApplication::teacherIdRules()],
            StudentApplication::teacherIdMessages(),
        );

        $storedPath = null;
        $replaced = null;
        $attached = false;

        try {
            $application = DB::transaction(function () use ($request, $user, &$storedPath, &$replaced, &$attached) {
                $user = User::query()->lockForUpdate()->findOrFail($user->id);
                $latest = $user->currentTeacherApplication();

                if (! $user->hasRole('teacher')) {
                    return null;
                }

                if (! $latest) {
                    return StudentApplication::submitForTeacher(
                        $user,
                        'legacy_request',
                        StudyLoads::storeTeacherId($user, $request->file('teacher_id'), $storedPath),
                    );
                }

                if ($latest->status === StudentApplication::REJECTED) {
                    $replaced = $latest;

                    return StudentApplication::submitForTeacher(
                        $user,
                        'resubmission',
                        StudyLoads::storeTeacherId($user, $request->file('teacher_id'), $storedPath),
                    );
                }

                // Pending without a document: add it to the same review.
                if ($latest->status === StudentApplication::PENDING && ! $latest->hasStudyLoad()) {
                    $locked = StudentApplication::query()->lockForUpdate()->findOrFail($latest->id);

                    if ($locked->status !== StudentApplication::PENDING || $locked->hasStudyLoad()) {
                        return null;
                    }

                    $locked->forceFill(
                        StudyLoads::storeTeacherId($user, $request->file('teacher_id'), $storedPath)
                    )->save();
                    $attached = true;

                    return $locked;
                }

                return null;
            });
        } catch (Throwable $e) {
            StudyLoads::discard($storedPath);

            throw $e;
        }

        if (! $application) {
            StudyLoads::discard($storedPath);

            return redirect()
                ->route('student-application.status')
                ->with('error', 'There is already a current teacher review for this email.');
        }

        // The old document is no longer needed once the new one is saved.
        if ($replaced) {
            StudyLoads::removeReplacedTeacherId($replaced);
        }

        StudyLoads::notifyEdp($application, resubmission: $application->review_origin === 'resubmission');

        return redirect()
            ->route('student-application.status')
            ->with('success', match (true) {
                $replaced !== null => 'Your new Faculty Verification Document was submitted. The EDP office will review your teacher registration again.',
                $attached => 'Your Faculty Verification Document was added. The EDP office can now review your teacher registration.',
                default => 'Your request was sent. The EDP office will review your teacher registration.',
            });
    }
}
