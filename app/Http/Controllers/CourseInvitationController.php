<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseInvitation;
use App\Models\Enrollment;
use App\Models\User;
use App\Notifications\PathwiseNotification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;


class CourseInvitationController extends Controller
{
   public function index()
{
    $invitations = CourseInvitation::with('course')
        ->where('created_by', auth()->id())
        ->latest()
        ->get();

    $courses = Course::where('teacher_id', auth()->id())
        ->orderBy('title')
        ->get();

    $students = User::whereHas('roles', function ($query) {
        $query->where('name', 'student');
    })
        ->orderBy('name')
        ->get();

    return view('course-invitations.index', compact(
        'invitations',
        'courses',
        'students'
    ));
}

    public function store(Request $request)
    {
       $validated = $request->validate([
    'course_id' => [
        'required',
        'integer',
        'exists:courses,id',
    ],
    'expires_at' => [
        'nullable',
        'date',
    ],
]);

$expiresAt = null;

if (! empty($validated['expires_at'])) {
    $expiresAt = Carbon::createFromFormat(
        'Y-m-d\TH:i',
        $validated['expires_at'],
        'Asia/Manila'
    )->utc();
}

        $course = Course::where('id', $validated['course_id'])
            ->where('teacher_id', auth()->id())
            ->firstOrFail();

        do {
            $code = Str::upper(Str::random(12));
        } while (CourseInvitation::where('code', $code)->exists());

        CourseInvitation::create([
            'course_id' => $course->id,
            'created_by' => auth()->id(),
            'student_id' => null,
            'code' => $code,
            'expires_at' => $expiresAt,
        ]);

        return redirect()
            ->route('course-invitations.index')
            ->with('success', 'Invitation created successfully.');
    }

  public function show(string $code)
{
    $invitation = CourseInvitation::with('course')
        ->where('code', strtoupper($code))
        ->firstOrFail();

    if ($invitation->isExpired()) {
        return redirect()
            ->route('student.dashboard')
            ->with(
                'error',
                'This course invitation has expired and can no longer be opened.'
            );
    }

    if ($invitation->isAccepted()) {
        return redirect()
            ->route('student.dashboard')
            ->with(
                'error',
                'This course invitation has already been used.'
            );
    }

    return view(
        'course-invitations.show',
        compact('invitation')
    );
}

    public function accept(string $code)
    {
        $invitation = CourseInvitation::with('course')
            ->where('code', strtoupper($code))
            ->firstOrFail();

        if (! $invitation->isValid()) {
            return redirect()
                ->route(
                    'course-invitations.show',
                    $invitation->code
                )
                ->with(
                    'error',
                    'This invitation is no longer valid.'
                );
        }

        $course = $invitation->course;

        if (! $course) {
            return redirect()
                ->route(
                    'course-invitations.show',
                    $invitation->code
                )
                ->with(
                    'error',
                    'The course associated with this invitation no longer exists.'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | If this invitation was specifically sent to a student,
        | make sure only that student can use it.
        |--------------------------------------------------------------------------
        */

        if (
            $invitation->student_id !== null
            && (int) $invitation->student_id !== (int) auth()->id()
        ) {
            abort(403, 'This invitation was assigned to another student.');
        }

        /*
        |--------------------------------------------------------------------------
        | PAID COURSE
        |--------------------------------------------------------------------------
        |
        | Never create an enrollment directly for a paid course.
        | The payment must go through the normal PayMongo flow.
        |
        */

        if ((float) $course->price > 0) {
            return redirect()
                ->route(
                    'course-invitations.show',
                    $invitation->code
                )
                ->with(
                    'info',
                    'This is a paid course. Please proceed to payment to enroll.'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | FREE COURSE
        |--------------------------------------------------------------------------
        */

        $existingEnrollment = Enrollment::where(
            'student_id',
            auth()->id()
        )
            ->where(
                'course_id',
                $course->id
            )
            ->first();

        if ($existingEnrollment) {
            $invitation->update([
                'accepted_at' => now(),
                'accepted_by' => auth()->id(),
            ]);

            return redirect()
                ->route('student.my-courses')
                ->with(
                    'success',
                    'You are already enrolled in this course.'
                );
        }

        Enrollment::create([
            'student_id' => auth()->id(),
            'course_id' => $course->id,
            'status' => 'active',
            'enrolled_at' => now(),
            'progress_percentage' => 0,
        ]);

        $invitation->update([
            'accepted_at' => now(),
            'accepted_by' => auth()->id(),
        ]);

        return redirect()
            ->route('student.my-courses')
            ->with(
                'success',
                'Invitation accepted. You are now enrolled in the course.'
            );
    }

    public function destroy(CourseInvitation $courseInvitation)
    {
        abort_unless(
            $courseInvitation->created_by === auth()->id(),
            403
        );

        $courseInvitation->delete();

        return redirect()
            ->route('course-invitations.index')
            ->with(
                'success',
                'Invitation deleted successfully.'
            );
    }

    public function send(Request $request, CourseInvitation $courseInvitation)
{
    abort_unless(
        $courseInvitation->created_by === auth()->id(),
        403
    );

    $validated = $request->validate([
        'student_id' => [
            'required',
            'integer',
            'exists:users,id',
        ],
    ]);

    $student = User::whereKey($validated['student_id'])
        ->whereHas('roles', function ($query) {
            $query->where('name', 'student');
        })
        ->first();

    if (! $student) {
        return back()->with(
            'error',
            'The selected user is not a student.'
        );
    }

    if (! $courseInvitation->isValid()) {
        return back()->with(
            'error',
            'This invitation is no longer valid.'
        );
    }

    if (
        $courseInvitation->student_id !== null
        && (int) $courseInvitation->student_id !== (int) $student->id
    ) {
        return back()->with(
            'error',
            'This invitation has already been assigned to another student.'
        );
    }

    $courseInvitation->update([
        'student_id' => $student->id,
    ]);

    $courseInvitation->loadMissing('course');

    $courseTitle = $courseInvitation->course?->title
        ?? 'your course';

   $student->notify(
    new PathwiseNotification(
        title: 'Course invitation',
        message:
            'You have been invited to join "'
            . $courseTitle
            . '". Open the invitation to continue.',
        type: 'course_invitation',
        courseId: $courseInvitation->course_id,
        invitationId: $courseInvitation->id,
        invitationCode: $courseInvitation->code
    )
);

    return back()->with(
        'success',
        'Invitation sent successfully to ' . $student->name . '.'
    );
}
}