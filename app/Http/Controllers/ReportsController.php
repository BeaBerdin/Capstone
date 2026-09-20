<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\QuizResult;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Http\Request;

class ReportsController extends Controller
{
    /**
     * Super Admin system reports.
     */
    public function index(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | Report Filters
        |--------------------------------------------------------------------------
        */

        $range = (string) $request->input('range', 'all');

        $startDate = match ($range) {
            '30' => now()->subDays(30)->startOfDay(),
            '90' => now()->subDays(90)->startOfDay(),
            '365' => now()->subDays(365)->startOfDay(),
            default => null,
        };

        /*
        |--------------------------------------------------------------------------
        | Users
        |--------------------------------------------------------------------------
        */

        $totalStudents = User::whereHas('roles', function ($query) {
            $query->where('name', 'student');
        })->count();

        $totalTeachers = User::whereHas('roles', function ($query) {
            $query->where('name', 'teacher');
        })->count();

        $totalAdmins = User::whereHas('roles', function ($query) {
            $query->where('name', 'admin');
        })->count();

        /*
        |--------------------------------------------------------------------------
        | Courses
        |--------------------------------------------------------------------------
        */

        $courseQuery = Course::query();

        if ($startDate) {
            $courseQuery->where('created_at', '>=', $startDate);
        }

        $totalCourses = $courseQuery->count();

        /*
        |--------------------------------------------------------------------------
        | Enrollments
        |--------------------------------------------------------------------------
        */

        $enrollmentQuery = Enrollment::query();

        if ($startDate) {
            $enrollmentQuery->where('created_at', '>=', $startDate);
        }

        $totalEnrollments = $enrollmentQuery->count();

        $completedEnrollments = (clone $enrollmentQuery)
            ->where('status', 'completed')
            ->count();

        $activeEnrollments = (clone $enrollmentQuery)
            ->where('status', 'active')
            ->count();

        $completionRate = $totalEnrollments > 0
            ? round(($completedEnrollments / $totalEnrollments) * 100, 2)
            : 0;

        /*
        |--------------------------------------------------------------------------
        | Certificates
        |--------------------------------------------------------------------------
        */

        $certificateQuery = Certificate::query();

        if ($startDate) {
            $certificateQuery->where('created_at', '>=', $startDate);
        }

        $certificatesIssued = $certificateQuery->count();

        /*
        |--------------------------------------------------------------------------
        | Quiz Results
        |--------------------------------------------------------------------------
        */

        $quizResultQuery = QuizResult::query();

        if ($startDate) {
            $quizResultQuery->where('created_at', '>=', $startDate);
        }

        $quizAttempts = $quizResultQuery->count();

        $averageQuizScore = round(
            (float) ($quizResultQuery->avg('percentage') ?? 0),
            2
        );

        $passedQuizAttempts = (clone $quizResultQuery)
            ->where('remarks', 'passed')
            ->count();

        $failedQuizAttempts = (clone $quizResultQuery)
            ->where('remarks', 'failed')
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Assignments / Submissions
        |--------------------------------------------------------------------------
        */

        $assignmentQuery = Assignment::query();

        if ($startDate) {
            $assignmentQuery->where('created_at', '>=', $startDate);
        }

        $totalAssignments = $assignmentQuery->count();

        $submissionQuery = Submission::query();

        if ($startDate) {
            $submissionQuery->where('created_at', '>=', $startDate);
        }

        $totalSubmissions = $submissionQuery->count();

        $gradedSubmissions = (clone $submissionQuery)
            ->where('status', 'graded')
            ->count();

        $pendingSubmissions = (clone $submissionQuery)
            ->where('status', 'submitted')
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Transactions / Revenue
        |--------------------------------------------------------------------------
        */

        $transactionQuery = \App\Models\Transaction::query();

        if ($startDate) {
            $transactionQuery->where('created_at', '>=', $startDate);
        }

        $totalTransactions = $transactionQuery->count();

        $approvedTransactions = (clone $transactionQuery)
            ->where('status', 'approved')
            ->count();

        $pendingTransactions = (clone $transactionQuery)
            ->where('status', 'pending')
            ->count();

        $rejectedTransactions = (clone $transactionQuery)
            ->where('status', 'rejected')
            ->count();

        $totalRevenue = (clone $transactionQuery)
            ->where('status', 'approved')
            ->sum('amount');

        /*
        |--------------------------------------------------------------------------
        | Recent Activity
        |--------------------------------------------------------------------------
        */

       $recentQuizResultsQuery = QuizResult::with(['student', 'quiz'])
    ->latest();

if ($startDate) {
    $recentQuizResultsQuery->where('created_at', '>=', $startDate);
}

$recentQuizResults = $recentQuizResultsQuery
    ->take(5)
    ->get();

$recentCertificatesQuery = Certificate::with(['student', 'course'])
    ->latest();

if ($startDate) {
    $recentCertificatesQuery->where('created_at', '>=', $startDate);
}

$recentCertificates = $recentCertificatesQuery
    ->take(5)
    ->get();

$popularCoursesQuery = Course::query();

if ($startDate) {
    $popularCoursesQuery->whereHas('enrollments', function ($query) use ($startDate) {
        $query->where('created_at', '>=', $startDate);
    });
}

$popularCourses = $popularCoursesQuery
    ->withCount([
        'enrollments' => function ($query) use ($startDate) {
            if ($startDate) {
                $query->where('created_at', '>=', $startDate);
            }
        },
    ])
    ->orderByDesc('enrollments_count')
    ->take(5)
    ->get();

        return view('reports.index', compact(
            'range',
            'totalStudents',
            'totalTeachers',
            'totalAdmins',
            'totalCourses',
            'totalEnrollments',
            'completedEnrollments',
            'activeEnrollments',
            'completionRate',
            'certificatesIssued',
            'quizAttempts',
            'averageQuizScore',
            'passedQuizAttempts',
            'failedQuizAttempts',
            'totalAssignments',
            'totalSubmissions',
            'gradedSubmissions',
            'pendingSubmissions',
            'totalTransactions',
            'approvedTransactions',
            'pendingTransactions',
            'rejectedTransactions',
            'totalRevenue',
            'recentQuizResults',
            'recentCertificates',
            'popularCourses'
        ));
    }

    /**
     * Teacher-only performance analytics.
     *
     * Uses only the authenticated teacher's own courses and related data.
     */
    public function teacherAnalytics(Request $request)
    {
        $teacherId = auth()->id();

        $teacherCourses = Course::query()
            ->where('teacher_id', $teacherId)
            ->orderBy('title')
            ->get(['id', 'title', 'status']);

        $teacherCourseIds = $teacherCourses->pluck('id');

        $selectedCourseId = $request->integer('course');

        if (
            $selectedCourseId
            && ! $teacherCourseIds->contains($selectedCourseId)
        ) {
            abort(403, 'You do not have access to this course.');
        }

        $range = (string) $request->input('range', 'all');

        $startDate = match ($range) {
            '30' => now()->subDays(30)->startOfDay(),
            '90' => now()->subDays(90)->startOfDay(),
            '365' => now()->subDays(365)->startOfDay(),
            default => null,
        };

        $selectedCourseIds = $selectedCourseId
            ? collect([$selectedCourseId])
            : $teacherCourseIds;

        /*
        |--------------------------------------------------------------------------
        | Enrollments
        |--------------------------------------------------------------------------
        */

        $enrollmentQuery = Enrollment::query()
            ->whereIn('course_id', $selectedCourseIds)
            ->with(['student', 'course']);

        if ($startDate) {
            $enrollmentQuery->where('created_at', '>=', $startDate);
        }

        $enrollments = $enrollmentQuery->get();

        /*
        |--------------------------------------------------------------------------
        | Quiz Results
        |--------------------------------------------------------------------------
        */

        $quizResultQuery = QuizResult::query()
            ->whereHas('quiz', function ($query) use ($selectedCourseIds) {
                $query->whereIn('course_id', $selectedCourseIds);
            })
            ->with(['student', 'quiz.course']);

        if ($startDate) {
            $quizResultQuery->where('created_at', '>=', $startDate);
        }

        $quizResults = $quizResultQuery->get();

        /*
        |--------------------------------------------------------------------------
        | Assignments + Submissions
        |--------------------------------------------------------------------------
        */

        $assignmentQuery = Assignment::query()
            ->whereIn('course_id', $selectedCourseIds);

        if ($startDate) {
            $assignmentQuery->where('created_at', '>=', $startDate);
        }

        $assignments = $assignmentQuery->get();

        $submissionQuery = Submission::query()
            ->whereIn('assignment_id', $assignments->pluck('id'));

        if ($startDate) {
            $submissionQuery->where('created_at', '>=', $startDate);
        }

        $submissions = $submissionQuery->get();

        /*
        |--------------------------------------------------------------------------
        | Headline Metrics
        |--------------------------------------------------------------------------
        */

        $totalCourses = $selectedCourseIds->count();

        $totalStudents = $enrollments
            ->pluck('student_id')
            ->filter()
            ->unique()
            ->count();

        $averageProgress = round(
            (float) ($enrollments->avg('progress_percentage') ?? 0),
            1
        );

        $averageQuizScore = round(
            (float) ($quizResults->avg('percentage') ?? 0),
            1
        );

        $completedEnrollments = $enrollments
            ->where('status', 'completed')
            ->count();

        $completionRate = $enrollments->count() > 0
            ? round(
                ($completedEnrollments / $enrollments->count()) * 100,
                1
            )
            : 0;

        $pendingGrading = $submissions
            ->where('status', 'submitted')
            ->count();

        $totalAssignments = $assignments->count();

        /*
        |--------------------------------------------------------------------------
        | Quiz Pass Rate
        |--------------------------------------------------------------------------
        */

        $passedQuizResults = $quizResults->filter(function ($result) {
            $passingScore = (float) ($result->quiz->passing_score ?? 75);

            return (float) ($result->percentage ?? 0) >= $passingScore;
        })->count();

        $quizPassRate = $quizResults->count() > 0
            ? round(
                ($passedQuizResults / $quizResults->count()) * 100,
                1
            )
            : 0;

        /*
        |--------------------------------------------------------------------------
        | Quiz Score Distribution
        |--------------------------------------------------------------------------
        */

        $scoreDistribution = [
            '90–100' => $quizResults
                ->filter(fn ($result) => (float) $result->percentage >= 90)
                ->count(),

            '75–89' => $quizResults
                ->filter(function ($result) {
                    $score = (float) $result->percentage;

                    return $score >= 75 && $score < 90;
                })
                ->count(),

            '60–74' => $quizResults
                ->filter(function ($result) {
                    $score = (float) $result->percentage;

                    return $score >= 60 && $score < 75;
                })
                ->count(),

            'Below 60' => $quizResults
                ->filter(fn ($result) => (float) $result->percentage < 60)
                ->count(),
        ];

        $maxDistribution = max(
            1,
            max($scoreDistribution)
        );

        /*
        |--------------------------------------------------------------------------
        | Enrollment Trend — Last 6 Months
        |--------------------------------------------------------------------------
        */

        $trendMonths = collect(range(5, 0))
            ->map(function ($monthsAgo) {
                $date = now()
                    ->copy()
                    ->subMonths($monthsAgo);

                return [
                    'key' => $date->format('Y-m'),
                    'label' => $date->format('M'),
                    'count' => 0,
                ];
            });

        $trendCounts = $enrollments
            ->filter(fn ($enrollment) => $enrollment->created_at)
            ->groupBy(
                fn ($enrollment) => $enrollment->created_at->format('Y-m')
            )
            ->map(fn ($items) => $items->count());

        $enrollmentTrend = $trendMonths
            ->map(function ($month) use ($trendCounts) {
                $month['count'] = (int) ($trendCounts[$month['key']] ?? 0);

                return $month;
            })
            ->values();

        $maxTrend = max(
            1,
            (int) $enrollmentTrend->max('count')
        );

        /*
        |--------------------------------------------------------------------------
        | Course Performance
        |--------------------------------------------------------------------------
        */

        $coursePerformance = $teacherCourses
            ->filter(
                fn ($course) =>
                    ! $selectedCourseId
                    || $course->id === $selectedCourseId
            )
            ->map(function ($course) use ($enrollments, $quizResults) {
                $courseEnrollments = $enrollments
                    ->where('course_id', $course->id);

                $courseQuizResults = $quizResults
                    ->filter(
                        fn ($result) =>
                            optional($result->quiz)->course_id === $course->id
                    );

                return [
                    'id' => $course->id,
                    'title' => $course->title,
                    'status' => $course->status,
                    'students' => $courseEnrollments
                        ->pluck('student_id')
                        ->filter()
                        ->unique()
                        ->count(),
                    'avg_progress' => round(
                        (float) ($courseEnrollments
                            ->avg('progress_percentage') ?? 0),
                        1
                    ),
                    'avg_quiz' => round(
                        (float) ($courseQuizResults
                            ->avg('percentage') ?? 0),
                        1
                    ),
                ];
            })
            ->sortByDesc('students')
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Learner Snapshot
        |--------------------------------------------------------------------------
        */

        $learnerSnapshot = $enrollments
            ->groupBy('student_id')
            ->map(function ($studentEnrollments, $studentId) use ($quizResults) {
                $student = $studentEnrollments
                    ->first()
                    ?->student;

                $studentQuizResults = $quizResults
                    ->where('student_id', $studentId);

                return [
                    'name' => $student->name ?? 'Student',
                    'email' => $student->email ?? '',
                    'avg_progress' => round(
                        (float) ($studentEnrollments
                            ->avg('progress_percentage') ?? 0),
                        1
                    ),
                    'avg_quiz' => round(
                        (float) ($studentQuizResults
                            ->avg('percentage') ?? 0),
                        1
                    ),
                    'courses' => $studentEnrollments
                        ->pluck('course_id')
                        ->unique()
                        ->count(),
                ];
            })
            ->sortByDesc(function ($student) {
                return (
                    ($student['avg_progress'] * 0.5)
                    + ($student['avg_quiz'] * 0.5)
                );
            })
            ->take(5)
            ->values();

        return view('Teacher.analytics', compact(
            'teacherCourses',
            'selectedCourseId',
            'range',
            'totalCourses',
            'totalStudents',
            'averageProgress',
            'averageQuizScore',
            'completionRate',
            'quizPassRate',
            'pendingGrading',
            'totalAssignments',
            'scoreDistribution',
            'maxDistribution',
            'enrollmentTrend',
            'maxTrend',
            'coursePerformance',
            'learnerSnapshot'
        ));
    }
}
