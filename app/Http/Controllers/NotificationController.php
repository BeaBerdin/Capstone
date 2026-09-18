<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function read(
        Request $request,
        string $notification
    ): RedirectResponse {
        $user = $request->user();

        abort_unless(
            $user,
            401
        );

        /*
        |--------------------------------------------------------------------------
        | OWNERSHIP-SAFE NOTIFICATION LOOKUP
        |--------------------------------------------------------------------------
        | The notification is always resolved through the authenticated user's
        | own notification relationship. A user cannot mark another user's
        | notification as read by guessing its UUID.
        */

        $item = $user
            ->notifications()
            ->whereKey($notification)
            ->firstOrFail();

        if (is_null($item->read_at)) {
            $item->markAsRead();
        }

        $data = is_array($item->data)
            ? $item->data
            : [];

        $type =
            (string) ($data['type'] ?? '');

        $courseId =
            $data['course_id'] ?? null;

        $assignmentId =
            $data['assignment_id'] ?? null;

        $submissionId =
            $data['submission_id'] ?? null;

        $transactionId =
            $data['transaction_id'] ?? null;

        $certificateId =
            $data['certificate_id'] ?? null;


        /*
        |--------------------------------------------------------------------------
        | TYPE-SPECIFIC DESTINATIONS
        |--------------------------------------------------------------------------
        | Resolve specific notification types before the generic course fallback.
        | Assignment/payment/certificate notifications also contain course_id,
        | so checking course_id first would send users to the wrong page.
        */

        switch ($type) {
            case 'course_submitted':
                if ($user->hasRole('admin')) {
                    return redirect()
                        ->route('courses.index');
                }
                break;


            case 'assignment_published':
            case 'assignment_graded':
            case 'assignment_grade_updated':
            case 'assignment_returned':
                if (
                    $user->hasRole('student')
                    &&
                    $assignmentId
                ) {
                    return redirect()
                        ->route(
                            'student.assignments.show',
                            [
                                'assignment' =>
                                    $assignmentId,
                            ]
                        );
                }
                break;


            case 'assignment_submitted':
            case 'assignment_resubmitted':
                if (
                    $user->hasRole('teacher')
                    &&
                    $assignmentId
                ) {
                    return redirect()
                        ->route(
                            'teacher.submissions.index',
                            [
                                'assignment' =>
                                    $assignmentId,
                            ]
                        );
                }
                break;


            case 'payment_approved':
                if (
                    $user->hasRole('student')
                    &&
                    $transactionId
                ) {
                    return redirect()
                        ->route(
                            'student.transactions.show',
                            [
                                'transaction' =>
                                    $transactionId,
                            ]
                        );
                }
                break;


            case 'enrollment_activated':
                if ($user->hasRole('student')) {
                    return redirect()
                        ->route('student.my-courses');
                }
                break;


            case 'certificate_issued':
                if (
                    $user->hasRole('student')
                    &&
                    $certificateId
                ) {
                    return redirect()
                        ->route(
                            'student.certificate.view',
                            [
                                'certificate' =>
                                    $certificateId,
                            ]
                        );
                }
                break;
        }


        /*
        |--------------------------------------------------------------------------
        | LEGACY / GENERIC COURSE NOTIFICATION FALLBACK
        |--------------------------------------------------------------------------
        | Existing CourseStatusNotification records do not contain a `type`
        | value, so preserve the original role-aware behavior for them.
        */

        if ($courseId) {
            if ($user->hasRole('teacher')) {
                return redirect()
                    ->route('teacher.my-courses');
            }

            if ($user->hasRole('admin')) {
                return redirect()
                    ->route('courses.index');
            }

            if ($user->hasRole('student')) {
                return redirect()
                    ->route('student.my-courses');
            }
        }


        /*
        |--------------------------------------------------------------------------
        | SAFE DEFAULT
        |--------------------------------------------------------------------------
        */

        return redirect()
            ->route('dashboard');
    }


    public function readAll(
        Request $request
    ): RedirectResponse {
        $user = $request->user();

        abort_unless(
            $user,
            401
        );

        $user->unreadNotifications()
            ->update([
                'read_at' => now(),
            ]);

        return back();
    }
}
