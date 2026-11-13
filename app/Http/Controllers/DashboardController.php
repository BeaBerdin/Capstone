<?php

namespace App\Http\Controllers;

use App\Models\AIRecommendation;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\QuizResult;
use App\Models\Submission;
use App\Models\Transaction;
use App\Models\User;
use App\Support\MailSettings;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $totalStudents = User::whereHas('roles', fn ($query) => $query->where('name', 'student'))->count();
        $totalTeachers = User::whereHas('roles', fn ($query) => $query->where('name', 'teacher'))->count();
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

    /*
    | Summary cards (Manuscript, Super Admin Dashboard): Total Users,
    | Pending Role Assignments, Payments to Verify and System Status.
    */
    $pendingRoleAssignments = User::doesntHave('roles')->count();

    $paymentsToVerify = Transaction::where('status', 'pending')->count();

    // Configuration checks only; no keys or passwords are shown.
    $systemChecks = [
        'Database' => true,
        'Gemini AI' => filled(config('services.gemini.api_key')),
        'PayMongo payments' => filled(config('services.paymongo.secret_key'))
            && filled(config('services.paymongo.public_key')),
        // Same check the email notifications use.
        'Email delivery' => MailSettings::isConfigured(),
    ];

    $systemIssues = collect($systemChecks)->reject()->keys()->all();

    $recentActivity = $this->superAdminRecentActivity();

    return view('super-admin.dashboard', compact(
        'totalUsers',
        'totalStudents',
        'totalTeachers',
        'totalAdmins',
        'totalCourses',
        'totalEnrollments',
        'pendingRoleAssignments',
        'paymentsToVerify',
        'systemChecks',
        'systemIssues',
        'recentActivity'
    ));
}

    /**
     * The latest registrations, role assignments, enrollments and payments,
     * newest first, each tagged with its status.
     *
     * @return \Illuminate\Support\Collection<int, array{type: string, title: string, detail: string, status: string, at: \Carbon\CarbonInterface}>
     */
    private function superAdminRecentActivity(int $limit = 8)
    {
        $registrations = User::latest()->take($limit)->get()
            ->map(fn (User $user) => [
                'type' => 'Registration',
                'title' => $user->name,
                'detail' => 'New account',
                'status' => $user->hasVerifiedEmail() ? 'Verified' : 'Pending',
                'at' => $user->created_at,
            ]);

        $roleAssignments = DB::table('user_roles')
            ->join('users', 'users.id', '=', 'user_roles.user_id')
            ->join('roles', 'roles.id', '=', 'user_roles.role_id')
            ->whereNotNull('user_roles.created_at')
            ->orderByDesc('user_roles.created_at')
            ->take($limit)
            ->get(['users.name', 'roles.name as role', 'user_roles.created_at'])
            ->map(fn ($row) => [
                'type' => 'Role assignment',
                'title' => $row->name,
                'detail' => 'Assigned role: '.str_replace('_', ' ', $row->role),
                'status' => 'Verified',
                'at' => \Illuminate\Support\Carbon::parse($row->created_at),
            ]);

        $enrollments = Enrollment::with(['student', 'course'])->latest()->take($limit)->get()
            ->map(fn (Enrollment $enrollment) => [
                'type' => 'Enrollment',
                'title' => $enrollment->student?->name ?? 'Student',
                'detail' => $enrollment->course?->title ?? 'Course',
                'status' => ucfirst((string) $enrollment->status),
                'at' => $enrollment->created_at,
            ]);

        $payments = Transaction::with(['student', 'course'])->latest()->take($limit)->get()
            ->map(fn (Transaction $transaction) => [
                'type' => 'Payment',
                'title' => $transaction->student?->name ?? 'Student',
                'detail' => '₱'.number_format((float) $transaction->amount, 2).' · '.($transaction->course?->title ?? 'Course'),
                'status' => match ($transaction->status) {
                    'approved' => 'Verified',
                    'rejected' => 'Rejected',
                    default => 'Pending',
                },
                'at' => $transaction->created_at,
            ]);

        return $registrations
            ->concat($roleAssignments)
            ->concat($enrollments)
            ->concat($payments)
            ->filter(fn (array $item) => $item['at'] !== null)
            ->sortByDesc(fn (array $item) => $item['at']->getTimestamp())
            ->take($limit)
            ->values();
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

        return view('Student.dashboard', compact(
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

        return view('Student.certificates', compact('certificates'));
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

        /*
        | AI evaluations awaiting the teacher's review (Manuscript Fig. 19:
        | the Teacher Dashboard shows AI evaluations awaiting approval).
        | Only the teacher's own courses; a submission counts while it is
        | still "submitted" (not yet graded or returned).
        */
        $ungradedInMyCourses = fn () => Submission::where('status', 'submitted')
            ->whereHas('assignment.course', fn ($query) => $query->where('teacher_id', $teacherId));

        $aiAwaitingCount = $ungradedInMyCourses()
            ->where('ai_evaluation_status', 'completed')
            ->count();

        $aiAwaitingSubmissions = $ungradedInMyCourses()
            ->where('ai_evaluation_status', 'completed')
            ->with(['student:id,name', 'assignment:id,course_id,title,max_score', 'assignment.course:id,title'])
            ->orderByDesc('ai_evaluated_at')
            ->orderByDesc('id')
            ->take(5)
            ->get();

        // Failed, or still "processing" long after the request should have ended.
        $aiUnfinished = $ungradedInMyCourses()
            ->where(function ($query) {
                $query->where('ai_evaluation_status', 'failed')
                    ->orWhere(function ($query) {
                        $query->where('ai_evaluation_status', 'processing')
                            ->where('updated_at', '<', now()->subMinutes(Submission::AI_PROCESSING_TIMEOUT_MINUTES));
                    });
            })
            ->orderByDesc('updated_at')
            ->get(['id', 'updated_at']);

        return view('Teacher.dashboard', compact(
            'totalCourses',
            'totalLessons',
            'totalStudents',
            'totalEnrollments',
            'greeting',
            'currentDate',
            'aiAwaitingCount',
            'aiAwaitingSubmissions',
            'aiUnfinished'
        ));
    }
}
