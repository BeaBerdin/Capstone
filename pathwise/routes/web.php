<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CourseCategoryController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\QuizQuestionController;
use App\Http\Controllers\QuizResultController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\SubmissionController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\AIRecommendationController;
use App\Http\Controllers\StudentProgressController;
use App\Http\Controllers\CertificateManagementController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\LearningPathController;
use App\Http\Controllers\TransactionController;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | DASHBOARD REDIRECT
    |--------------------------------------------------------------------------
    */

    Route::get('/dashboard', function () {

        if (auth()->user()->hasRole('super_admin')) {
            return redirect()->route('super_admin.dashboard');
        }

        if (auth()->user()->hasRole('admin')) {
            return redirect()->route('admin.dashboard');
        }

        if (auth()->user()->hasRole('teacher')) {
            return redirect()->route('teacher.dashboard');
        }

        if (auth()->user()->hasRole('student')) {
            return redirect()->route('student.dashboard');
        }

        abort(403, 'Unauthorized');

    })->name('dashboard');


    /*
|--------------------------------------------------------------------------
| SUPER ADMIN - EDP
|--------------------------------------------------------------------------
| System-level administration
*/

Route::middleware('role:super_admin')->group(function () {

    // Super Admin Dashboard
    Route::get('/super-admin-dashboard', [DashboardController::class, 'superAdmin'])
        ->name('super_admin.dashboard');

    // User Management
    Route::get('/super-admin/users', [UserManagementController::class, 'index'])
        ->name('users.index');

    Route::put('/super-admin/users/{user}/role', [UserManagementController::class, 'updateRole'])
        ->name('users.update-role');

    // Transaction Verification
    Route::get('/super-admin/transactions', [TransactionController::class, 'adminIndex'])
        ->name('super_admin.transactions.index');

    Route::post('/super-admin/transactions/{transaction}/approve', [TransactionController::class, 'approve'])
        ->name('super_admin.transactions.approve');

    Route::post('/super-admin/transactions/{transaction}/reject', [TransactionController::class, 'reject'])
        ->name('super_admin.transactions.reject');

    // System Reports
    Route::get('/super-admin/reports', [ReportsController::class, 'index'])
        ->name('reports.index');

});
    /*
    |--------------------------------------------------------------------------
    | ADMIN - DEPARTMENT HEAD
    |--------------------------------------------------------------------------
    | E-learning content and academic management
    */

    Route::middleware('role:admin')->group(function () {

        // Admin Dashboard
        Route::get('/admin-dashboard', [DashboardController::class, 'index'])
            ->name('admin.dashboard');


        // Course Categories
        Route::resource('course-categories', CourseCategoryController::class);


        // Courses
        Route::resource('courses', CourseController::class);

        Route::post('/courses/{course}/approve', [CourseController::class, 'approve'])
            ->name('courses.approve');

        Route::post('/courses/{course}/reject', [CourseController::class, 'reject'])
            ->name('courses.reject');


        // Lessons
        Route::resource('lessons', LessonController::class);


        // Quizzes
        Route::resource('quizzes', QuizController::class);


        // Quiz Questions
        Route::resource('quiz-questions', QuizQuestionController::class);


        // Quiz Results
        Route::resource('quiz-results', QuizResultController::class);


        // Enrollments
        Route::resource('enrollments', EnrollmentController::class);


        // Assignments
        Route::resource('assignments', AssignmentController::class);


        // Submissions
        Route::resource('submissions', SubmissionController::class);


        // Certificates
        Route::resource('certificates', CertificateController::class);


        // AI Recommendations
        Route::resource('ai-recommendations', AIRecommendationController::class);


        // Student Progress
        Route::get('/student-progress', [StudentProgressController::class, 'index'])
            ->name('student-progress.index');


        // Certificate Management
        Route::get('/certificate-management', [CertificateManagementController::class, 'index'])
            ->name('certificate-management.index');


        // Learning Paths
        Route::resource('admin/learning-paths', LearningPathController::class)
            ->names('learning-paths');
    });


    /*
    |--------------------------------------------------------------------------
    | TEACHER
    |--------------------------------------------------------------------------
    */

    Route::middleware('role:teacher')->group(function () {

        // Teacher Dashboard
        Route::get('/teacher-dashboard', [DashboardController::class, 'teacher'])
            ->name('teacher.dashboard');


        // Teacher Courses
        Route::get('/teacher-courses', [CourseController::class, 'teacherCourses'])
            ->name('teacher.courses');

        Route::get('/teacher/my-courses', [CourseController::class, 'teacherCourses'])
            ->name('teacher.my-courses');

        // ============================================
        // NEW: CREATE COURSE ROUTES (ADD THESE)
        // ============================================
        Route::get('/teacher/courses/create', [CourseController::class, 'teacherCreateCourse'])
            ->name('teacher.courses.create');

        Route::post('/teacher/courses', [CourseController::class, 'teacherStoreCourse'])
            ->name('teacher.courses.store');

        Route::get('/teacher/courses/{course}/edit', [CourseController::class, 'teacherEditCourse'])
            ->name('teacher.courses.edit');

        Route::put('/teacher/courses/{course}', [CourseController::class, 'teacherUpdateCourse'])
            ->name('teacher.courses.update');
        // ============================================


        // View Students
        Route::get('/teacher-courses/{course}/students', [CourseController::class, 'teacherCourseStudents'])
            ->name('teacher.course.students');


        // Student Progress
        Route::get('/teacher/course/{course}/student/{student}/progress', [CourseController::class, 'studentProgress'])
            ->name('teacher.student.progress');


        // Teacher Lessons
        Route::get('/teacher/courses/{course}/lessons', [LessonController::class, 'teacherLessons'])
            ->name('teacher.lessons');

        Route::get('/teacher/courses/{course}/lessons/create', [LessonController::class, 'teacherCreateLesson'])
            ->name('teacher.lessons.create');

        Route::post('/teacher/courses/{course}/lessons', [LessonController::class, 'teacherStoreLesson'])
            ->name('teacher.lessons.store');

        Route::get('/teacher/lessons/{lesson}/edit', [LessonController::class, 'teacherEditLesson'])
            ->name('teacher.lessons.edit');

        Route::put('/teacher/lessons/{lesson}', [LessonController::class, 'teacherUpdateLesson'])
            ->name('teacher.lessons.update');

        Route::delete('/teacher/lessons/{lesson}', [LessonController::class, 'teacherDeleteLesson'])
            ->name('teacher.lessons.delete');


        // Submit Course for Approval
        Route::post('/teacher/courses/{course}/submit', [CourseController::class, 'submitForApproval'])
            ->name('teacher.courses.submit');


        // Quiz Results
        Route::get('/teacher/quiz-results', [QuizResultController::class, 'index'])
            ->name('teacher.quiz-results.index');

        Route::get('/teacher/quiz-results/create', [QuizResultController::class, 'create'])
            ->name('teacher.quiz-results.create');

        Route::post('/teacher/quiz-results', [QuizResultController::class, 'store'])
            ->name('teacher.quiz-results.store');

        Route::get('/teacher/quiz-results/{quiz_result}/edit', [QuizResultController::class, 'edit'])
            ->name('teacher.quiz-results.edit');

        Route::put('/teacher/quiz-results/{quiz_result}', [QuizResultController::class, 'update'])
            ->name('teacher.quiz-results.update');

        Route::delete('/teacher/quiz-results/{quiz_result}', [QuizResultController::class, 'destroy'])
            ->name('teacher.quiz-results.destroy');


        // Teacher Student Progress
        Route::get('/teacher/student-progress', [StudentProgressController::class, 'index'])
            ->name('teacher.student-progress.index');


        // All Teacher Lessons
        Route::get('/teacher/lessons', [LessonController::class, 'teacherAllLessons'])
            ->name('teacher.lessons.index');
    });


    /*
    |--------------------------------------------------------------------------
    | STUDENT
    |--------------------------------------------------------------------------
    */

    Route::middleware('role:student')->group(function () {

        // Student Dashboard
        Route::get('/student-dashboard', [DashboardController::class, 'student'])
            ->name('student.dashboard');


        // Marketplace
        Route::get('/marketplace', [CourseController::class, 'marketplace'])
            ->name('student.marketplace');

        Route::get('/marketplace/{course}', [CourseController::class, 'showStudentCourse'])
            ->name('student.course.show');


        // Enrollment
        Route::post('/marketplace/{course}/enroll', [CourseController::class, 'enroll'])
            ->name('student.enroll');


        // My Courses
        Route::get('/my-courses', [CourseController::class, 'myCourses'])
            ->name('student.my-courses');


        // Learning
        Route::get('/learn/{course}', [LessonController::class, 'studentCourse'])
            ->name('student.learn.course');

        Route::get('/lesson/{lesson}', [LessonController::class, 'studentLesson'])
            ->name('student.lesson.view');

        Route::post('/lesson/{lesson}/complete', [LessonController::class, 'markComplete'])
            ->name('student.lesson.complete');


        // Certificates
        Route::get('/my-certificates', [DashboardController::class, 'certificates'])
            ->name('student.certificates');

        Route::get('/certificate/{certificate}', [CertificateController::class, 'studentView'])
            ->name('student.certificate.view');

        Route::get('/certificate/{certificate}/download', [CertificateController::class, 'download'])
            ->name('student.certificate.download');


        // Quizzes
        Route::get('/quiz/{quiz}/take', [QuizController::class, 'take'])
            ->name('student.quiz.take');

        Route::post('/quiz/{quiz}/submit', [QuizController::class, 'submit'])
            ->name('student.quiz.submit');


        // Learning Paths
        Route::get('/student/learning-paths', [LearningPathController::class, 'studentIndex'])
            ->name('student.learning-paths');

        Route::get('/student/learning-paths/{learningPath}', [LearningPathController::class, 'studentShow'])
            ->name('student.learning-paths.show');

        Route::post('/student/learning-paths/generate', [LearningPathController::class, 'generateForStudent'])
            ->name('student.learning-paths.generate');


        // AI Course Recommendations
        Route::get('/recommended-courses', [AIRecommendationController::class, 'studentRecommendations'])
            ->name('student.recommendations');


               // Student Transactions
        Route::get('/transactions', [TransactionController::class, 'studentIndex'])
            ->name('student.transactions');

        Route::post('/marketplace/{course}/purchase', [TransactionController::class, 'store'])
            ->name('student.transactions.store');

        Route::get('/transactions/{transaction}', [TransactionController::class, 'studentShow'])
            ->name('student.transactions.show');

        // PayMongo Payment Success
        Route::get('/transactions/{transaction}/success', [TransactionController::class, 'success'])
            ->name('student.transactions.success');

        // PayMongo Payment Cancelled
        Route::get('/transactions/{transaction}/cancel', [TransactionController::class, 'cancel'])
            ->name('student.transactions.cancel');

        // OLD PAYMENT PROOF ROUTE
        // Keep temporarily for compatibility.
        Route::post('/transactions/{transaction}/upload-proof', [TransactionController::class, 'uploadProof'])
            ->name('student.transactions.upload-proof');
    });
});


// =====================================================
// PAYMONGO WEBHOOK
// =====================================================
// IMPORTANT:
// This route is outside auth/verified middleware.
// PayMongo needs to access this endpoint directly.

Route::post('/paymongo/webhook', [TransactionController::class, 'webhook'])
    ->name('paymongo.webhook');


require __DIR__.'/settings.php';