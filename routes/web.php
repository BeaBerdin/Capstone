<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CourseCategoryController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\CourseInvitationController;
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
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\DepartmentController;

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
    | NOTIFICATIONS
    |--------------------------------------------------------------------------
    */

    Route::post('/notifications/{notification}/read',
        [NotificationController::class, 'read'])
        ->name('notifications.read');

    Route::post('/notifications/read-all',
        [NotificationController::class, 'readAll'])
        ->name('notifications.read-all');


    /*
    |--------------------------------------------------------------------------
    | SUPER ADMIN - EDP
    |--------------------------------------------------------------------------
    | System-level administration
    */

    Route::middleware('role:super_admin')->group(function () {

        Route::get('/super-admin-dashboard', [DashboardController::class, 'superAdmin'])
            ->name('super_admin.dashboard');

        Route::get('/super-admin/users', [UserManagementController::class, 'index'])
            ->name('users.index');

        Route::put('/super-admin/users/{user}/role', [UserManagementController::class, 'updateRole'])
            ->name('users.update-role');

        Route::get('/super-admin/transactions', [TransactionController::class, 'adminIndex'])
            ->name('super_admin.transactions.index');

        Route::post('/super-admin/transactions/{transaction}/approve', [TransactionController::class, 'approve'])
            ->name('super_admin.transactions.approve');

        Route::post('/super-admin/transactions/{transaction}/reject', [TransactionController::class, 'reject'])
            ->name('super_admin.transactions.reject');

        Route::get('/super-admin/reports', [ReportsController::class, 'index'])
            ->name('reports.index');

        /*
        | FIX: assign routes BEFORE the resource,
        | otherwise GET /departments/assign matches
        | the resource show route ({department} = "assign")
        */
        Route::get('/departments/assign', [DepartmentController::class, 'assign'])
            ->name('departments.assign');

        Route::post('/departments/assign', [DepartmentController::class, 'assignStore'])
            ->name('departments.assign.store');

        Route::get('/departments', [DepartmentController::class, 'index'])
            ->name('departments.index');

        Route::get('/departments/create', [DepartmentController::class, 'create'])
            ->name('departments.create');

        Route::post('/departments', [DepartmentController::class, 'store'])
            ->name('departments.store');

        Route::get('/departments/{department}/edit', [DepartmentController::class, 'edit'])
            ->name('departments.edit');

        Route::put('/departments/{department}', [DepartmentController::class, 'update'])
            ->name('departments.update');

        Route::delete('/departments/{department}', [DepartmentController::class, 'destroy'])
            ->name('departments.destroy');
    });


    /*
    |--------------------------------------------------------------------------
    | ADMIN - DEPARTMENT HEAD
    |--------------------------------------------------------------------------
    */

    Route::middleware('role:admin')->group(function () {

        Route::get('/admin-dashboard', [DashboardController::class, 'index'])
            ->name('admin.dashboard');

        Route::resource('course-categories', CourseCategoryController::class);

        Route::resource('courses', CourseController::class);

        Route::post('/courses/{course}/approve', [CourseController::class, 'approve'])
            ->name('courses.approve');

        Route::post('/courses/{course}/reject', [CourseController::class, 'reject'])
            ->name('courses.reject');

        Route::resource('lessons', LessonController::class);
        Route::resource('quizzes', QuizController::class);
        Route::resource('quiz-questions', QuizQuestionController::class);
        Route::resource('quiz-results', QuizResultController::class);
        Route::resource('enrollments', EnrollmentController::class);
        Route::resource('assignments', AssignmentController::class);
        Route::resource('submissions', SubmissionController::class);
        Route::resource('certificates', CertificateController::class);
        Route::resource('ai-recommendations', AIRecommendationController::class);

        Route::get('/student-progress', [StudentProgressController::class, 'index'])
            ->name('student-progress.index');

        Route::get('/certificate-management', [CertificateManagementController::class, 'index'])
            ->name('certificate-management.index');

        Route::resource('admin/learning-paths', LearningPathController::class)
            ->names('learning-paths');
    });


    /*
    |--------------------------------------------------------------------------
    | TEACHER
    |--------------------------------------------------------------------------
    */

    Route::middleware('role:teacher')->group(function () {

        Route::get('/teacher-dashboard', [DashboardController::class, 'teacher'])
            ->name('teacher.dashboard');

        Route::get('/teacher-courses', [CourseController::class, 'teacherCourses'])
            ->name('teacher.courses');

        Route::get('/teacher/my-courses', [CourseController::class, 'teacherCourses'])
            ->name('teacher.my-courses');

        Route::get('/teacher/courses/create', [CourseController::class, 'teacherCreateCourse'])
            ->name('teacher.courses.create');

        Route::post('/teacher/courses', [CourseController::class, 'teacherStoreCourse'])
            ->name('teacher.courses.store');

        /*
        | ADDED: edit/update course routes
        | (teacherEditCourse / teacherUpdateCourse exist in CourseController)
        */
        Route::get('/teacher/courses/{course}/edit', [CourseController::class, 'teacherEditCourse'])
            ->name('teacher.courses.edit');

        Route::put('/teacher/courses/{course}', [CourseController::class, 'teacherUpdateCourse'])
            ->name('teacher.courses.update');

        Route::get('/teacher-courses/{course}/students', [CourseController::class, 'teacherCourseStudents'])
            ->name('teacher.course.students');

        Route::get('/teacher/course/{course}/student/{student}/progress', [CourseController::class, 'studentProgress'])
            ->name('teacher.student.progress');

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

        Route::get('/teacher/lessons/{lesson}/quiz', [QuizController::class, 'teacherBuilder'])
            ->name('teacher.quiz.builder');

        Route::post('/teacher/lessons/{lesson}/quiz', [QuizController::class, 'teacherSaveQuiz'])
            ->name('teacher.quiz.save');

        Route::post('/teacher/quizzes/{quiz}/generate-questions', [QuizController::class, 'teacherGenerateQuestions'])
            ->name('teacher.quiz.questions.generate');

        Route::post('/teacher/quizzes/{quiz}/questions', [QuizController::class, 'teacherStoreQuestion'])
            ->name('teacher.quiz.question.store');

        Route::put('/teacher/quizzes/{quiz}/questions/{question}', [QuizController::class, 'teacherUpdateQuestion'])
            ->name('teacher.quiz.question.update');

        Route::delete('/teacher/quizzes/{quiz}/questions/{question}', [QuizController::class, 'teacherDeleteQuestion'])
            ->name('teacher.quiz.question.delete');

        Route::post('/teacher/courses/{course}/submit', [CourseController::class, 'submitForApproval'])
            ->name('teacher.courses.submit');

        // Assignments
        Route::get('/teacher/assignments', [AssignmentController::class, 'teacherIndex'])
            ->name('teacher.assignments.index');

        Route::get('/teacher/courses/{course}/assignments/create', [AssignmentController::class, 'teacherCreate'])
            ->name('teacher.assignments.create');

        Route::post('/teacher/courses/{course}/assignments', [AssignmentController::class, 'teacherStore'])
            ->name('teacher.assignments.store');

        Route::get('/teacher/assignments/{assignment}/edit', [AssignmentController::class, 'teacherEdit'])
            ->name('teacher.assignments.edit');

        Route::put('/teacher/assignments/{assignment}', [AssignmentController::class, 'teacherUpdate'])
            ->name('teacher.assignments.update');

        Route::delete('/teacher/assignments/{assignment}', [AssignmentController::class, 'teacherDestroy'])
            ->name('teacher.assignments.destroy');

        // Submission review / grading
        Route::get('/teacher/assignments/{assignment}/submissions', [SubmissionController::class, 'teacherIndex'])
            ->name('teacher.submissions.index');

        Route::get('/teacher/submissions/{submission}', [SubmissionController::class, 'teacherShow'])
            ->name('teacher.submissions.show');

        Route::put('/teacher/submissions/{submission}/grade', [SubmissionController::class, 'teacherGrade'])
            ->name('teacher.submissions.grade');

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

        Route::get('/teacher/student-progress', [StudentProgressController::class, 'index'])
            ->name('teacher.student-progress.index');

        Route::get('/teacher/lessons', [LessonController::class, 'teacherAllLessons'])
            ->name('teacher.lessons.index');

        Route::get('/teacher/analytics', [ReportsController::class, 'teacherAnalytics'])
            ->name('teacher.analytics');

        // Course invitations
        Route::get('/teacher/invitations', [CourseInvitationController::class, 'index'])
            ->name('course-invitations.index');

        Route::post('/teacher/invitations', [CourseInvitationController::class, 'store'])
            ->name('course-invitations.store');

        Route::delete('/teacher/invitations/{courseInvitation}', [CourseInvitationController::class, 'destroy'])
            ->name('course-invitations.destroy');

        Route::delete('/teacher/courses/{course}', [CourseController::class, 'teacherDestroyCourse'])
            ->name('teacher.courses.destroy');
        
            Route::post(
    '/teacher/invitations/{courseInvitation}/send',
    [CourseInvitationController::class, 'send']
)->name('course-invitations.send');
    });

/*
|--------------------------------------------------------------------------
| STUDENT
|--------------------------------------------------------------------------
*/

Route::middleware('role:student')->group(function () {

    Route::get('/student-dashboard', [DashboardController::class, 'student'])
        ->name('student.dashboard');

    Route::get('/marketplace', [CourseController::class, 'marketplace'])
        ->name('student.marketplace');

    Route::get('/marketplace/{course}', [CourseController::class, 'showStudentCourse'])
        ->name('student.course.show');

    Route::post('/marketplace/{course}/enroll', [CourseController::class, 'enroll'])
        ->name('student.enroll');

    Route::get('/my-courses', [CourseController::class, 'myCourses'])
        ->name('student.my-courses');

    Route::get('/learn/{course}', [LessonController::class, 'studentCourse'])
        ->name('student.learn.course');

    Route::get('/lesson/{lesson}', [LessonController::class, 'studentLesson'])
        ->name('student.lesson.view');

    Route::post('/lesson/{lesson}/complete', [LessonController::class, 'markComplete'])
        ->name('student.lesson.complete');

    // Assignments / output submissions
    Route::get('/student/assignments', [AssignmentController::class, 'studentIndex'])
        ->name('student.assignments.index');

    Route::get('/student/assignments/{assignment}', [AssignmentController::class, 'studentShow'])
        ->name('student.assignments.show');

    Route::post('/student/assignments/{assignment}/submit', [SubmissionController::class, 'studentStore'])
        ->name('student.assignments.submit');

    Route::get('/my-certificates', [DashboardController::class, 'certificates'])
        ->name('student.certificates');

    Route::get('/certificate/{certificate}', [CertificateController::class, 'studentView'])
        ->name('student.certificate.view');

    Route::get('/certificate/{certificate}/download', [CertificateController::class, 'download'])
        ->name('student.certificate.download');

    Route::get('/quiz/{quiz}/take', [QuizController::class, 'take'])
        ->name('student.quiz.take');

    Route::post('/quiz/{quiz}/submit', [QuizController::class, 'submit'])
        ->name('student.quiz.submit');

    Route::get('/student/learning-paths', [LearningPathController::class, 'studentIndex'])
        ->name('student.learning-paths');

    Route::get('/student/learning-paths/{learningPath}', [LearningPathController::class, 'studentShow'])
        ->name('student.learning-paths.show');

    Route::post('/student/learning-paths/generate', [LearningPathController::class, 'generateForStudent'])
        ->name('student.learning-paths.generate');

    Route::get('/recommended-courses', [AIRecommendationController::class, 'studentRecommendations'])
        ->name('student.recommendations');

    Route::get('/transactions', [TransactionController::class, 'studentIndex'])
        ->name('student.transactions');

    Route::post('/marketplace/{course}/purchase', [TransactionController::class, 'store'])
        ->name('student.transactions.store');

    Route::get('/transactions/{transaction}', [TransactionController::class, 'studentShow'])
        ->name('student.transactions.show');

    Route::get('/transactions/{transaction}/success', [TransactionController::class, 'success'])
        ->name('student.transactions.success');

    Route::get('/transactions/{transaction}/cancel', [TransactionController::class, 'cancel'])
        ->name('student.transactions.cancel');

    Route::post('/transactions/{transaction}/upload-proof', [TransactionController::class, 'uploadProof'])
        ->name('student.transactions.upload-proof');

    // Course invitation acceptance
    Route::post('/invitations/{code}/accept', [CourseInvitationController::class, 'accept'])
        ->name('course-invitations.accept');
});
});
/*
|--------------------------------------------------------------------------
| COURSE INVITATION PREVIEW
|--------------------------------------------------------------------------
|
| This route is outside the student role middleware so the invitation
| link can be opened before the student accepts the invitation.
|
*/

Route::get('/invitations/{code}', [CourseInvitationController::class, 'show'])
    ->name('course-invitations.show');
// =====================================================
// PAYMONGO WEBHOOK
// =====================================================
// IMPORTANT:
// This route is outside auth/verified middleware.
// PayMongo needs to access this endpoint directly.

Route::post('/paymongo/webhook', [TransactionController::class, 'webhook'])
    ->name('paymongo.webhook');


require __DIR__.'/settings.php';