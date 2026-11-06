<?php

namespace App\Http\Controllers;

use App\Models\Enrollment;
use App\Models\Course;
use App\Models\User;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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

    public function create()
    {
        $students = $this->students()->orderBy('name')->get();
        $courses = Course::orderBy('title')->get();

        return view('enrollments.create', compact('students', 'courses'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'student_id' => ['required', Rule::exists('users', 'id'), $this->mustBeStudent()],
            'course_id' => 'required|exists:courses,id',
            'status' => 'required|in:active,completed,cancelled',
            'progress_percentage' => 'required|numeric|min:0|max:100',
        ]);

        Enrollment::create([
            'student_id' => $request->student_id,
            'course_id' => $request->course_id,
            'status' => $request->status,
            'enrolled_at' => now(),
            'completed_at' => $request->status === 'completed' ? now() : null,
            'progress_percentage' => $request->progress_percentage,
        ]);

        return redirect()
            ->route('enrollments.index')
            ->with('success', 'Enrollment created successfully.');
    }

    public function edit(Enrollment $enrollment)
    {
        $students = $this->students()->orderBy('name')->get();
        $courses = Course::orderBy('title')->get();

        return view('enrollments.edit', compact('enrollment', 'students', 'courses'));
    }

    public function update(Request $request, Enrollment $enrollment)
    {
        $request->validate([
            'student_id' => ['required', Rule::exists('users', 'id'), $this->mustBeStudent()],
            'course_id' => 'required|exists:courses,id',
            'status' => 'required|in:active,completed,cancelled',
            'progress_percentage' => 'required|numeric|min:0|max:100',
        ]);

        $enrollment->update([
            'student_id' => $request->student_id,
            'course_id' => $request->course_id,
            'status' => $request->status,
            'completed_at' => $request->status === 'completed' ? now() : null,
            'progress_percentage' => $request->progress_percentage,
        ]);

        return redirect()
            ->route('enrollments.index')
            ->with('success', 'Enrollment updated successfully.');
    }

    public function destroy(Enrollment $enrollment)
    {
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

    private function mustBeStudent(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) {
            if (! $this->students()->whereKey($value)->exists()) {
                $fail('Only student accounts can be enrolled in a course.');
            }
        };
    }
}
