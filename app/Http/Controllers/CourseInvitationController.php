<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseInvitation;
use App\Models\Enrollment;
use App\Models\User;
use App\Notifications\PathwiseNotification;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;


class CourseInvitationController extends Controller
{
    private const STUDENT_SEARCH_MIN_LENGTH = 3;

    private const STUDENT_SEARCH_LIMIT = 10;

   public function index(Request $request)
{
    $invitations = CourseInvitation::with('course')
        ->where('created_by', auth()->id())
        ->latest()
        ->get();

    // Invitations can only be created for published courses.
    $courses = Course::where('teacher_id', auth()->id())
        ->where('status', 'published')
        ->orderBy('title')
        ->get();

    /*
    | Teachers never see a full student list. They search one invitation
    | at a time by name or email (at least 3 characters, up to 10 results).
    */
    $searchInvitationId = (int) $request->query('invitation');
    $searchTerm = trim((string) $request->query('q'));
    $searchResults = collect();
    $searchError = null;

    $searchInvitation = $invitations->firstWhere('id', $searchInvitationId);

    if ($searchInvitation) {
        if (mb_strlen($searchTerm) < self::STUDENT_SEARCH_MIN_LENGTH) {
            $searchError = 'Type at least '.self::STUDENT_SEARCH_MIN_LENGTH.' characters of a name or email.';
        } else {
            $searchResults = $this->searchStudents($searchInvitation, $searchTerm);

            if ($searchResults->isEmpty()) {
                $searchError = 'No matching students who are not already enrolled in this course.';
            }
        }
    }

    return view('course-invitations.index', compact(
        'invitations',
        'courses',
        'searchInvitationId',
        'searchTerm',
        'searchResults',
        'searchError'
    ));
}

    public function store(Request $request)
    {
       $validated = $request->validate([
    'course_id' => [
        'required',
        'integer',
        Rule::exists('courses', 'id')
            ->where('teacher_id', auth()->id())
            ->where('status', 'published'),
    ],
    // The form uses <input type="datetime-local">, which sends the time
    // with or without seconds depending on the browser.
    'expires_at' => [
        'nullable',
        'date_format:Y-m-d\TH:i,Y-m-d\TH:i:s',
    ],
], [
    'course_id.exists' => 'Choose one of your published courses.',
    'expires_at.date_format' => 'Enter the expiry as a date and time.',
]);

$expiresAt = null;

if (! empty($validated['expires_at'])) {
    // The teacher enters Philippine time; it is stored in UTC.
    $expiresAt = Carbon::parse(
        $validated['expires_at'],
        'Asia/Manila'
    )->utc();

    if ($expiresAt->isPast()) {
        throw ValidationException::withMessages([
            'expires_at' => 'The expiry must be in the future.',
        ]);
    }
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

        // A course that was unpublished after the invitation was created
        // cannot be joined.
        if (strtolower((string) $course->status) !== 'published') {
            return redirect()
                ->route(
                    'course-invitations.show',
                    $invitation->code
                )
                ->with(
                    'error',
                    'This course is not open for enrollment right now.'
                );
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

        // A cancelled enrollment must be restored by the administrator.
        if ($existingEnrollment && ! $existingEnrollment->grantsAccess()) {
            return redirect()
                ->route('student.course.show', $course)
                ->with(
                    'error',
                    'Your enrollment in this course was cancelled. Please contact the course administrator to restore it.'
                );
        }

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

    $student = $this->invitableStudents($courseInvitation)
        ->whereKey($validated['student_id'])
        ->first();

    if (! $student) {
        return back()->with(
            'error',
            'Choose a student who is not already enrolled in this course.'
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

    /**
     * Students who can receive this invitation: any student who has no
     * enrollment record in the invitation's course yet.
     */
    private function invitableStudents(CourseInvitation $invitation): Builder
    {
        return User::query()
            ->whereHas('roles', function (Builder $query) {
                $query->where('name', 'student');
            })
            ->whereNotIn(
                'id',
                Enrollment::select('student_id')
                    ->where('course_id', $invitation->course_id)
            );
    }

    /**
     * Up to 10 invitable students whose name or email contains the term.
     */
    private function searchStudents(CourseInvitation $invitation, string $term)
    {
        $like = '%'.$term.'%';

        return $this->invitableStudents($invitation)
            ->where(function (Builder $query) use ($like) {
                $query->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like);
            })
            ->orderBy('name')
            ->limit(self::STUDENT_SEARCH_LIMIT)
            ->get(['id', 'name', 'email']);
    }
}