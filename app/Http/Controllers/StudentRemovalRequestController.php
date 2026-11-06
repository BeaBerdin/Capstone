<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\StudentRemovalRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StudentRemovalRequestController extends Controller
{
    /**
     * Show removal requests submitted by the logged-in teacher.
     */
    public function instructorIndex()
    {
        $requests = StudentRemovalRequest::with([
            'student',
            'course',
            'reviewer',
        ])
            ->where('instructor_id', auth()->id())
            ->latest()
            ->paginate(10);

        return view(
            'Teacher.student-removal-requests.index',
            compact('requests')
        );
    }

    /**
     * Teacher submits a request to remove a student from one course.
     */
    public function store(Request $request, Course $course)
    {
        $validated = $request->validate([
            'student_id' => [
                'required',
                'integer',
                'exists:users,id',
            ],
            'reason' => [
                'required',
                'string',
                'min:10',
                'max:2000',
            ],
        ]);

        // Only the teacher who owns the course can submit the request.
        abort_unless(
            (int) $course->teacher_id === (int) auth()->id(),
            403,
            'You are not authorized to manage this course.'
        );

        // Confirm the student is enrolled in this specific course.
        $enrollment = Enrollment::where('course_id', $course->id)
            ->where('student_id', $validated['student_id'])
            ->first();

        // A cancelled enrollment has already been removed.
        if (!$enrollment || !$enrollment->grantsAccess()) {
            return back()
                ->withErrors([
                    'student_id' =>
                        'The student is not enrolled in this course.',
                ])
                ->withInput();
        }

        // Prevent duplicate pending requests.
        $existingRequest = StudentRemovalRequest::where(
            'instructor_id',
            auth()->id()
        )
            ->where('student_id', $validated['student_id'])
            ->where('course_id', $course->id)
            ->where('status', 'pending')
            ->exists();

        if ($existingRequest) {
            return back()->withErrors([
                'reason' =>
                    'A pending removal request already exists for this student in this course.',
            ]);
        }

        StudentRemovalRequest::create([
            'instructor_id' => auth()->id(),
            'student_id' => $validated['student_id'],
            'course_id' => $course->id,
            'reason' => $validated['reason'],
            'status' => 'pending',
        ]);

        $reviewers = auth()->user()->department_id
            ? 'the Department Head approves it'
            : 'an administrator approves it (your account has no department, so the request was sent to all administrators)';

        return back()->with(
            'success',
            'Student removal request submitted successfully. The student remains enrolled until '.$reviewers.'.'
        );
    }

    /**
     * Show the requests this administrator may review (see the rules in
     * StudentRemovalRequest): the own department, teachers without a
     * department and departments without an administrator; everything
     * for an administrator without a department.
     */
    public function departmentHeadIndex()
    {
        $administrator = auth()->user();

        $requests = StudentRemovalRequest::with([
            'instructor',
            'student',
            'course',
            'reviewer',
        ])
            ->reviewableBy($administrator)
            ->latest()
            ->paginate(10);

        $campusWide = ! $administrator->department_id;

        return view(
            'student-removal-requests.index',
            compact('requests', 'campusWide')
        );
    }

    /**
     * Department Head approves or rejects a request.
     */
    public function review(
        Request $request,
        StudentRemovalRequest $studentRemovalRequest
    ) {
        $validated = $request->validate([
            'decision' => [
                'required',
                'in:approved,rejected',
            ],
            'review_note' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ]);

        // A Department Head reviews only the requests routed to them.
        abort_unless(
            $studentRemovalRequest->isReviewableBy(auth()->user()),
            403,
            'You cannot review requests from another department.'
        );

        DB::transaction(function () use (
            $validated,
            $studentRemovalRequest
        ) {
            $removalRequest = StudentRemovalRequest::whereKey(
                $studentRemovalRequest->id
            )
                ->lockForUpdate()
                ->firstOrFail();

            abort_if(
                $removalRequest->status !== 'pending',
                409,
                'This request has already been reviewed.'
            );

            if ($validated['decision'] === 'approved') {
                $enrollment = Enrollment::where(
                    'course_id',
                    $removalRequest->course_id
                )
                    ->where(
                        'student_id',
                        $removalRequest->student_id
                    )
                    ->lockForUpdate()
                    ->first();

                abort_if(
                    !$enrollment || !$enrollment->grantsAccess(),
                    409,
                    'The student is no longer enrolled in this course.'
                );

                // Cancel the enrollment instead of deleting it, so progress,
                // quiz results and payment history are kept and an
                // administrator can restore it. The student account is
                // never touched.
                $enrollment->update([
                    'status' => 'cancelled',
                ]);
            }

            $removalRequest->update([
                'status' => $validated['decision'],
                'reviewed_by' => auth()->id(),
                'review_note' => $validated['review_note'] ?? null,
                'reviewed_at' => now(),
            ]);
        });

        return back()->with(
            'success',
            'Removal request ' .
                $validated['decision'] .
                ' successfully.'
        );
    }
}
