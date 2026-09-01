<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Certificate;
use App\Models\QuizResult;

class ReportsController extends Controller
{
    public function index()
    {
        $totalStudents = User::whereHas('roles', function ($query) {
            $query->where('name', 'student');
        })->count();

        $totalTeachers = User::whereHas('roles', function ($query) {
            $query->where('name', 'teacher');
        })->count();

        $totalCourses = Course::count();
        $totalEnrollments = Enrollment::count();
        $completedEnrollments = Enrollment::where('status', 'completed')->count();
        $activeEnrollments = Enrollment::where('status', 'active')->count();
        $certificatesIssued = Certificate::count();
        $quizAttempts = QuizResult::count();

        $completionRate = $totalEnrollments > 0
            ? round(($completedEnrollments / $totalEnrollments) * 100, 2)
            : 0;

        $recentQuizResults = QuizResult::with(['student', 'quiz'])
            ->latest()
            ->take(5)
            ->get();

        $recentCertificates = Certificate::with(['student', 'course'])
            ->latest()
            ->take(5)
            ->get();

        $popularCourses = Course::withCount('enrollments')
            ->orderByDesc('enrollments_count')
            ->take(5)
            ->get();

        return view('reports.index', compact(
            'totalStudents',
            'totalTeachers',
            'totalCourses',
            'totalEnrollments',
            'completedEnrollments',
            'activeEnrollments',
            'certificatesIssued',
            'quizAttempts',
            'completionRate',
            'recentQuizResults',
            'recentCertificates',
            'popularCourses'
        ));
    }
}