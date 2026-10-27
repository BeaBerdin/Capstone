<?php

namespace App\Http\Controllers;

use App\Models\Enrollment;
use App\Models\Course;
use App\Models\User;
use Illuminate\Http\Request;

class EnrollmentController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
        ]);

        $from = $filters['from'] ?? null;
        $to = $filters['to'] ?? null;

        // Older rows may have no enrolled_at; their created_at stands in for it.
        $enrolledOn = 'DATE(COALESCE(enrolled_at, created_at))';

        $enrollments = Enrollment::with(['student', 'course'])
            ->when($from, fn ($query) => $query->whereRaw("{$enrolledOn} >= ?", [$from]))
            ->when($to, fn ($query) => $query->whereRaw("{$enrolledOn} <= ?", [$to]))
            ->orderByRaw('COALESCE(enrolled_at, created_at) DESC')
            ->orderByDesc('id')
            ->get();

        return view('enrollments.index', compact('enrollments', 'from', 'to'));
    }

    public function create()
    {
        $students = User::orderBy('name')->get();
        $courses = Course::orderBy('title')->get();

        return view('enrollments.create', compact('students', 'courses'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:users,id',
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
        $students = User::orderBy('name')->get();
        $courses = Course::orderBy('title')->get();

        return view('enrollments.edit', compact('enrollment', 'students', 'courses'));
    }

    public function update(Request $request, Enrollment $enrollment)
    {
        $request->validate([
            'student_id' => 'required|exists:users,id',
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
}