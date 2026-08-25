<?php

namespace App\Http\Controllers;

use App\Models\AIRecommendation;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\QuizResult;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $totalStudents = User::count();
        $totalTeachers = User::count();
        $totalCourses = Course::count();
        $totalEnrollments = Enrollment::count();

        return view('dashboard', compact(
            'totalStudents',
            'totalTeachers',
            'totalCourses',
            'totalEnrollments'
        ));
    }

    public function superAdmin()
{
    $totalUsers = User::count();

    $totalStudents = User::whereHas('roles', function ($query) {
        $query->where('name', 'student');
    })->count();

    $totalTeachers = User::whereHas('roles', function ($query) {
        $query->where('name', 'teacher');
    })->count();

    $totalAdmins = User::whereHas('roles', function ($query) {
        $query->where('name', 'admin');
    })->count();

    $totalCourses = Course::count();

    $totalEnrollments = Enrollment::count();

    return view('super-admin.dashboard', compact(
        'totalUsers',
        'totalStudents',
        'totalTeachers',
        'totalAdmins',
        'totalCourses',
        'totalEnrollments'
    ));
}
    public function student()
    {
        $studentId = auth()->id();

        $enrollments = Enrollment::with('course')
            ->where('student_id', $studentId)
            ->latest()
            ->get();

        $recommendedCourse = AIRecommendation::with('course')
            ->where('student_id', $studentId)
            ->latest()
            ->first();

        $quizResults = QuizResult::with('quiz.course.category')
            ->where('student_id', $studentId)
            ->latest()
            ->get();

        $quizzesTaken = $quizResults->count();
        $averageScore = round($quizResults->avg('percentage') ?? 0, 2);
        $certificatesEarned = Certificate::where('student_id', $studentId)->count();

        $completedCourses = Enrollment::where('student_id', $studentId)
            ->where('status', 'completed')
            ->count();

        $activeCourses = Enrollment::where('student_id', $studentId)
            ->where('status', 'active')
            ->count();

        if ($averageScore >= 85) {
            $learningLevel = 'Advanced Learner';
        } elseif ($averageScore >= 75) {
            $learningLevel = 'Intermediate Learner';
        } elseif ($averageScore > 0) {
            $learningLevel = 'Needs Reinforcement';
        } else {
            $learningLevel = 'No quiz data yet';
        }

        $categoryPerformance = QuizResult::select(
                'course_categories.name as category_name',
                DB::raw('ROUND(AVG(quiz_results.percentage), 2) as average_score')
            )
            ->join('quizzes', 'quiz_results.quiz_id', '=', 'quizzes.id')
            ->join('courses', 'quizzes.course_id', '=', 'courses.id')
            ->join('course_categories', 'courses.category_id', '=', 'course_categories.id')
            ->where('quiz_results.student_id', $studentId)
            ->groupBy('course_categories.name')
            ->orderByDesc('average_score')
            ->get();

        $strongestCategory = $categoryPerformance->first();

        $weakestCategory = $categoryPerformance->count() > 1
            ? $categoryPerformance->last()
            : null;

        $chartResults = $quizResults
            ->take(5)
            ->reverse()
            ->values();

        $chartLabels = $chartResults
            ->map(fn ($result, $index) => 'Quiz ' . ($index + 1))
            ->values();

        $chartScores = $chartResults
            ->pluck('percentage')
            ->values();

        return view('student.dashboard', compact(
            'enrollments',
            'recommendedCourse',
            'quizzesTaken',
            'certificatesEarned',
            'quizResults',
            'averageScore',
            'completedCourses',
            'activeCourses',
            'learningLevel',
            'strongestCategory',
            'weakestCategory',
            'categoryPerformance',
            'chartLabels',
            'chartScores'
        ));
    }

    public function certificates()
    {
        $certificates = Certificate::with('course')
            ->where('student_id', auth()->id())
            ->latest()
            ->get();

        return view('student.certificates', compact('certificates'));
    }

    public function teacher()
    {
        $teacherId = auth()->id();

        $totalCourses = Course::where('teacher_id', $teacherId)->count();

        $totalLessons = Lesson::whereHas('course', function ($query) use ($teacherId) {
            $query->where('teacher_id', $teacherId);
        })->count();

        $totalStudents = Enrollment::whereHas('course', function ($query) use ($teacherId) {
            $query->where('teacher_id', $teacherId);
        })
            ->distinct('student_id')
            ->count('student_id');

        $totalEnrollments = Enrollment::whereHas('course', function ($query) use ($teacherId) {
            $query->where('teacher_id', $teacherId);
        })->count();

        $hour = now()->hour;

        if ($hour < 12) {
            $greeting = 'Good Morning';
        } elseif ($hour < 18) {
            $greeting = 'Good Afternoon';
        } else {
            $greeting = 'Good Evening';
        }

        $currentDate = now()->format('l, F d, Y');

        return view('teacher.dashboard', compact(
            'totalCourses',
            'totalLessons',
            'totalStudents',
            'totalEnrollments',
            'greeting',
            'currentDate'
        ));
    }
}