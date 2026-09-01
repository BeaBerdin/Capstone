<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\QuizResult;
use App\Services\RecommendationService;
use Illuminate\Http\Request;

class QuizController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | ADMIN - QUIZZES
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        $quizzes = Quiz::with([
            'course',
            'lesson',
            'questions',
        ])
            ->latest()
            ->get();

        return view(
            'quizzes.index',
            compact('quizzes')
        );
    }


    public function create()
    {
        $courses = Course::orderBy('title')->get();

        $lessons = Lesson::orderBy('title')->get();

        return view(
            'quizzes.create',
            compact(
                'courses',
                'lessons'
            )
        );
    }


    public function store(Request $request)
    {
        $validated = $request->validate([
            'course_id' => [
                'required',
                'exists:courses,id',
            ],

            'lesson_id' => [
                'nullable',
                'exists:lessons,id',
            ],

            'title' => [
                'required',
                'string',
                'max:255',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'passing_score' => [
                'required',
                'integer',
                'min:1',
                'max:100',
            ],

            'time_limit_minutes' => [
                'nullable',
                'integer',
                'min:1',
            ],

            'is_published' => [
                'nullable',
                'boolean',
            ],
        ]);


        Quiz::create([
            'course_id' =>
                $validated['course_id'],

            'lesson_id' =>
                $validated['lesson_id'] ?? null,

            'title' =>
                $validated['title'],

            'description' =>
                $validated['description'] ?? null,

            'passing_score' =>
                $validated['passing_score'],

            'time_limit_minutes' =>
                $validated['time_limit_minutes'] ?? null,

            'is_published' =>
                $request->boolean('is_published'),
        ]);


        return redirect()
            ->route('quizzes.index')
            ->with(
                'success',
                'Quiz created successfully.'
            );
    }


    public function edit(Quiz $quiz)
    {
        $courses =
            Course::orderBy('title')->get();

        $lessons =
            Lesson::orderBy('title')->get();

        return view(
            'quizzes.edit',
            compact(
                'quiz',
                'courses',
                'lessons'
            )
        );
    }


    public function update(
        Request $request,
        Quiz $quiz
    ) {
        $validated = $request->validate([
            'course_id' => [
                'required',
                'exists:courses,id',
            ],

            'lesson_id' => [
                'nullable',
                'exists:lessons,id',
            ],

            'title' => [
                'required',
                'string',
                'max:255',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'passing_score' => [
                'required',
                'integer',
                'min:1',
                'max:100',
            ],

            'time_limit_minutes' => [
                'nullable',
                'integer',
                'min:1',
            ],

            'is_published' => [
                'nullable',
                'boolean',
            ],
        ]);


        $quiz->update([
            'course_id' =>
                $validated['course_id'],

            'lesson_id' =>
                $validated['lesson_id'] ?? null,

            'title' =>
                $validated['title'],

            'description' =>
                $validated['description'] ?? null,

            'passing_score' =>
                $validated['passing_score'],

            'time_limit_minutes' =>
                $validated['time_limit_minutes'] ?? null,

            'is_published' =>
                $request->boolean('is_published'),
        ]);


        return redirect()
            ->route('quizzes.index')
            ->with(
                'success',
                'Quiz updated successfully.'
            );
    }


    public function destroy(Quiz $quiz)
    {
        $quiz->delete();

        return redirect()
            ->route('quizzes.index')
            ->with(
                'success',
                'Quiz deleted successfully.'
            );
    }



    /*
    |--------------------------------------------------------------------------
    | TEACHER - QUIZ BUILDER
    |--------------------------------------------------------------------------
    */

    public function teacherBuilder(
        Lesson $lesson
    ) {
        $lesson->load([
            'course.category',
        ]);


        if (
            (int) $lesson->course->teacher_id
            !==
            (int) auth()->id()
        ) {
            abort(
                403,
                'Unauthorized'
            );
        }


        if (
            $lesson->lesson_type
            !==
            'quiz'
        ) {
            return redirect()
                ->route(
                    'teacher.lessons',
                    $lesson->course
                )
                ->with(
                    'error',
                    'This lesson is not a quiz lesson.'
                );
        }


        $quiz = Quiz::with('questions')
            ->where(
                'lesson_id',
                $lesson->id
            )
            ->first();


        return view(
            'teacher.quiz-builder',
            compact(
                'lesson',
                'quiz'
            )
        );
    }



    /*
    |--------------------------------------------------------------------------
    | TEACHER - SAVE QUIZ SETTINGS
    |--------------------------------------------------------------------------
    */

    public function teacherSaveQuiz(
        Request $request,
        Lesson $lesson
    ) {
        $lesson->load('course');


        if (
            (int) $lesson->course->teacher_id
            !==
            (int) auth()->id()
        ) {
            abort(
                403,
                'Unauthorized'
            );
        }


        if (
            $lesson->lesson_type
            !==
            'quiz'
        ) {
            abort(
                422,
                'This lesson is not a quiz lesson.'
            );
        }


        $validated = $request->validate([
            'title' => [
                'required',
                'string',
                'max:255',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'passing_score' => [
                'required',
                'integer',
                'min:1',
                'max:100',
            ],

            'time_limit_minutes' => [
                'nullable',
                'integer',
                'min:1',
            ],

            'is_published' => [
                'nullable',
                'boolean',
            ],
        ]);


        $quiz = Quiz::firstOrNew([
            'lesson_id' =>
                $lesson->id,
        ]);


        $quiz->course_id =
            $lesson->course_id;

        $quiz->lesson_id =
            $lesson->id;

        $quiz->title =
            $validated['title'];

        $quiz->description =
            $validated['description']
            ?? null;

        $quiz->passing_score =
            $validated['passing_score'];

        $quiz->time_limit_minutes =
            $validated['time_limit_minutes']
            ?? null;

        $quiz->is_published =
            $request->boolean(
                'is_published'
            );

        $quiz->save();


        return redirect()
            ->route(
                'teacher.quiz.builder',
                $lesson
            )
            ->with(
                'success',
                'Quiz settings saved successfully.'
            );
    }



    /*
    |--------------------------------------------------------------------------
    | TEACHER - ADD QUESTION
    |--------------------------------------------------------------------------
    */

    public function teacherStoreQuestion(
        Request $request,
        Quiz $quiz
    ) {
        $quiz->load('course');


        if (
            (int) $quiz->course->teacher_id
            !==
            (int) auth()->id()
        ) {
            abort(
                403,
                'Unauthorized'
            );
        }


        $validated =
            $this->validateQuestion(
                $request
            );


        $this->validateCorrectOption(
            $validated
        );


        QuizQuestion::create([
            'quiz_id' =>
                $quiz->id,

            'question' =>
                $validated['question'],

            'option_a' =>
                $validated['option_a'],

            'option_b' =>
                $validated['option_b'],

            'option_c' =>
                $validated['option_c']
                ?? null,

            'option_d' =>
                $validated['option_d']
                ?? null,

            'correct_answer' =>
                $validated['correct_answer'],

            'points' =>
                $validated['points'],
        ]);


        return redirect()
            ->route(
                'teacher.quiz.builder',
                $quiz->lesson_id
            )
            ->with(
                'success',
                'Question added successfully.'
            );
    }



    /*
    |--------------------------------------------------------------------------
    | TEACHER - UPDATE QUESTION
    |--------------------------------------------------------------------------
    */

    public function teacherUpdateQuestion(
        Request $request,
        Quiz $quiz,
        QuizQuestion $question
    ) {
        $quiz->load('course');


        if (
            (int) $quiz->course->teacher_id
            !==
            (int) auth()->id()
        ) {
            abort(
                403,
                'Unauthorized'
            );
        }


        if (
            (int) $question->quiz_id
            !==
            (int) $quiz->id
        ) {
            abort(404);
        }


        $validated =
            $this->validateQuestion(
                $request
            );


        $this->validateCorrectOption(
            $validated
        );


        $question->update([
            'question' =>
                $validated['question'],

            'option_a' =>
                $validated['option_a'],

            'option_b' =>
                $validated['option_b'],

            'option_c' =>
                $validated['option_c']
                ?? null,

            'option_d' =>
                $validated['option_d']
                ?? null,

            'correct_answer' =>
                $validated['correct_answer'],

            'points' =>
                $validated['points'],
        ]);


        return redirect()
            ->route(
                'teacher.quiz.builder',
                $quiz->lesson_id
            )
            ->with(
                'success',
                'Question updated successfully.'
            );
    }



    /*
    |--------------------------------------------------------------------------
    | TEACHER - DELETE QUESTION
    |--------------------------------------------------------------------------
    */

    public function teacherDeleteQuestion(
        Quiz $quiz,
        QuizQuestion $question
    ) {
        $quiz->load('course');


        if (
            (int) $quiz->course->teacher_id
            !==
            (int) auth()->id()
        ) {
            abort(
                403,
                'Unauthorized'
            );
        }


        if (
            (int) $question->quiz_id
            !==
            (int) $quiz->id
        ) {
            abort(404);
        }


        $question->delete();


        return redirect()
            ->route(
                'teacher.quiz.builder',
                $quiz->lesson_id
            )
            ->with(
                'success',
                'Question deleted successfully.'
            );
    }



    /*
    |--------------------------------------------------------------------------
    | QUESTION VALIDATION
    |--------------------------------------------------------------------------
    */

    private function validateQuestion(
        Request $request
    ): array {
        return $request->validate([
            'question' => [
                'required',
                'string',
            ],

            'option_a' => [
                'required',
                'string',
                'max:255',
            ],

            'option_b' => [
                'required',
                'string',
                'max:255',
            ],

            'option_c' => [
                'nullable',
                'string',
                'max:255',
            ],

            'option_d' => [
                'nullable',
                'string',
                'max:255',
            ],

            'correct_answer' => [
                'required',
                'in:A,B,C,D',
            ],

            'points' => [
                'required',
                'integer',
                'min:1',
                'max:100',
            ],
        ]);
    }


    private function validateCorrectOption(
        array $validated
    ): void {
        $field = match (
            $validated['correct_answer']
        ) {
            'A' => 'option_a',
            'B' => 'option_b',
            'C' => 'option_c',
            'D' => 'option_d',
        };


        if (
            empty(
                $validated[$field] ?? null
            )
        ) {
            abort(
                422,
                'The selected correct answer must contain an option.'
            );
        }
    }



    /*
    |--------------------------------------------------------------------------
    | STUDENT - TAKE QUIZ
    |--------------------------------------------------------------------------
    */

    public function take(Quiz $quiz)
    {
        $quiz->load([
            'course',
            'questions',
        ]);


        return view(
            'student.take-quiz',
            compact('quiz')
        );
    }



    /*
    |--------------------------------------------------------------------------
    | STUDENT - SUBMIT QUIZ
    |--------------------------------------------------------------------------
    */

    public function submit(
        Request $request,
        Quiz $quiz
    ) {
        $quiz->load([
            'questions',
            'course.category',
            'course.lessons',
        ]);


        $score = 0;

        $totalItems =
            $quiz->questions->count();


        foreach (
            $quiz->questions
            as $question
        ) {
            $answer =
                $request->input(
                    'question_'
                    .
                    $question->id
                );


            if (
                $answer
                ===
                $question->correct_answer
            ) {
                $score++;
            }
        }


        $percentage =
            $totalItems > 0
                ? round(
                    (
                        $score
                        /
                        $totalItems
                    )
                    *
                    100,
                    2
                )
                : 0;


        $remarks =
            $percentage
            >=
            $quiz->passing_score
                ? 'passed'
                : 'failed';


        QuizResult::create([
            'student_id' =>
                auth()->id(),

            'quiz_id' =>
                $quiz->id,

            'score' =>
                $score,

            'total_items' =>
                $totalItems,

            'percentage' =>
                $percentage,

            'remarks' =>
                $remarks,

            'attempt_number' =>
                $this
                    ->getNextAttemptNumber(
                        $quiz
                    ),

            'completed_at' =>
                now(),
        ]);


        app(
            RecommendationService::class
        )->generate(
            auth()->id(),
            $quiz,
            $percentage
        );


        if (
            $remarks
            ===
            'failed'
        ) {
            $this
                ->markCourseAsActiveAfterFailedQuiz(
                    $quiz
                );
        }


        if (
            $remarks
            ===
            'passed'
        ) {
            $this
                ->generateCertificateIfEligible(
                    $quiz
                );
        }


        return redirect()
            ->route(
                'student.learn.course',
                $quiz->course
            )
            ->with(
                'success',
                'Quiz submitted. Your score is '
                .
                $percentage
                .
                '%.'
            );
    }



    private function getNextAttemptNumber(
        Quiz $quiz
    ): int {
        return QuizResult::where(
                'student_id',
                auth()->id()
            )
            ->where(
                'quiz_id',
                $quiz->id
            )
            ->count()
            + 1;
    }



    private function markCourseAsActiveAfterFailedQuiz(
        Quiz $quiz
    ): void {
        $studentId =
            auth()->id();

        $course =
            $quiz->course;


        if (!$course) {
            return;
        }


        Enrollment::where(
            'student_id',
            $studentId
        )
            ->where(
                'course_id',
                $course->id
            )
            ->update([
                'status' =>
                    'active',

                'progress_percentage' =>
                    100,
            ]);
    }



    private function generateCertificateIfEligible(
        Quiz $quiz
    ): void {
        $studentId =
            auth()->id();

        $course =
            $quiz->course;


        if (
            !$course
            ||
            !$course->certificate_available
        ) {
            return;
        }


        $totalLessons =
            $course
                ->lessons()
                ->count();


        $completedLessons =
            LessonProgress::where(
                'student_id',
                $studentId
            )
                ->whereHas(
                    'lesson',
                    function ($query) use ($course) {

                        $query->where(
                            'course_id',
                            $course->id
                        );
                    }
                )
                ->where(
                    'status',
                    'completed'
                )
                ->count();


        if (
            $totalLessons > 0
            &&
            $completedLessons
            <
            $totalLessons
        ) {
            return;
        }


        Enrollment::where(
            'student_id',
            $studentId
        )
            ->where(
                'course_id',
                $course->id
            )
            ->update([
                'status' =>
                    'completed',

                'progress_percentage' =>
                    100,
            ]);


        Certificate::firstOrCreate(
            [
                'student_id' =>
                    $studentId,

                'course_id' =>
                    $course->id,
            ],
            [
                'certificate_number' =>
                    $this
                        ->generateCertificateNumber(),

                'issued_date' =>
                    now()->toDateString(),

                'status' =>
                    'issued',
            ]
        );
    }



    private function generateCertificateNumber(): string
    {
        $count =
            Certificate::count()
            + 1;


        return
            'PATH-'
            .
            now()->format('Y')
            .
            '-'
            .
            str_pad(
                $count,
                5,
                '0',
                STR_PAD_LEFT
            );
    }
}