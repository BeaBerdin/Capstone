<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CourseCategoryController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\CourseInvitationController;
use App\Http\Controllers\CourseReviewController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\QuizQuestionController;
use App\Http\Controllers\QuizResultController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\SubmissionController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\CertificateTemplateController;
use App\Http\Controllers\CertificateVerificationController;
use App\Http\Controllers\AIRecommendationController;
use App\Http\Controllers\StudentProgressController;
use App\Http\Controllers\CertificateManagementController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\LearningPathController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PrivateFileController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\StudentRemovalRequestController;

Route::view('/', 'welcome')->name('home');

/*
|--------------------------------------------------------------------------
| EMAIL VERIFICATION LINK
|--------------------------------------------------------------------------
|
| Replaces Fortify's route of the same name and URL, which required a login.
| The link still needs Laravel's valid signature and expiry (signed) and is
| throttled the same way; see VerifyEmailLinkController.
|
*/

Route::get('/email/verify/{id}/{hash}', \App\Http\Controllers\Auth\VerifyEmailLinkController::class)
    ->middleware(['signed', 'throttle:verification-link'])
    ->name('verification.verify');

// Redeems a one-time sign-in grant stored in this browser's session.
Route::post('/email/verified/continue', [\App\Http\Controllers\Auth\VerifyEmailLinkController::class, 'continue'])
    ->middleware('throttle:verification-continue')
    ->name('verification.continue');

/*
|--------------------------------------------------------------------------
| FORTIFY ROUTES WITH EXTRA PROTECTION
|--------------------------------------------------------------------------
|
| Same URLs, names and controllers as Fortify's; these replace them (app
| routes load after Fortify's) to add:
|  - registration: layered rate limits (per email + IP, and per IP);
|  - "verify your email" page and resend: a student whose application is
|    pending or rejected is sent to the application status page instead,
|    and cannot request a verification email.
|
*/

Route::post('/register', [\Laravel\Fortify\Http\Controllers\RegisteredUserController::class, 'store'])
    ->middleware(['guest:'.config('fortify.guard'), 'throttle:registration'])
    ->name('register.store');

Route::get('/email/verify', \Laravel\Fortify\Http\Controllers\EmailVerificationPromptController::class)
    ->middleware(['auth', 'student.application'])
    ->name('verification.notice');

Route::post('/email/verification-notification', [\Laravel\Fortify\Http\Controllers\EmailVerificationNotificationController::class, 'store'])
    ->middleware(['auth', 'student.application', 'throttle:verification-resend'])
    ->name('verification.send');

/*
|--------------------------------------------------------------------------
| STUDENT APPLICATION STATUS (signed in, not yet approved/verified)
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {

    Route::get('/application-status', [\App\Http\Controllers\StudentApplicationStatusController::class, 'show'])
        ->name('student-application.status');

    Route::get('/application-status/check', [\App\Http\Controllers\StudentApplicationStatusController::class, 'check'])
        ->middleware('throttle:application-check')
        ->name('student-application.check');

    Route::post('/application-status/resubmit', [\App\Http\Controllers\StudentApplicationStatusController::class, 'resubmit'])
        ->middleware('throttle:study-load-resubmission')
        ->name('student-application.resubmit');
});

/*
| The application gate runs BEFORE `verified`: a pending or rejected student
| is sent to the application status page, never to the verify page.
*/

Route::middleware(['auth', 'student.application', 'verified'])->group(function () {

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
    | PRIVATE FILES
    |--------------------------------------------------------------------------
    | Payment receipts, assignment submissions and lesson files. Access is
    | checked in PrivateFileController.
    */

    Route::get('/files/receipts/{transaction}', [PrivateFileController::class, 'receipt'])
        ->name('files.receipt');

    Route::get('/files/submissions/{submission}', [PrivateFileController::class, 'submission'])
        ->name('files.submission');

    Route::get('/files/lessons/{lesson}', [PrivateFileController::class, 'lesson'])
        ->name('files.lesson');

    Route::get('/files/lesson-sections/{lessonSection}', [PrivateFileController::class, 'lessonSection'])
        ->name('files.lesson-section');


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

        Route::get('/super-admin/users/create', [UserManagementController::class, 'create'])
            ->name('users.create');

        Route::post('/super-admin/users', [UserManagementController::class, 'store'])
            ->name('users.store');

        Route::put('/super-admin/users/{user}/role', [UserManagementController::class, 'updateRole'])
            ->name('users.update-role');

        Route::patch('/super-admin/users/{user}/status', [UserManagementController::class, 'updateStatus'])
            ->name('users.update-status');

        Route::patch('/super-admin/users/{user}/verify-email', [UserManagementController::class, 'verifyEmail'])
            ->name('users.verify-email');

        Route::post('/super-admin/users/{user}/teacher-review', [UserManagementController::class, 'queueTeacherReview'])
            ->name('users.teacher-review');

        // Audit Log (read-only: there is no route that edits or deletes it).
        Route::get('/super-admin/audit-log', [\App\Http\Controllers\SuperAdmin\AuditLogController::class, 'index'])
            ->name('super-admin.audit-log.index');

        // Issued certificates whose course still has activities without a
        // passing final grade for that student (read-only report).
        Route::get('/super-admin/certificates-to-review', [\App\Http\Controllers\SuperAdmin\CertificateReviewController::class, 'index'])
            ->name('super-admin.certificate-review.index');

        Route::get('/super-admin/users/{user}/edit', [UserManagementController::class, 'edit'])
            ->name('users.edit');

        Route::put('/super-admin/users/{user}', [UserManagementController::class, 'update'])
            ->name('users.update');

        Route::get('/super-admin/transactions', [TransactionController::class, 'adminIndex'])
            ->name('super_admin.transactions.index');

        Route::post('/super-admin/transactions/{transaction}/approve', [TransactionController::class, 'approve'])
            ->name('super_admin.transactions.approve');

        Route::post('/super-admin/transactions/{transaction}/reject', [TransactionController::class, 'reject'])
            ->name('super_admin.transactions.reject');

        Route::get('/super-admin/reports', [ReportsController::class, 'index'])
            ->name('reports.index');
        
        Route::get('/super-admin/reports/pdf', [ReportsController::class, 'exportPdf'])
            ->name('reports.export.pdf');

        /*
        | Student applications (EDP). Only the Super Admin may list, view,
        | open a Study Load, approve or reject; the role middleware above
        | refuses everyone else, including the Admin (Dean).
        */
        Route::get('/super-admin/student-applications', [\App\Http\Controllers\SuperAdmin\StudentApplicationController::class, 'index'])
            ->name('super-admin.student-applications.index');

        Route::get('/super-admin/student-applications/{studentApplication}', [\App\Http\Controllers\SuperAdmin\StudentApplicationController::class, 'show'])
            ->name('super-admin.student-applications.show');

        Route::get('/super-admin/student-applications/{studentApplication}/study-load', [\App\Http\Controllers\SuperAdmin\StudentApplicationController::class, 'studyLoad'])
            ->name('super-admin.student-applications.study-load');

        Route::post('/super-admin/student-applications/{studentApplication}/approve', [\App\Http\Controllers\SuperAdmin\StudentApplicationController::class, 'approve'])
            ->name('super-admin.student-applications.approve');

        Route::post('/super-admin/student-applications/{studentApplication}/reject', [\App\Http\Controllers\SuperAdmin\StudentApplicationController::class, 'reject'])
            ->name('super-admin.student-applications.reject');

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

    Route::middleware('role:admin,super_admin')->group(function () {

        Route::get('/admin-dashboard', [DashboardController::class, 'index'])
            ->name('admin.dashboard');

        Route::resource('course-categories', CourseCategoryController::class)->except('show')->middleware('role:super_admin');

        Route::resource('courses', CourseController::class)->except('show');

        Route::post('/courses/{course}/approve', [CourseController::class, 'approve'])
            ->name('courses.approve');

        Route::post('/courses/{course}/reject', [CourseController::class, 'reject'])
            ->name('courses.reject');

        Route::resource('lessons', LessonController::class)->except('show');
        Route::resource('quizzes', QuizController::class)->except('show');
        Route::resource('quiz-questions', QuizQuestionController::class)->except('show');
        // Quiz results are view-only: they are recorded when a student submits a quiz.
        Route::get('/quiz-results', [QuizResultController::class, 'index'])
            ->name('quiz-results.index');
        Route::resource('enrollments', EnrollmentController::class)->except('show');
        Route::resource('assignments', AssignmentController::class);
        // Submissions are view-only for administrators: teachers grade their own courses.
        Route::resource('submissions', SubmissionController::class)->only(['index', 'show']);
        Route::resource('certificates', CertificateController::class)->except('show');
        Route::resource('ai-recommendations', AIRecommendationController::class)->except(['show', 'edit', 'update']);

        Route::get('/student-progress', [StudentProgressController::class, 'index'])
            ->name('student-progress.index');

        Route::get('/certificate-management', [CertificateManagementController::class, 'index'])
            ->name('certificate-management.index');

        // Certificate template used by the certificate PDF (title, body, signatory).
        Route::get('/admin/certificate-template', [CertificateTemplateController::class, 'edit'])
            ->middleware('role:super_admin')->name('certificate-template.edit');

        Route::put('/admin/certificate-template', [CertificateTemplateController::class, 'update'])
            ->middleware('role:super_admin')->name('certificate-template.update');

        Route::get('/admin/certificate-template/preview', [CertificateTemplateController::class, 'preview'])
            ->middleware('role:super_admin')->name('certificate-template.preview');

        Route::resource('admin/learning-paths', LearningPathController::class)
            ->names('learning-paths');
        
        // Student removal requests — Department Head
        Route::get('/admin/student-removal-requests',[StudentRemovalRequestController::class, 'departmentHeadIndex'])
            ->name('admin.student-removal-requests.index');

        // Course review moderation
        Route::get('/course-reviews', [CourseReviewController::class, 'adminIndex'])
            ->name('admin.course-reviews.index');

        Route::post('/course-reviews/{courseReview}/hide', [CourseReviewController::class, 'hide'])
            ->name('admin.course-reviews.hide');

        Route::post('/course-reviews/{courseReview}/unhide', [CourseReviewController::class, 'unhide'])
            ->name('admin.course-reviews.unhide');

        Route::post('/admin/student-removal-requests/{studentRemovalRequest}/review',[StudentRemovalRequestController::class, 'review'])
            ->name('admin.student-removal-requests.review');

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

        Route::get('/teacher/courses/{course}/reviews', [CourseReviewController::class, 'teacherIndex'])
            ->name('teacher.courses.reviews');

        Route::get('/teacher/course-reviews', [CourseReviewController::class, 'teacherAll'])
            ->name('teacher.course-reviews.index');

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

        // First save + AI generation in one step (the quiz has not been saved yet).
        Route::post('/teacher/lessons/{lesson}/quiz/save-and-generate', [QuizController::class, 'teacherSaveAndGenerate'])
            ->name('teacher.quiz.save-and-generate');

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

        Route::get('/teacher/student-progress', [StudentProgressController::class, 'index'])
            ->name('teacher.student-progress.index');

        Route::get('/teacher/lessons', [LessonController::class, 'teacherAllLessons'])
            ->name('teacher.lessons.index');

        Route::get('/teacher/analytics', [ReportsController::class, 'teacherAnalytics'])
            ->name('teacher.analytics');
        
        // Limited per teacher: every evaluation is one Gemini request.
        Route::post('/teacher/submissions/{submission}/ai-evaluate',[\App\Http\Controllers\SubmissionController::class, 'aiEvaluate'])
             ->middleware('throttle:ai-evaluation')
             ->name('teacher.submissions.ai-evaluate');

        // Course invitations
        Route::get('/teacher/invitations', [CourseInvitationController::class, 'index'])
            ->name('course-invitations.index');

        Route::post('/teacher/invitations', [CourseInvitationController::class, 'store'])
            ->name('course-invitations.store');

        Route::delete('/teacher/invitations/{courseInvitation}', [CourseInvitationController::class, 'destroy'])
            ->name('course-invitations.destroy');

        Route::delete('/teacher/courses/{course}', [CourseController::class, 'teacherDestroyCourse'])
            ->name('teacher.courses.destroy');
        
         Route::post('/teacher/invitations/{courseInvitation}/send',[CourseInvitationController::class, 'send'])
           ->name('course-invitations.send');

        // Student removal requests — Instructor
        Route::get('/teacher/student-removal-requests', [StudentRemovalRequestController::class, 'instructorIndex'])
           ->name('teacher.student-removal-requests.index');

        Route::post('/teacher/courses/{course}/students/removal-request',[StudentRemovalRequestController::class, 'store'])
            ->name('teacher.student-removal-requests.store');

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
        ->middleware('throttle:enrollment')
        ->name('student.enroll');

    // Course reviews: limited to 5 changes per minute per student.
    Route::middleware('throttle:5,1')->group(function () {
        Route::post('/marketplace/{course}/reviews', [CourseReviewController::class, 'store'])
            ->name('student.reviews.store');

        Route::put('/reviews/{courseReview}', [CourseReviewController::class, 'update'])
            ->name('student.reviews.update');

        Route::delete('/reviews/{courseReview}', [CourseReviewController::class, 'destroy'])
            ->name('student.reviews.destroy');
    });

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
        ->middleware('throttle:learning-path')
        ->name('student.learning-paths.generate');

    Route::get('/recommended-courses', [AIRecommendationController::class, 'studentRecommendations'])
        ->name('student.recommendations');

    Route::get('/transactions', [TransactionController::class, 'studentIndex'])
        ->name('student.transactions');

    Route::post('/marketplace/{course}/purchase', [TransactionController::class, 'store'])
        ->middleware('throttle:purchase')
        ->name('student.transactions.store');

    Route::get('/transactions/{transaction}', [TransactionController::class, 'studentShow'])
        ->name('student.transactions.show');

    Route::get('/transactions/{transaction}/success', [TransactionController::class, 'success'])
        ->name('student.transactions.success');

    Route::get('/transactions/{transaction}/cancel', [TransactionController::class, 'cancel'])
        ->name('student.transactions.cancel');

    Route::post('/transactions/{transaction}/upload-proof', [TransactionController::class, 'uploadProof'])
        ->name('student.transactions.upload-proof');

    Route::post('/invitations/{code}/accept', [CourseInvitationController::class, 'accept'])
        ->middleware('throttle:invite-code')
        ->name('course-invitations.accept');

    Route::get('/course-invitations', [CourseInvitationController::class, 'studentIndex'])
        ->name('student.invitations.index');

    Route::post('/course-invitations/join', [CourseInvitationController::class, 'join'])
        ->middleware('throttle:invite-code')
        ->name('student.invitations.join');
});
});

// Guests keep their intended link after signing in. The preview reveals
// invitation details only to approved and email-verified students.
Route::get('/invitations/{code}', [CourseInvitationController::class, 'show'])
    ->middleware(['auth', 'role:student', 'throttle:invite-preview'])
    ->name('course-invitations.show');

/*
|--------------------------------------------------------------------------
| PUBLIC CERTIFICATE VERIFICATION
|--------------------------------------------------------------------------
|
| The "Verify at" address printed on certificates. Public, and limited to
| 10 lookups per minute per visitor to prevent guessing numbers.
|
*/

Route::get('/verify-certificate', [CertificateVerificationController::class, 'show'])
    ->middleware('throttle:10,1')
    ->name('certificates.verify');
// =====================================================
// PAYMONGO WEBHOOK
// =====================================================
// IMPORTANT:
// This route is outside auth/verified middleware.
// PayMongo needs to access this endpoint directly.

Route::post('/paymongo/webhook', [TransactionController::class, 'webhook'])
    ->name('paymongo.webhook');


require __DIR__.'/settings.php';
