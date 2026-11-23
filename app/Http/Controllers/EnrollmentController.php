<?php

namespace App\Http\Controllers;

use App\Models\Enrollment;
use App\Models\Course;
use App\Models\User;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EnrollmentController extends Controller
{
    /**
     * Quick date ranges on the Enrollments page: key => [button label, summary wording].
     */
    public const DATE_RANGES = [
        'all' => ['All time', ''],
        'today' => ['Today', 'today'],
        '7d' => ['Last 7 days', 'in the last 7 days'],
        '30d' => ['Last 30 days', 'in the last 30 days'],
        '90d' => ['Last 90 days', 'in the last 90 days'],
        'year' => ['This year', 'this year'],
    ];

    public function index(Request $request)
    {
        $range = $request->query('range', 'all');

        // An unknown range (an old or edited link) shows everything.
        if (! array_key_exists($range, self::DATE_RANGES)) {
            $range = 'all';
        }

        // Each range runs up to today; "Last 7 days" includes today.
        $since = match ($range) {
            'today' => today(),
            '7d' => today()->subDays(6),
            '30d' => today()->subDays(29),
            '90d' => today()->subDays(89),
            'year' => today()->startOfYear(),
            default => null,
        };

        // Older rows may have no enrolled_at; their created_at stands in for it.
        $enrollments = Enrollment::with(['student', 'course'])
            ->whereHas('course', fn ($course) => $course->manageableBy($request->user()))
            ->when($since, fn ($query) => $query->whereRaw(
                'DATE(COALESCE(enrolled_at, created_at)) >= ?',
                [$since->toDateString()]
            ))
            ->orderByRaw('COALESCE(enrolled_at, created_at) DESC')
            ->orderByDesc('id')
            ->get();

        return view('enrollments.index', [
            'enrollments' => $enrollments,
            'range' => $range,
            'ranges' => self::DATE_RANGES,
        ]);
    }

    public function create(Request $request)
    {
        $courses = Course::manageableBy($request->user())->orderBy('title')->get();
        $students = $courses->isEmpty() ? collect() : $this->students()->orderBy('name')->get();

        return view('enrollments.create', compact('students', 'courses'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => ['required', Rule::exists('users', 'id'), $this->mustBeStudent(), $this->notAlreadyEnrolled($request)],
            'course_id' => 'required|integer|exists:courses,id',
            'status' => 'required|in:active,completed,cancelled',
            'progress_percentage' => 'required|numeric|min:0|max:100',
        ]);

        Course::authorizeManagement($request->user(), $validated['course_id']);

        $this->saveWithoutDuplicate(fn () => Enrollment::create([
            'student_id' => $request->student_id,
            'course_id' => $validated['course_id'],
            'status' => $request->status,
            'enrolled_at' => now(),
            'completed_at' => $request->status === 'completed' ? now() : null,
            'progress_percentage' => $request->progress_percentage,
        ]));

        return redirect()
            ->route('enrollments.index')
            ->with('success', 'Enrollment created successfully.');
    }

    public function edit(Request $request, Enrollment $enrollment)
    {
        Course::authorizeManagement($request->user(), $enrollment->course_id);

        $students = $this->students()->orderBy('name')->get();
        $courses = Course::manageableBy($request->user())->orderBy('title')->get();

        return view('enrollments.edit', compact('enrollment', 'students', 'courses'));
    }

    public function update(Request $request, Enrollment $enrollment)
    {
        Course::authorizeManagement($request->user(), $enrollment->course_id);

        $validated = $request->validate([
            'student_id' => ['required', Rule::exists('users', 'id'), $this->mustBeStudent(), $this->notAlreadyEnrolled($request, $enrollment)],
            'course_id' => 'required|integer|exists:courses,id',
            'status' => 'required|in:active,completed,cancelled',
            'progress_percentage' => 'required|numeric|min:0|max:100',
        ]);

        Course::authorizeManagement($request->user(), $validated['course_id']);

        $this->saveWithoutDuplicate(fn () => $enrollment->update([
            'student_id' => $request->student_id,
            'course_id' => $validated['course_id'],
            'status' => $request->status,
            'completed_at' => $request->status === 'completed' ? now() : null,
            'progress_percentage' => $request->progress_percentage,
        ]));

        return redirect()
            ->route('enrollments.index')
            ->with('success', 'Enrollment updated successfully.');
    }

    public function destroy(Request $request, Enrollment $enrollment)
    {
        Course::authorizeManagement($request->user(), $enrollment->course_id);

        $enrollment->delete();

        return redirect()
            ->route('enrollments.index')
            ->with('success', 'Enrollment deleted successfully.');
    }

    /**
     * Only student accounts can be enrolled in a course.
     *
     * @return Builder<\App\Models\User>
     */
    private function students(): Builder
    {
        return User::query()->whereHas('roles', function (Builder $query) {
            $query->where('name', 'student');
        });
    }

    public const DUPLICATE_MESSAGE = 'This student is already enrolled in this course.';

    /**
     * One enrollment per student and course (the enrollments table has a
     * unique index on student_id + course_id). Checked before saving so the
     * admin sees a message instead of a database error.
     */
    private function notAlreadyEnrolled(Request $request, ?Enrollment $ignore = null): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($request, $ignore) {
            $exists = Enrollment::where('student_id', $value)
                ->where('course_id', $request->input('course_id'))
                ->when($ignore, fn ($query) => $query->whereKeyNot($ignore->getKey()))
                ->exists();

            if ($exists) {
                $fail(self::DUPLICATE_MESSAGE);
            }
        };
    }

    /**
     * Two saves at the same moment can both pass the check above; the unique
     * index then refuses the second one, which is shown as the same message.
     */
    private function saveWithoutDuplicate(Closure $save): mixed
    {
        try {
            return $save();
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['student_id' => self::DUPLICATE_MESSAGE]);
        }
    }

    private function mustBeStudent(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) {
            if (! $this->students()->whereKey($value)->exists()) {
                $fail('Only student accounts can be enrolled in a course.');
            }
        };
    }
}
