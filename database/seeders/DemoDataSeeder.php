<?php

namespace Database\Seeders;

use App\Models\Assignment;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\CourseReview;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\QuizResult;
use App\Models\Role;
use App\Models\Submission;
use App\Models\Transaction;
use App\Models\User;
use App\Support\PrivateFiles;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/*
| Sample data for demonstrations (for example the defense), on a local
| computer only:
|
|   php artisan db:seed --class=DemoDataSeeder
|
| - Runs only when APP_ENV=local; it does nothing anywhere else.
| - The demo accounts' password comes from DEMO_SEED_PASSWORD in .env.
| - Only adds records. Existing accounts and data are never changed or
|   deleted: an account that already exists keeps its name, password and
|   roles, and every record is looked up before it is created, so running
|   the seeder twice adds nothing the second time.
| - Names are marked "(Demo)" and emails end in @example.com.
| - No emails or notifications are sent.
*/
class DemoDataSeeder extends Seeder
{
    use WithoutModelEvents;

    public const RECEIPT_PATH = 'payment-proofs/demo-receipt.png';

    private const TEACHERS = [
        ['demo.teacher1@example.com', 'Ana Cruz (Demo)'],
        ['demo.teacher2@example.com', 'Ben Santos (Demo)'],
        ['demo.teacher3@example.com', 'Carla Reyes (Demo)'],
    ];

    private const STUDENTS = [
        ['demo.student01@example.com', 'Dan Flores (Demo)'],
        ['demo.student02@example.com', 'Ella Ramos (Demo)'],
        ['demo.student03@example.com', 'Felix Garcia (Demo)'],
        ['demo.student04@example.com', 'Gina Mendoza (Demo)'],
        ['demo.student05@example.com', 'Hugo Torres (Demo)'],
        ['demo.student06@example.com', 'Iris Navarro (Demo)'],
        ['demo.student07@example.com', 'Jon Castillo (Demo)'],
        ['demo.student08@example.com', 'Kara Villanueva (Demo)'],
        ['demo.student09@example.com', 'Leo Aquino (Demo)'],
        ['demo.student10@example.com', 'Mia Bautista (Demo)'],
        ['demo.student11@example.com', 'Noel Dela Cruz (Demo)'],
        ['demo.student12@example.com', 'Olive Pascual (Demo)'],
        ['demo.student13@example.com', 'Paolo Rivera (Demo)'],
        ['demo.student14@example.com', 'Quinn Domingo (Demo)'],
        ['demo.student15@example.com', 'Rosa Marquez (Demo)'],
    ];

    public function run(): void
    {
        if (! app()->environment('local')) {
            $this->command?->warn('DemoDataSeeder runs only when APP_ENV=local. Nothing was added.');

            return;
        }

        $password = (string) config('auth.seed_demo_password');

        if ($password === '') {
            $this->command?->error('Set DEMO_SEED_PASSWORD in .env first. Nothing was added.');

            return;
        }

        $teachers = collect(self::TEACHERS)
            ->map(fn ($account) => $this->account($account[0], $account[1], $password, 'teacher'))
            ->values();

        $students = collect(self::STUDENTS)
            ->map(fn ($account) => $this->account($account[0], $account[1], $password, 'student'))
            ->values();

        $it = CourseCategory::firstOrCreate(
            ['name' => 'Information Technology'],
            ['description' => 'Programming and web development', 'is_active' => true]
        );

        $business = CourseCategory::firstOrCreate(
            ['name' => 'Business'],
            ['description' => 'Business and entrepreneurship', 'is_active' => true]
        );

        $accounting = CourseCategory::firstOrCreate(
            ['name' => 'Accounting'],
            ['description' => 'Accounting and financial management', 'is_active' => true]
        );

        $web = $this->course($teachers[0], $it, 'Web Development Basics (Demo)', 'published', 0, 'Build simple web pages with HTML, CSS and JavaScript.');
        $python = $this->course($teachers[0], $it, 'Introduction to Python (Demo)', 'published', 499, 'Variables, conditions, loops and functions in Python.');
        $accountingCourse = $this->course($teachers[1], $accounting, 'Basic Accounting Principles (Demo)', 'published', 799, 'The accounting equation, journal entries and the trial balance.');
        $communication = $this->course($teachers[2], $business, 'Business Communication (Demo)', 'published', 0, 'Writing clear emails, memos and short reports.');
        $this->course($teachers[1], $business, 'Entrepreneurship 101 (Demo)', 'pending', 0, 'From a business idea to a simple business plan.');
        $this->course(
            $teachers[2],
            $it,
            'Java Fundamentals (Demo)',
            'rejected',
            0,
            'Classes, objects and methods in Java.',
            'Please add a lesson on methods and a short quiz before resubmitting.'
        );

        $webQuiz = $this->lessonsAndQuiz($web, ['What HTML Is', 'Styling with CSS', 'Adding JavaScript'], [
            ['What does HTML stand for?', 'HyperText Markup Language', 'High Tech Modern Language', 'Home Tool Markup Language', 'Hyperlink Text Mode Language', 'A'],
            ['Which tag makes the largest heading?', '<h6>', '<head>', '<h1>', '<title>', 'C'],
            ['Which language styles a web page?', 'HTML', 'CSS', 'SQL', 'Python', 'B'],
            ['Which tag adds a link?', '<a>', '<link>', '<href>', '<url>', 'A'],
            ['Where is JavaScript usually run?', 'In the printer', 'In the browser', 'In the monitor', 'In the keyboard', 'B'],
        ]);

        $pythonQuiz = $this->lessonsAndQuiz($python, ['Variables and Types', 'If Statements', 'Loops and Functions'], [
            ['Which keyword defines a function?', 'func', 'def', 'function', 'fn', 'B'],
            ['What does print(2 + 3) show?', '23', '5', '2 + 3', 'Error', 'B'],
            ['Which loop repeats over a list?', 'for', 'repeat', 'loop', 'each', 'A'],
            ['What type is "hello"?', 'int', 'bool', 'str', 'list', 'C'],
            ['Which symbol starts a comment?', '//', '#', '--', '/*', 'B'],
        ]);

        $accountingQuiz = $this->lessonsAndQuiz($accountingCourse, ['The Accounting Equation', 'Journal Entries', 'Trial Balance'], [
            ['Assets = Liabilities + ?', 'Revenue', 'Equity', 'Expenses', 'Cash', 'B'],
            ['A debit increases which account?', 'Revenue', 'Liabilities', 'Assets', 'Equity', 'C'],
            ['Where are transactions first recorded?', 'Journal', 'Ledger', 'Trial balance', 'Balance sheet', 'A'],
            ['A trial balance checks that...', 'Profit is high', 'Debits equal credits', 'Cash is positive', 'Taxes are paid', 'B'],
            ['Which is a liability?', 'Equipment', 'Accounts payable', 'Cash', 'Inventory', 'B'],
        ]);

        $communicationQuiz = $this->lessonsAndQuiz($communication, ['Writing Clear Emails', 'Memos and Notices', 'Short Reports'], [
            ['A good email subject line is...', 'Empty', 'Short and specific', 'All capital letters', 'A full paragraph', 'B'],
            ['A memo is usually sent...', 'Inside an organization', 'To newspapers', 'To customers only', 'By post only', 'A'],
            ['The main point of a report should be...', 'At the end only', 'Hidden', 'Near the start', 'In the footnotes', 'C'],
            ['Which tone fits a business email?', 'Rude', 'Professional', 'Sarcastic', 'Casual slang', 'B'],
            ['Before sending, you should...', 'Proofread', 'Add emojis', 'Remove the greeting', 'Use red text', 'A'],
        ]);

        $webAssignment = $this->assignment($web, 'Build a Personal Page', 'code', 'javascript', 'Create a page with a heading, a paragraph about yourself and a link.');
        $pythonAssignment = $this->assignment($python, 'Grade Calculator', 'code', 'python', 'Write a program that reads a score and prints the letter grade.');
        $accountingAssignment = $this->assignment($accountingCourse, 'Journal Entries Exercise', 'essay', null, 'Record the five transactions given in the lesson as journal entries.');
        $communicationAssignment = $this->assignment($communication, 'Write a Business Email', 'essay', null, 'Write an email asking a supplier for a price quotation.');

        /*
        | Enrollments: [student index, course, status, quiz, quiz score out of 5]
        */
        $enrollments = [
            [0, $web, 'completed', $webQuiz, 5],
            [1, $web, 'completed', $webQuiz, 4],
            [2, $web, 'active', $webQuiz, 3],
            [3, $web, 'active', null, null],
            [4, $web, 'cancelled', null, null],
            [5, $python, 'completed', $pythonQuiz, 5],
            [6, $python, 'active', $pythonQuiz, 4],
            [7, $python, 'active', null, null],
            [8, $accountingCourse, 'completed', $accountingQuiz, 4],
            [9, $accountingCourse, 'active', $accountingQuiz, 2],
            [10, $communication, 'completed', $communicationQuiz, 5],
            [11, $communication, 'active', $communicationQuiz, 3],
            [12, $communication, 'active', null, null],
            [13, $communication, 'cancelled', null, null],
            [0, $python, 'active', null, null],
            [1, $communication, 'active', null, null],
        ];

        foreach ($enrollments as $row) {
            [$index, $course, $status, $quiz, $quizScore] = $row;

            $student = $students[$index];

            $this->enroll($student, $course, $status);

            if ($quiz && $quizScore !== null) {
                $this->quizResult($student, $quiz, $quizScore);
            }

            if ($status === 'completed' && $course->certificate_available) {
                $this->certificate($student, $course);
            }
        }

        // Approved PayMongo payments for the paid courses (GCash, Maya, Card).
        $this->payment('DEMO-TRX-0001', $students[5], $python, 'gcash', 10);
        $this->payment('DEMO-TRX-0002', $students[6], $python, 'paymaya', 8);
        $this->payment('DEMO-TRX-0003', $students[7], $python, 'card', 6);
        $this->payment('DEMO-TRX-0004', $students[0], $python, 'gcash', 5);
        $this->payment('DEMO-TRX-0005', $students[8], $accountingCourse, 'paymaya', 12);
        $this->payment('DEMO-TRX-0006', $students[9], $accountingCourse, 'card', 9);

        // A manual receipt waiting for the Super Admin (not enrolled yet).
        $this->pendingReceipt('DEMO-TRX-0007', $students[14], $accountingCourse);

        /*
        | Submissions: graded, waiting, and one AI evaluation awaiting review.
        */
        $this->submission($webAssignment, $students[0], [
            'answer_text' => "<h1>Dan Flores</h1>\n<p>I am a first-year student who likes web design.</p>\n<a href=\"https://example.com\">My portfolio</a>",
            'status' => 'graded',
            'score' => 95,
            'feedback' => 'Complete and well organized. Nice work.',
        ]);

        $this->submission($webAssignment, $students[2], [
            'answer_text' => "<h1>Felix Garcia</h1>\n<p>Hello! This is my page.</p>",
            'status' => 'submitted',
        ]);

        $this->submission($pythonAssignment, $students[6], [
            'answer_text' => "score = int(input('Score: '))\nif score >= 90:\n    print('A')\nelif score >= 80:\n    print('B')\nelif score >= 75:\n    print('C')\nelse:\n    print('F')",
            'status' => 'submitted',
            'ai_score' => 88,
            'ai_feedback' => 'The program reads the score and prints the right letter grade for each range. Input that is not a number is not handled.',
            'ai_strengths' => "• Clear if/elif/else structure\n• Correct grade ranges",
            'ai_improvements' => "• Handle input that is not a number\n• Add a short comment explaining the ranges",
            'ai_evaluation_status' => 'completed',
            'ai_evaluated_at' => now()->subDay(),
        ]);

        $this->submission($pythonAssignment, $students[5], [
            'answer_text' => "def grade(score):\n    if score >= 90:\n        return 'A'\n    if score >= 80:\n        return 'B'\n    return 'C' if score >= 75 else 'F'\n\nprint(grade(int(input('Score: '))))",
            'status' => 'graded',
            'score' => 92,
            'feedback' => 'Good use of a function.',
        ]);

        $this->submission($accountingAssignment, $students[8], [
            'answer_text' => "1. Cash (Dr) 50,000 / Owner's Capital (Cr) 50,000\n2. Equipment (Dr) 15,000 / Cash (Cr) 15,000\n3. Supplies (Dr) 2,000 / Accounts Payable (Cr) 2,000",
            'status' => 'graded',
            'score' => 85,
            'feedback' => 'Correct entries. Two transactions are missing.',
        ]);

        $this->submission($communicationAssignment, $students[11], [
            'answer_text' => "Subject: Request for Price Quotation\n\nGood day,\n\nWe would like to ask for a price quotation for 50 reams of bond paper. Please include the delivery cost.\n\nThank you,\nOlive Pascual",
            'status' => 'submitted',
        ]);

        /*
        | Course reviews from enrolled students; one is hidden by an
        | administrator, so it is left out of the course rating.
        */
        $this->review($web, $students[0], 5, 'Clear lessons and good examples. I built my first web page in one afternoon.');
        $this->review($web, $students[1], 4, 'Very helpful for beginners. More exercises on CSS would be nice.');
        $this->review($web, $students[2], 5, null);
        $this->review($python, $students[5], 5, 'The grade calculator exercise was fun and practical.');
        $this->review($python, $students[6], 4, 'Good pace. The loops lesson could use one more example.');
        $this->review($accountingCourse, $students[8], 4, 'The journal entries lesson finally made debits and credits clear to me.');
        $this->review($communication, $students[10], 5, 'I use the email template from this course at work now.');
        $this->review(
            $communication,
            $students[11],
            1,
            'This is spam. Visit my page for cheap followers!',
            'Advertising and spam are not allowed in reviews.'
        );

        $this->command?->info('Demo data is ready: 3 teachers, 15 students, 6 courses (password from DEMO_SEED_PASSWORD).');
    }

    private function review(Course $course, User $student, int $rating, ?string $comment, ?string $hiddenReason = null): void
    {
        $exists = CourseReview::where('course_id', $course->id)
            ->where('student_id', $student->id)
            ->exists();

        if ($exists) {
            return;
        }

        $review = new CourseReview([
            'rating' => $rating,
            'comment' => $comment,
        ]);
        $review->course()->associate($course);
        $review->student()->associate($student);
        $review->forceFill([
            'is_hidden' => $hiddenReason !== null,
            'hidden_reason' => $hiddenReason,
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(2),
        ])->save();
    }

    /**
     * A new account gets the role and a verified email. An account that
     * already exists is returned unchanged.
     */
    private function account(string $email, string $name, string $password, string $role): User
    {
        $existing = User::where('email', $email)->first();

        if ($existing) {
            return $existing;
        }

        $user = new User;
        $user->forceFill([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'email_verified_at' => now(),
        ])->save();

        $user->roles()->attach(Role::firstOrCreate(['name' => $role])->id);

        return $user;
    }

    private function course(
        User $teacher,
        CourseCategory $category,
        string $title,
        string $status,
        int $price,
        string $description,
        ?string $reviewRemarks = null
    ): Course {
        return Course::firstOrCreate(
            ['teacher_id' => $teacher->id, 'title' => $title],
            [
                'category_id' => $category->id,
                'description' => $description,
                'difficulty_level' => 'beginner',
                'price' => $price,
                'status' => $status,
                'review_remarks' => $reviewRemarks,
                'estimated_hours' => 6,
                'certificate_available' => true,
            ]
        );
    }

    /**
     * Reading lessons followed by a final quiz lesson with five questions.
     *
     * @param  array<int, string>  $lessonTitles
     * @param  array<int, array{0: string, 1: string, 2: string, 3: string, 4: string, 5: string}>  $questions
     */
    private function lessonsAndQuiz(Course $course, array $lessonTitles, array $questions): Quiz
    {
        foreach ($lessonTitles as $index => $title) {
            Lesson::firstOrCreate(
                ['course_id' => $course->id, 'title' => $title],
                [
                    'content' => "This lesson covers {$title}. Read the notes, then try the examples on your own.",
                    'lesson_type' => 'text',
                    'lesson_order' => $index + 1,
                    'duration_minutes' => 20,
                    'is_preview' => $index === 0,
                    'is_published' => true,
                ]
            );
        }

        $quizLesson = Lesson::firstOrCreate(
            ['course_id' => $course->id, 'title' => 'Final Quiz'],
            [
                'lesson_type' => 'quiz',
                'lesson_order' => count($lessonTitles) + 1,
                'duration_minutes' => 15,
                'is_preview' => false,
                'is_published' => true,
            ]
        );

        $quiz = Quiz::firstOrCreate(
            ['course_id' => $course->id, 'title' => 'Final Quiz'],
            [
                'lesson_id' => $quizLesson->id,
                'description' => 'Five questions about this course.',
                'passing_score' => 75,
                'time_limit_minutes' => 15,
                'is_published' => true,
            ]
        );

        foreach ($questions as [$question, $a, $b, $c, $d, $answer]) {
            QuizQuestion::firstOrCreate(
                ['quiz_id' => $quiz->id, 'question' => $question],
                [
                    'option_a' => $a,
                    'option_b' => $b,
                    'option_c' => $c,
                    'option_d' => $d,
                    'correct_answer' => $answer,
                    'points' => 1,
                ]
            );
        }

        return $quiz;
    }

    private function assignment(Course $course, string $title, string $type, ?string $language, string $description): Assignment
    {
        return Assignment::firstOrCreate(
            ['course_id' => $course->id, 'title' => $title],
            [
                'description' => $description,
                'submission_type' => $type,
                'answer_format' => 'either',
                'programming_language' => $language,
                'ai_evaluation_enabled' => true,
                'evaluation_criteria' => 'Correctness, completeness and clarity.',
                'due_date' => now()->addWeeks(2),
                'max_score' => 100,
                'is_published' => true,
            ]
        );
    }

    private function enroll(User $student, Course $course, string $status): void
    {
        $enrollment = Enrollment::where('student_id', $student->id)
            ->where('course_id', $course->id)
            ->first();

        if ($enrollment) {
            return;
        }

        $enrollment = Enrollment::create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'status' => $status,
            'enrolled_at' => now()->subDays(20),
            'completed_at' => $status === 'completed' ? now()->subDays(3) : null,
            'progress_percentage' => match ($status) {
                'completed' => 100,
                'active' => 50,
                default => 25,
            },
        ]);

        // Lesson progress that matches the percentage.
        $lessons = $course->lessons()->orderBy('lesson_order')->get();
        $done = (int) round($lessons->count() * $enrollment->progress_percentage / 100);

        foreach ($lessons->take($done) as $lesson) {
            LessonProgress::firstOrCreate(
                ['student_id' => $student->id, 'lesson_id' => $lesson->id],
                [
                    'status' => 'completed',
                    'started_at' => now()->subDays(18),
                    'completed_at' => now()->subDays(10),
                    'time_spent_minutes' => 20,
                ]
            );
        }
    }

    private function quizResult(User $student, Quiz $quiz, int $score): void
    {
        $total = 5;
        $percentage = round($score / $total * 100, 2);

        QuizResult::firstOrCreate(
            ['student_id' => $student->id, 'quiz_id' => $quiz->id, 'attempt_number' => 1],
            [
                'score' => $score,
                'total_items' => $total,
                'percentage' => $percentage,
                'remarks' => $percentage >= $quiz->passing_score ? 'passed' : 'failed',
                'completed_at' => now()->subDays(5),
            ]
        );
    }

    private function certificate(User $student, Course $course): void
    {
        $exists = Certificate::where('student_id', $student->id)
            ->where('course_id', $course->id)
            ->exists();

        if ($exists) {
            return;
        }

        Certificate::create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'certificate_number' => Certificate::generateNumber(),
            'issued_date' => now()->subDays(3)->toDateString(),
            'status' => 'issued',
        ]);
    }

    private function payment(string $number, User $student, Course $course, string $method, int $daysAgo): void
    {
        Transaction::firstOrCreate(
            ['transaction_no' => $number],
            [
                'student_id' => $student->id,
                'course_id' => $course->id,
                'amount' => $course->price,
                'payment_method' => 'PayMongo',
                'paymongo_method' => $method,
                'payment_reference' => 'cs_demo_'.strtolower(str_replace('-', '_', $number)),
                'status' => 'approved',
                'remarks' => 'Demo payment.',
                'approved_at' => now()->subDays($daysAgo),
            ]
        );
    }

    private function pendingReceipt(string $number, User $student, Course $course): void
    {
        if (! Storage::disk(PrivateFiles::DISK)->exists(self::RECEIPT_PATH)) {
            Storage::disk(PrivateFiles::DISK)->put(self::RECEIPT_PATH, $this->receiptImage());
        }

        Transaction::firstOrCreate(
            ['transaction_no' => $number],
            [
                'student_id' => $student->id,
                'course_id' => $course->id,
                'amount' => $course->price,
                'payment_method' => 'Bank Transfer',
                'payment_reference' => 'DEMO-REF-1234',
                'payment_proof' => self::RECEIPT_PATH,
                'status' => 'pending',
                'remarks' => 'Demo receipt waiting for review.',
            ]
        );
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function submission(Assignment $assignment, User $student, array $attributes): void
    {
        Submission::firstOrCreate(
            ['assignment_id' => $assignment->id, 'student_id' => $student->id],
            array_merge(['submitted_at' => now()->subDays(2)], $attributes)
        );
    }

    /**
     * A small PNG that says "DEMO RECEIPT" (a plain grey image when the GD
     * extension is not installed).
     */
    private function receiptImage(): string
    {
        if (! function_exists('imagecreatetruecolor')) {
            return base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAAAAAA6fptVAAAACklEQVR4nGNgAAAAAgABc3UBGAAAAABJRU5ErkJggg==');
        }

        $image = imagecreatetruecolor(360, 200);
        $white = imagecolorallocate($image, 255, 255, 255);
        $dark = imagecolorallocate($image, 30, 41, 59);

        if ($white === false || $dark === false) {
            return base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAAAAAA6fptVAAAACklEQVR4nGNgAAAAAgABc3UBGAAAAABJRU5ErkJggg==');
        }

        imagefill($image, 0, 0, $white);
        imagerectangle($image, 4, 4, 355, 195, $dark);
        imagestring($image, 5, 110, 40, 'DEMO RECEIPT', $dark);
        imagestring($image, 4, 70, 90, 'Bank Transfer  PHP 799.00', $dark);
        imagestring($image, 3, 100, 130, 'Reference DEMO-REF-1234', $dark);

        ob_start();
        imagepng($image);
        imagedestroy($image);

        return (string) ob_get_clean();
    }
}
