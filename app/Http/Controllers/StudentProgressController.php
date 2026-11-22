<?php

namespace App\Http\Controllers;

use App\Models\Enrollment;
use Illuminate\Http\Request;

class StudentProgressController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | TEACHER - STUDENT PROGRESS
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        $user = $request->user();
        $adminRoute = $request->routeIs('student-progress.index');

        $enrollments = Enrollment::with([
                'student',
                'course.category',
            ])
            ->whereHas('course', fn ($course) => $adminRoute
                ? $course->manageableBy($user)
                : $course->where('teacher_id', $user->id))
            ->latest()
            ->get();


        return view(
            'student-progress.index',
            compact('enrollments')
        );
    }
}