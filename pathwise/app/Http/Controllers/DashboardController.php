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

        $coursesQuery = Course::where('teacher_id', $teacherId);
        $enrollmentsQuery = Enrollment::whereHas('course', fn ($query) => $query->where('teacher_id', $teacherId));
        $resultsQuery = QuizResult::whereHas('quiz.course', fn ($query) => $query->where('teacher_id', $teacherId));

        $totalCourses = (clone $coursesQuery)->count();
        $totalLessons = Lesson::whereHas('course', fn ($query) => $query->where('teacher_id', $teacherId))->count();
        $totalStudents = (clone $enrollmentsQuery)->distinct('student_id')->count('student_id');
        $totalEnrollments = (clone $enrollmentsQuery)->count();

        $publishedCourses = (clone $coursesQuery)->where('status', 'published')->count();
        $pendingCourses = (clone $coursesQuery)->where('status', 'pending')->count();
        $draftCourses = (clone $coursesQuery)->where('status', 'draft')->count();
        $averageScore = round((float) ((clone $resultsQuery)->avg('percentage') ?? 0), 1);
        $averageProgress = round((float) ((clone $enrollmentsQuery)->avg('progress_percentage') ?? 0), 1);
        $completedEnrollments = (clone $enrollmentsQuery)->where('status', 'completed')->count();

        $recentCourses = Course::with('category')
            ->withCount(['lessons', 'enrollments'])
            ->where('teacher_id', $teacherId)
            ->latest()
            ->take(4)
            ->get();

        $recentQuizResults = QuizResult::with(['student', 'quiz.course'])
            ->whereHas('quiz.course', fn ($query) => $query->where('teacher_id', $teacherId))
            ->latest()
            ->take(5)
            ->get();

        $hour = now()->hour;
        $greeting = $hour < 12 ? 'Good Morning' : ($hour < 18 ? 'Good Afternoon' : 'Good Evening');
        $currentDate = now()->format('l, F d, Y');

        return view('teacher.dashboard', compact(
            'totalCourses',
            'totalLessons',
            'totalStudents',
            'totalEnrollments',
            'publishedCourses',
            'pendingCourses',
            'draftCourses',
            'averageScore',
            'averageProgress',
            'completedEnrollments',
            'recentCourses',
            'recentQuizResults',
            'greeting',
            'currentDate'
        ));
    }
}