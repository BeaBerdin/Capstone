<?php

namespace App\Http\Controllers;

use App\Models\Enrollment;

class StudentProgressController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | TEACHER - STUDENT PROGRESS
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        /*
         * Teachers see their own courses only. The Administrator (Department
         * Head) sees every course: "Student Progress Reports" (Fig. 36).
         */
        $enrollments = Enrollment::with([
                'student',
                'course.category',
            ])
            ->when(
                ! auth()->user()->hasRole('admin'),
                fn ($query) => $query->whereHas(
                    'course',
                    fn ($course) => $course->where('teacher_id', auth()->id())
                )
            )
            ->latest()
            ->get();


        return view(
            'student-progress.index',
            compact('enrollments')
        );
    }
}