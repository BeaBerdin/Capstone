<?php
/**
 * Disposable Selenium harness only. Never included by application routes or providers.
 *
 * - CLI "init": migrates a NEW, EMPTY SQLite file inside a freshly created
 *   temporary runtime folder and seeds synthetic test data.
 * - cli-server router: serves the app from that runtime (php -S ... runtime.php).
 *
 * Gemini, PayMongo and every other outbound HTTP call are faked, mail goes to
 * the log inside the runtime, and the database must be the runtime's file.
 */
$repo = getenv('PATHWISE_REPO_ROOT');
$runtime = getenv('PATHWISE_SELENIUM_RUNTIME');
if (!$repo || !$runtime || !is_dir($runtime) || !str_starts_with(basename($runtime), 'pathwise-selenium-')) {
    throw new RuntimeException('An explicitly created disposable Selenium runtime is required.');
}
$repo = realpath($repo);
$runtime = realpath($runtime);
$db = $runtime.DIRECTORY_SEPARATOR.'selenium.sqlite';
if (file_exists($repo.'/bootstrap/cache/config.php')) throw new RuntimeException('Refusing cached application configuration.');
if (!file_exists($runtime.'/created-new-runtime')) throw new RuntimeException('Missing new-runtime marker.');
require $repo.'/vendor/autoload.php';

function seleniumApp(string $repo, string $runtime, string $db) {
    $app = require $repo.'/bootstrap/app.php';
    $app->useEnvironmentPath($runtime);
    $app->useStoragePath($runtime.'/storage');
    $app->booted(function () use ($db) {
        if (config('database.default') !== 'sqlite' || realpath(config('database.connections.sqlite.database')) !== realpath($db)) {
            throw new RuntimeException('Selenium database isolation failed.');
        }
        if (!in_array(config('mail.default'), ['log', 'array'], true)) throw new RuntimeException('Selenium mail must be local only.');
        foreach (['gemini.api_key', 'paymongo.secret_key', 'paymongo.public_key', 'paymongo.webhook_secret', 'ses.key', 'ses.secret', 'postmark.key', 'resend.key'] as $key) {
            if (filled(config('services.'.$key))) throw new RuntimeException('External credentials are forbidden.');
        }
        // Fake Gemini: a fixed explanation instead of a network call.
        app()->bind(App\Services\GeminiRecommendationService::class, fn () => new class extends App\Services\GeminiRecommendationService {
            public function generateReason(App\Models\Quiz $quiz, App\Models\Course $recommendedCourse, float $percentage): ?string
            {
                return 'Fake Gemini explanation (Selenium test double).';
            }
        });
        // Fake every other outbound HTTP call (Gemini quiz generation, PayMongo, ...).
        Illuminate\Support\Facades\Http::fake(['*' => Illuminate\Support\Facades\Http::response(['error' => ['message' => 'Fake external service (Selenium test)']], 503)]);
        Illuminate\Support\Facades\Http::preventStrayRequests();
        // The suite signs in through the real form ~70 times in a few minutes
        // (several times per minute as the same account). Lift only the
        // log-in limiter (5/minute in FortifyServiceProvider) for this
        // disposable instance; every other limiter stays as configured.
        Illuminate\Support\Facades\RateLimiter::for('login', fn () => Illuminate\Cache\RateLimiting\Limit::none());
    });

    return $app;
}

if (PHP_SAPI === 'cli-server') {
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $public = realpath($repo.'/public');
    $file = realpath($public.'/'.ltrim(rawurldecode($path), '/'));
    if ($file && str_starts_with($file, $public.DIRECTORY_SEPARATOR) && is_file($file) && !in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['php', 'phtml', 'phar'], true)) {
        $types = ['js' => 'text/javascript', 'css' => 'text/css', 'png' => 'image/png', 'jpg' => 'image/jpeg', 'svg' => 'image/svg+xml', 'woff' => 'font/woff', 'woff2' => 'font/woff2', 'ico' => 'image/x-icon', 'json' => 'application/json'];
        header('Content-Type: '.($types[strtolower(pathinfo($file, PATHINFO_EXTENSION))] ?? 'application/octet-stream'));
        readfile($file);

        return;
    }
    $app = seleniumApp($repo, $runtime, $db);
    if ($path === '/__selenium-safety') {
        $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        header('Content-Type: application/json');
        echo json_encode([
            'runtimeId' => getenv('PATHWISE_SELENIUM_ID'),
            'sqlite' => config('database.default') === 'sqlite',
            'database' => realpath($db),
            'mail' => config('mail.default'),
            'gemini' => filled(config('services.gemini.api_key')),
        ]);

        return;
    }
    $app->handleRequest(Illuminate\Http\Request::capture());

    return;
}

$app = seleniumApp($repo, $runtime, $db);
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$action = $argv[1] ?? '';
if ($action !== 'init') throw new RuntimeException('Unsupported Selenium fixture operation.');
if (filesize($db) !== 0 || file_exists($runtime.'/fixtures.json')) throw new RuntimeException('Refusing an existing database.');
if (Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true, '--no-interaction' => true]) !== 0) {
    throw new RuntimeException('Disposable migration failed.');
}

$password = getenv('PATHWISE_SELENIUM_PASSWORD');
if (!$password || strlen($password) < 24) throw new RuntimeException('A generated password is required.');
$suffix = getenv('PATHWISE_SELENIUM_ID');

use App\Models\{AIRecommendation, Assignment, Course, CourseCategory, CourseReview, Department, Enrollment, Lesson, LessonProgress, Quiz, QuizResult, Role, StudentApplication, StudentRemovalRequest, Submission, User};
use Illuminate\Database\Eloquent\Model;

Model::unguard();

$dept = Department::create(['name' => 'College of Computer Studies']);
$dept2 = Department::create(['name' => 'College of Business']);

$users = [];
$makeUser = function (string $key, string $name, string $role, Department $department) use (&$users, $password, $suffix) {
    $user = new User;
    $user->forceFill([
        'name' => $name,
        'email' => $key.'+'.$suffix.'@example.test',
        'password' => $password,
        'department_id' => $department->id,
        'email_verified_at' => now(),
        'is_active' => true,
    ])->save();
    $user->roles()->attach(Role::firstOrCreate(['name' => $role])->id);

    return $users[$key] = $user;
};

$makeUser('edp', 'EDP Super Admin', 'super_admin', $dept);
$makeUser('dean', 'Dean Ramos', 'admin', $dept);
$makeUser('dean2', 'Dean Villanueva', 'admin', $dept2);
foreach (['teacher' => ['Prof. Santos', $dept], 'teacher2' => ['Prof. Reyes', $dept2]] as $key => [$name, $department]) {
    $teacher = $makeUser($key, $name, 'teacher', $department);
    StudentApplication::submitForTeacher($teacher, 'selenium_fixture')
        ->forceFill(['status' => 'approved', 'reviewed_by' => $users['edp']->id, 'reviewed_at' => now()])->save();
}
$studentNames = [
    'stu_lesson' => 'Ana Lopez', 'stu_complete' => 'Ben Cruz', 'stu_outsider' => 'Carl Diaz',
    'stu_take' => 'Dana Flores', 'stu_full' => 'Eli Garcia', 'stu_pass75' => 'Faye Hernandez',
    'stu_fail50' => 'Gio Ignacio', 'stu_points' => 'Hana Jimenez', 'stu_blank' => 'Ivan Kalaw',
    'stu_retake' => 'Jade Luna', 'stu_retake2' => 'Kyle Mendoza', 'stu_passed' => 'Lia Navarro',
    'stu_prog' => 'Mark Ocampo', 'stu_cert_ok' => 'Nina Pascual', 'stu_cert_low' => 'Owen Quinto',
    'stu_cert_wait' => 'Pia Ramos', 'stu_cert_active' => 'Quin Salazar', 'stu_rm1' => 'Rey Tan',
    'stu_rm2' => 'Sam Uy', 'stu_rm3' => 'Tina Valdez', 'stu_ai' => 'Uma Wong', 'stu_noquiz' => 'Vic Yap',
    'stu_reviewer' => 'Wena Zamora',
];
foreach ($studentNames as $key => $name) $makeUser($key, $name, 'student', $dept);
$makeUser('student2', 'Xian Aquino', 'student', $dept2);

$programming = CourseCategory::create(['name' => 'Programming', 'description' => 'Programming courses', 'is_active' => true]);
$business = CourseCategory::create(['name' => 'Business', 'description' => 'Business courses', 'is_active' => true]);
$unused = CourseCategory::create(['name' => 'Unused Category', 'description' => 'No course uses this category', 'is_active' => true]);
$networking = CourseCategory::create(['name' => 'Networking', 'description' => 'Networking courses', 'is_active' => true]);

$course = fn (User $teacher, CourseCategory $category, string $title, string $status, string $level = 'beginner', bool $certificate = false) => Course::create([
    'teacher_id' => $teacher->id, 'category_id' => $category->id, 'title' => $title,
    'description' => 'Synthetic course for Selenium unit testing.', 'difficulty_level' => $level,
    'price' => 0, 'status' => $status, 'estimated_hours' => 4, 'certificate_available' => $certificate,
]);
$textLesson = function (Course $c, string $title, int $order, bool $published, string $body) {
    $lesson = $c->lessons()->create(['title' => $title, 'lesson_type' => 'text', 'content' => strip_tags($body), 'lesson_order' => $order, 'is_published' => $published]);
    $lesson->sections()->create(['type' => 'text', 'body_format' => 'html', 'body' => $body, 'position' => 0]);

    return $lesson;
};
$quizLesson = fn (Course $c, string $title, int $order) => $c->lessons()->create(['title' => $title, 'lesson_type' => 'quiz', 'lesson_order' => $order, 'is_published' => true]);
$enroll = fn (string $student, Course $c, string $status = 'active', float $progress = 0) => Enrollment::create([
    'student_id' => $users[$student]->id, 'course_id' => $c->id, 'status' => $status, 'enrolled_at' => now()->subDays(7),
    'progress_percentage' => $progress, 'completed_at' => $status === 'completed' ? now()->subDay() : null,
]);

/* Course A: published course used by the student learning cases. */
$a = $course($users['teacher'], $programming, 'Introduction to Programming', 'published');
$a1 = $textLesson($a, 'Variables and Data Types', 1, true, '<h2>Variables</h2><p>A variable stores a value that your program can use and change.</p>');
$a2 = $textLesson($a, 'Control Structures', 2, true, '<p>Use if statements and loops to control the flow of a program.</p>');
$a3 = $textLesson($a, 'Upcoming Topic: Recursion', 3, false, '<p>This lesson is not published yet.</p>');
$a4 = $quizLesson($a, 'Quiz 1: Programming Basics', 4);
$quizA = Quiz::create(['course_id' => $a->id, 'lesson_id' => $a4->id, 'title' => 'Quiz 1: Programming Basics', 'description' => 'Answer all four questions.', 'passing_score' => 75, 'is_published' => true]);
$qs = [];
foreach ([
    ['Which keyword declares a constant in JavaScript?', 'const', 'var', 'loop', 'print', 'A', 5],
    ['Which symbol ends a statement in Java?', 'Colon', 'Semicolon', 'Comma', 'Period', 'B', 1],
    ['Which loop always runs at least once?', 'for', 'while', 'do-while', 'foreach', 'C', 1],
    ['Which data type stores true or false?', 'int', 'string', 'float', 'boolean', 'D', 1],
] as [$q, $oa, $ob, $oc, $od, $correct, $points]) {
    $qs[] = $quizA->questions()->create(['question' => $q, 'option_a' => $oa, 'option_b' => $ob, 'option_c' => $oc, 'option_d' => $od, 'correct_answer' => $correct, 'points' => $points])->id;
}
$a5 = $quizLesson($a, 'Quiz 2: Functions', 5);
$quizDraft = Quiz::create(['course_id' => $a->id, 'lesson_id' => $a5->id, 'title' => 'Quiz 2: Functions', 'passing_score' => 75, 'is_published' => false]);
$quizDraft->questions()->create(['question' => 'What does a function return by default in Python?', 'option_a' => 'None', 'option_b' => '0', 'correct_answer' => 'A', 'points' => 1]);

foreach (['stu_lesson', 'stu_complete', 'stu_take', 'stu_full', 'stu_pass75', 'stu_fail50', 'stu_points', 'stu_blank', 'stu_retake', 'stu_retake2', 'stu_passed', 'stu_rm1', 'stu_rm2', 'stu_rm3', 'stu_reviewer', 'stu_ai', 'stu_noquiz'] as $s) {
    $enroll($s, $a);
}
$enroll('stu_prog', $a, 'active', 25);
LessonProgress::create(['student_id' => $users['stu_prog']->id, 'lesson_id' => $a1->id, 'status' => 'completed', 'started_at' => now()->subDays(2), 'completed_at' => now()->subDay()]);

$result = fn (string $student, Quiz $quiz, int $score, int $total, int $attempt) => QuizResult::create([
    'student_id' => $users[$student]->id, 'quiz_id' => $quiz->id, 'score' => $score, 'total_items' => $total,
    'percentage' => round($score / $total * 100, 2), 'remarks' => $score / $total * 100 >= $quiz->passing_score ? 'passed' : 'failed',
    'attempt_number' => $attempt, 'completed_at' => now()->subHours(5 - $attempt),
]);
$result('stu_retake', $quizA, 2, 4, 1);
$result('stu_retake2', $quizA, 1, 4, 1);
$result('stu_passed', $quizA, 4, 4, 1);

/* Course B: draft course for the quiz builder cases. */
$b = $course($users['teacher'], $programming, 'Web Development Basics', 'draft');
$b1 = $quizLesson($b, 'Quiz: HTML Basics', 1);
$b2 = $quizLesson($b, 'Quiz: CSS Basics', 2);
$b3 = $quizLesson($b, 'Quiz: JavaScript Basics', 3);
$b4 = $quizLesson($b, 'Quiz: Web Forms', 4);
$quizB4 = Quiz::create(['course_id' => $b->id, 'lesson_id' => $b4->id, 'title' => 'Quiz: Web Forms', 'passing_score' => 70, 'is_published' => false]);
$quizB4->questions()->create(['question' => 'Which HTML element creates a form?', 'option_a' => '<form>', 'option_b' => '<input>', 'option_c' => '<table>', 'option_d' => '<div>', 'correct_answer' => 'A', 'points' => 1]);

/* Pending courses for the approval cases. */
$c = $course($users['teacher'], $programming, 'Database Fundamentals', 'pending');
$d = $course($users['teacher'], $programming, 'Mobile App Development', 'pending');
$d2 = $course($users['teacher'], $programming, 'Software Testing Basics', 'pending');
$e = $course($users['teacher2'], $business, 'Marketing Analytics', 'pending');
foreach ([$c, $d, $d2, $e] as $pending) $textLesson($pending, 'Course Overview', 1, true, '<p>Overview of the course.</p>');

/* Course F: certificates. */
$f = $course($users['teacher'], $programming, 'Capstone Project Writing', 'published', 'intermediate', true);
$textLesson($f, 'Writing the Proposal', 1, true, '<p>How to write a capstone proposal.</p>');
$f2 = $f->lessons()->create(['title' => 'Final Project', 'lesson_type' => 'activity', 'content' => 'Submit your final project.', 'lesson_order' => 2, 'is_published' => true]);
$activity = Assignment::create(['course_id' => $f->id, 'lesson_id' => $f2->id, 'title' => 'Final Project', 'description' => 'Submit your final project.', 'submission_type' => 'essay', 'answer_format' => 'text', 'max_score' => 100, 'is_activity' => true, 'is_published' => true]);
foreach (['stu_cert_ok' => [90, 'graded'], 'stu_cert_low' => [60, 'graded'], 'stu_cert_wait' => [null, 'submitted']] as $s => [$score, $status]) {
    $enroll($s, $f, 'completed', 100);
    Submission::create(['assignment_id' => $activity->id, 'student_id' => $users[$s]->id, 'answer_text' => 'My final project.', 'score' => $score, 'feedback' => $score ? 'Graded by teacher.' : null, 'status' => $status, 'submitted_at' => now()->subDays(2)]);
}
$enroll('stu_cert_active', $f, 'active', 50);

/* Course G: advanced course the AI recommendation points to. */
$g = $course($users['teacher'], $programming, 'Advanced Algorithms', 'published', 'advanced');
$textLesson($g, 'Graph Algorithms', 1, true, '<p>Breadth-first and depth-first search.</p>');
$g2 = $quizLesson($g, 'Quiz: Graphs', 2);
Quiz::create(['course_id' => $g->id, 'lesson_id' => $g2->id, 'title' => 'Quiz: Graphs', 'passing_score' => 75, 'is_published' => true])
    ->questions()->create(['question' => 'Which search uses a queue?', 'option_a' => 'BFS', 'option_b' => 'DFS', 'correct_answer' => 'A', 'points' => 1]);
$result('stu_ai', $quizA, 9, 10, 1); // 90%
$oldRec = AIRecommendation::create(['student_id' => $users['stu_noquiz']->id, 'course_id' => $g->id, 'recommendation_score' => 72.5, 'reason' => 'Old recommendation to delete (Selenium fixture).', 'is_viewed' => false]);

/* Course H: another department (teacher2) for isolation checks. */
$h = $course($users['teacher2'], $business, 'Business Communication', 'published');
$textLesson($h, 'Writing Business Letters', 1, true, '<p>Formal letter structure.</p>');
$enroll('student2', $h, 'active', 40);
CourseReview::create(['course_id' => $h->id, 'student_id' => $users['student2']->id, 'rating' => 4, 'comment' => 'Business review from another department.']);

/* Reviews on course A. */
$review = CourseReview::create(['course_id' => $a->id, 'student_id' => $users['stu_reviewer']->id, 'rating' => 2, 'comment' => 'Visit my site for free answers!!!']);
$freshReview = CourseReview::create(['course_id' => $a->id, 'student_id' => $users['stu_lesson']->id, 'rating' => 3, 'comment' => 'Good start, but I need more examples.']);
$hiddenReview = CourseReview::create(['course_id' => $a->id, 'student_id' => $users['stu_complete']->id, 'rating' => 5, 'comment' => 'Very clear lessons and helpful quizzes.', 'is_hidden' => true, 'hidden_reason' => 'Hidden by mistake during testing.', 'hidden_by' => $users['dean']->id]);

/* Pending removal requests for the review cases. */
$rm2 = StudentRemovalRequest::create(['instructor_id' => $users['teacher']->id, 'student_id' => $users['stu_rm2']->id, 'course_id' => $a->id, 'reason' => 'Student has not attended any session for six weeks.', 'status' => 'pending']);
$rm3 = StudentRemovalRequest::create(['instructor_id' => $users['teacher']->id, 'student_id' => $users['stu_rm3']->id, 'course_id' => $a->id, 'reason' => 'Student enrolled in the wrong course section.', 'status' => 'pending']);
$rm4 = StudentRemovalRequest::create(['instructor_id' => $users['teacher']->id, 'student_id' => $users['stu_noquiz']->id, 'course_id' => $a->id, 'reason' => 'Repeated late submissions without coordination.', 'status' => 'pending']);

$fixtures = [
    'id' => $suffix,
    'users' => array_map(fn (User $u) => ['id' => $u->id, 'email' => $u->email, 'name' => $u->name], $users),
    'categories' => ['programming' => $programming->id, 'business' => $business->id, 'unused' => $unused->id, 'networking' => $networking->id],
    'courses' => ['a' => $a->id, 'b' => $b->id, 'c' => $c->id, 'd' => $d->id, 'd2' => $d2->id, 'e' => $e->id, 'f' => $f->id, 'g' => $g->id, 'h' => $h->id],
    'lessons' => ['a1' => $a1->id, 'a2' => $a2->id, 'a3' => $a3->id, 'a4' => $a4->id, 'a5' => $a5->id, 'b1' => $b1->id, 'b2' => $b2->id, 'b3' => $b3->id, 'b4' => $b4->id],
    'quizzes' => ['a' => $quizA->id, 'draft' => $quizDraft->id, 'b4' => $quizB4->id],
    'questions' => $qs,
    'reviews' => ['spam' => $review->id, 'fresh' => $freshReview->id, 'hidden' => $hiddenReview->id],
    'removal' => ['rm2' => $rm2->id, 'rm3' => $rm3->id, 'rm4' => $rm4->id],
    'recommendations' => ['old' => $oldRec->id],
];
file_put_contents($runtime.'/fixtures.json', json_encode($fixtures, JSON_PRETTY_PRINT));
echo json_encode($fixtures);
