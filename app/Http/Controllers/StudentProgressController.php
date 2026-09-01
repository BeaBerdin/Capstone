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
        $enrollments = Enrollment::with([
                'student',
                'course.category',
            ])
            ->whereHas(
                'course',
                function ($query) {
                    $query->where(
                        'teacher_id',
                        auth()->id()
                    );
                }
            )
            ->latest()
            ->get();


        return view(
            'student-progress.index',
            compact('enrollments')
        );
    }
}