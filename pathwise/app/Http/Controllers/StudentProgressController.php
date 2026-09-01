<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Enrollment;
use Illuminate\Http\Request;

class StudentProgressController extends Controller
{
    public function index(Request $request)
    {
        $query = Enrollment::with(['student', 'course.category']);
        $courses = Course::orderBy('title')->get();

        if (auth()->user()->hasRole('teacher')) {
            $teacherId = auth()->id();
            $query->whereHas('course', fn ($q) => $q->where('teacher_id', $teacherId));
            $courses = Course::where('teacher_id', $teacherId)->orderBy('title')->get();
        }

        if ($request->filled('course') && $request->course !== 'all') {
            $query->where('course_id', $request->course);
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->whereHas('student', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $enrollments = $query->latest()->get();

        return view('student-progress.index', compact('enrollments', 'courses'));
    }
}
