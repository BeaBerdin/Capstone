<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseInvitation;
use App\Models\Enrollment;
use App\Models\Transaction;
use App\Models\User;
use App\Notifications\PathwiseNotification;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CourseInvitationController extends Controller
{
    private const INVALID_CODE = 'That invitation is not available to your account. Check the code or ask your teacher.';
    private const PREVIOUSLY_REDEEMED = 'You previously redeemed this invitation. Contact the course administrator for access.';

    private const CANCELLED_ENROLLMENT = 'Your enrollment was cancelled. Please contact the course administrator for help.';

    public function index(Request $request)
    {
        $teacherId = $request->user()->id;
        $invitations = CourseInvitation::with(['course', 'parentInvitation', 'student'])
            ->withCount('redeemedBy')
            ->where('created_by', $teacherId)
            ->whereHas('course', fn (Builder $course) => $course->where('teacher_id', $teacherId))
            ->latest()
            ->get();

        $courses = Course::where('teacher_id', $teacherId)->where('status', 'published')->orderBy('title')->get();
        $searchInvitationId = $request->integer('invitation');
        $query = $request->query('q');
        $searchTerm = is_string($query) ? trim($query) : '';
        $searchResults = collect();
        $searchError = null;
        $searchInvitation = $invitations->firstWhere('id', $searchInvitationId);

        if ($searchInvitation && $this->isShareable($searchInvitation) && $searchInvitation->isValid()) {
            if (mb_strlen($searchTerm) < 3 || mb_strlen($searchTerm) > 255) {
                $searchError = 'Type at least 3 characters of a student name or email (up to 255).';
            } else {
                $like = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $searchTerm).'%';
                $searchResults = $this->invitableStudents($searchInvitation)
                    ->where(fn (Builder $students) => $students
                        ->whereRaw("name LIKE ? ESCAPE '!'", [$like])
                        ->orWhereRaw("email LIKE ? ESCAPE '!'", [$like]))
                    ->orderBy('name')
                    ->limit(10)
                    ->get(['id', 'name', 'email']);

                if ($searchResults->isEmpty()) {
                    $searchError = 'No matching students are eligible to receive this invitation.';
                }
            }
        } else {
            $searchInvitationId = 0;
        }

        return view('course-invitations.index', compact(
            'invitations', 'courses', 'searchInvitationId', 'searchTerm', 'searchResults', 'searchError'
        ));
    }

    public function store(Request $request)
    {
        $teacherId = $request->user()->id;
        $validated = $request->validate([
            'course_id' => [
                'required',
                'integer',
                Rule::exists('courses', 'id')->where('teacher_id', $teacherId)->where('status', 'published'),
            ],
            'expires_at' => ['nullable', 'date_format:Y-m-d\TH:i,Y-m-d\TH:i:s'],
        ], [
            'course_id.exists' => 'Choose one of your published courses.',
            'expires_at.date_format' => 'Enter the expiry as a date and time.',
        ]);

        $expiresAt = empty($validated['expires_at'])
            ? null
            : Carbon::parse($validated['expires_at'], 'Asia/Manila')->setTimezone(config('app.timezone'));

        DB::transaction(function () use ($validated, $teacherId, $expiresAt) {
            $course = Course::lockForUpdate()->find($validated['course_id']);
            abort_unless($course && (int) $course->teacher_id === (int) $teacherId, 403);

            if ($course->status !== 'published') {
                throw ValidationException::withMessages(['course_id' => 'Choose one of your published courses.']);
            }
            if ($expiresAt?->lte(now())) {
                throw ValidationException::withMessages(['expires_at' => 'The expiry must be in the future.']);
            }

            CourseInvitation::create([
                'course_id' => $course->id,
                'created_by' => $teacherId,
                'code' => $this->newCode(),
                'expires_at' => $expiresAt,
            ]);
        }, 3);

        return redirect()->route('course-invitations.index')->with('success', 'Invitation code created.');
    }

    public function show(Request $request, string $code)
    {
        $student = $request->user();
        $approvalPending = $student->awaitingStudentApproval();
        if ($approvalPending || ! $student->hasVerifiedEmail()) {
            return $this->invitationPage(compact('approvalPending'));
        }

        $studentId = (int) $student->id;
        $invitation = $this->findForStudent($code, $studentId);
        if (! $invitation) {
            return redirect()->route('student.invitations.index')->with('error', self::INVALID_CODE);
        }

        $enrollment = $this->studentEnrollment($studentId, $invitation->course_id)->first();
        $unavailableMessage = $enrollment && ! $enrollment->grantsAccess()
            ? self::CANCELLED_ENROLLMENT
            : $invitation->unavailableMessage();
        if (! $enrollment && $unavailableMessage === null) {
            if (CourseInvitation::redeemedFor($studentId, $invitation->course_id)->exists()) {
                $unavailableMessage = self::PREVIOUSLY_REDEEMED;
            } elseif (Transaction::blockingCoursePayment($studentId, $invitation->course_id)->exists()) {
                $unavailableMessage = Transaction::ENROLLMENT_PAYMENT_CONFLICT;
            }
        }

        return $this->invitationPage(compact('invitation', 'enrollment', 'unavailableMessage'));
    }

    private function invitationPage(array $data): \Illuminate\Http\Response
    {
        return response()->view('course-invitations.show', $data, 200, [
            'Cache-Control' => 'private, no-store',
            'Referrer-Policy' => 'no-referrer',
        ]);
    }

    public function join(Request $request)
    {
        $validated = $request->validate(['code' => ['required', 'string', 'max:2048']], [
            'code.required' => 'Enter the invite code or paste the invitation link.',
        ]);
        $code = CourseInvitation::normalizeCode($validated['code']);
        if ($code === null) {
            return back()->withInput()->withErrors(['code' => 'Enter the 12-character code or paste the invitation link.']);
        }

        $invitation = $this->findForStudent($code, (int) $request->user()->id);
        if (! $invitation) {
            return back()->withInput()->withErrors(['code' => self::INVALID_CODE]);
        }

        return redirect()->route('course-invitations.show', $invitation->code);
    }

    public function accept(Request $request, string $code)
    {
        $student = $request->user();
        $studentId = (int) $student->id;
        $invitation = $this->findForStudent($code, $studentId);
        if (! $invitation) {
            return redirect()->route('student.invitations.index')->with('error', self::INVALID_CODE);
        }

        try {
            $joined = DB::transaction(function () use ($studentId, $invitation) {
                $course = Course::lockForUpdate()->find($invitation->course_id);
                $locked = $course ? CourseInvitation::findForLockedCourse($course, $invitation->id) : null;
                if (! $locked || ! $locked->canBeOpenedBy($studentId)) {
                    throw ValidationException::withMessages(['invitation' => self::INVALID_CODE]);
                }

                $existing = $this->studentEnrollment($studentId, $course->id)->lockForUpdate()->first();
                if ($existing && ! $existing->grantsAccess()) {
                    throw ValidationException::withMessages(['invitation' => self::CANCELLED_ENROLLMENT]);
                }
                if ($existing && $locked->courseIsAvailable()) {
                    return false;
                }
                if ($message = $locked->unavailableMessage()) {
                    throw ValidationException::withMessages(['invitation' => $message]);
                }
                if (CourseInvitation::redeemedFor($studentId, $course->id)->exists()) {
                    throw ValidationException::withMessages(['invitation' => self::PREVIOUSLY_REDEEMED]);
                }
                if (Transaction::blockingCoursePayment($studentId, $course->id)->lockForUpdate()->first()) {
                    throw ValidationException::withMessages(['invitation' => Transaction::ENROLLMENT_PAYMENT_CONFLICT]);
                }

                $enrollment = Enrollment::create([
                    'student_id' => $studentId,
                    'course_id' => $course->id,
                    'status' => 'active',
                    'enrolled_at' => now(),
                    'progress_percentage' => 0,
                ]);
                $locked->redeemedBy()->attach($studentId, ['enrollment_id' => $enrollment->id]);

                return true;
            }, 3);
        } catch (ValidationException $exception) {
            return redirect()->route('course-invitations.show', $invitation->code)
                ->withErrors($exception->errors());
        } catch (UniqueConstraintViolationException $exception) {
            // Ordinary enrollment or checkout can win the student/course unique key.
            $existing = $this->studentEnrollment($studentId, $invitation->course_id)->first();
            if (! $existing) {
                throw $exception;
            }
            if (! $existing->grantsAccess()) {
                return redirect()->route('course-invitations.show', $invitation->code)
                    ->withErrors(['invitation' => self::CANCELLED_ENROLLMENT]);
            }
            $joined = false;
        }

        if ($joined) {
            try {
                $student->notify(new PathwiseNotification(
                    title: 'Enrollment confirmed',
                    message: 'You joined "'.$invitation->course->title.'" through an invitation.',
                    type: 'enrollment_activated',
                    courseId: $invitation->course_id,
                    invitationId: $invitation->id
                ));
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        return redirect()->route('student.learn.course', $invitation->course_id)->with(
            'success',
            $joined ? 'Invitation accepted. You joined the course for free.' : 'You are already enrolled in this course.'
        );
    }

    public function studentIndex(Request $request)
    {
        $studentId = (int) $request->user()->id;
        $invitations = CourseInvitation::with(['course.teacher', 'parentInvitation'])
            ->withExists(['redeemedBy as redeemed_by_student' => fn (Builder $students) => $students->whereKey($studentId)])
            ->where(fn (Builder $invitations) => $invitations->where('student_id', $studentId)
                ->orWhereHas('redeemedBy', fn (Builder $students) => $students->whereKey($studentId)))
            ->latest()
            ->paginate(15);
        $enrollments = Enrollment::where('student_id', $studentId)
            ->whereIn('course_id', $invitations->getCollection()->pluck('course_id'))
            ->get()
            ->keyBy('course_id');

        return view('course-invitations.student-index', compact('invitations', 'enrollments'));
    }

    public function destroy(Request $request, CourseInvitation $courseInvitation)
    {
        abort_unless((int) $courseInvitation->created_by === (int) $request->user()->id, 403);

        DB::transaction(function () use ($request, $courseInvitation) {
            $invitation = $this->lockTeacherInvitation($request, $courseInvitation);
            if (! $invitation->deactivated_at) {
                $invitation->update(['deactivated_at' => now()]);
            }
        }, 3);

        return redirect()->route('course-invitations.index')->with('success', 'Invitation deactivated.');
    }

    public function send(Request $request, CourseInvitation $courseInvitation)
    {
        abort_unless((int) $courseInvitation->created_by === (int) $request->user()->id, 403);
        $validated = $request->validate(['student_id' => ['required', 'integer']]);

        [$delivery, $student] = DB::transaction(function () use ($request, $courseInvitation, $validated) {
            $invitation = $this->lockTeacherInvitation($request, $courseInvitation);
            if (! $this->isShareable($invitation) || ! $invitation->isValid()) {
                throw ValidationException::withMessages(['student_id' => 'Choose an active shareable code to send.']);
            }

            $student = $this->invitableStudents($invitation)->whereKey($validated['student_id'])->first();
            if (! $student) {
                throw ValidationException::withMessages(['student_id' => 'Choose an eligible student who has no enrollment for this course.']);
            }

            $delivery = CourseInvitation::where('parent_invitation_id', $invitation->id)
                ->where('student_id', $student->id)
                ->first();

            if ($delivery && ! $delivery->isValid()) {
                throw ValidationException::withMessages(['student_id' => 'Create a new shareable code to send this student a fresh invitation.']);
            }

            $delivery ??= CourseInvitation::create([
                'course_id' => $invitation->course_id,
                'created_by' => $invitation->created_by,
                'student_id' => $student->id,
                'is_private' => true,
                'parent_invitation_id' => $invitation->id,
                'code' => $this->newCode(),
                'expires_at' => $invitation->expires_at,
            ]);

            return [$delivery, $student];
        }, 3);

        try {
            $student->notify(new PathwiseNotification(
                title: 'Course invitation',
                message: 'You have been invited to join "'.$delivery->course->title.'". Open the invitation to join for free.',
                type: 'course_invitation',
                courseId: $delivery->course_id,
                invitationId: $delivery->id,
                invitationCode: $delivery->code
            ));
        } catch (\Throwable $exception) {
            report($exception);

            return back()->with('error', 'The invitation appears on the student’s Course Invitations page. The notification could not be sent.');
        }

        return back()->with('success', 'Private invitation sent to '.$student->name.'.');
    }

    private function findForStudent(string $input, int $studentId): ?CourseInvitation
    {
        $code = CourseInvitation::normalizeCode($input);
        if ($code === null) {
            return null;
        }

        $invitation = CourseInvitation::with(['course.teacher', 'parentInvitation'])->where('code', $code)->first();

        return $invitation?->canBeOpenedBy($studentId) ? $invitation : null;
    }

    private function studentEnrollment(int $studentId, int $courseId): Builder
    {
        return Enrollment::where('student_id', $studentId)->where('course_id', $courseId);
    }

    private function lockTeacherInvitation(Request $request, CourseInvitation $invitation): CourseInvitation
    {
        $teacherId = (int) $request->user()->id;
        $course = Course::lockForUpdate()->find($invitation->course_id);
        abort_unless($course && (int) $course->teacher_id === $teacherId, 403);

        $locked = CourseInvitation::findForLockedCourse($course, $invitation->id);
        abort_unless($locked && (int) $locked->created_by === $teacherId, 403);

        return $locked;
    }

    private function isShareable(CourseInvitation $invitation): bool
    {
        return ! $invitation->is_private && $invitation->student_id === null && $invitation->parent_invitation_id === null;
    }

    private function newCode(): string
    {
        do {
            $code = Str::upper(Str::random(12));
        } while (CourseInvitation::where('code', $code)->exists());

        return $code;
    }

    private function invitableStudents(CourseInvitation $invitation): Builder
    {
        return User::where('is_active', true)
            ->whereHas('roles', fn (Builder $roles) => $roles->where('name', 'student'))
            ->whereNotIn('id', Enrollment::where('course_id', $invitation->course_id)->select('student_id'));
    }
}