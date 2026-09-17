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
use App\Services\GeminiQuizService;
use App\Services\RecommendationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Throwable;

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
    | TEACHER - COURSE MODIFICATION GUARD
    |--------------------------------------------------------------------------
    */

    private function ensureTeacherCanModifyCourse(
        Course $course
    ): void {
        if (
            (int) $course->teacher_id
            !==
            (int) auth()->id()
        ) {
            abort(
                403,
                'Unauthorized'
            );
        }

        if (
            !in_array(
                strtolower(
                    trim(
                        (string) $course->status
                    )
                ),
                [
                    'draft',
                    'rejected',
                ],
                true
            )
        ) {
            abort(
                403,
                'This quiz cannot be modified while the course is pending approval or published.'
            );
        }
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


        $quizCanBeModified =
            in_array(
                strtolower(
                    trim(
                        (string) $lesson->course->status
                    )
                ),
                [
                    'draft',
                    'rejected',
                ],
                true
            );


        return view(
            'teacher.quiz-builder',
            compact(
                'lesson',
                'quiz',
                'quizCanBeModified'
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


        $this->ensureTeacherCanModifyCourse(
            $lesson->course
        );


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
    | TEACHER - GENERATE QUESTIONS WITH GEMINI
    |--------------------------------------------------------------------------
    */

    public function teacherGenerateQuestions(
        Request $request,
        Quiz $quiz,
        GeminiQuizService $geminiQuizService
    ) {
        $quiz->load([
            'course',
            'lesson',
        ]);


        $this->ensureTeacherCanModifyCourse(
            $quiz->course
        );


        if (
            !$quiz->lesson
            ||
            $quiz->lesson->lesson_type
            !==
            'quiz'
        ) {
            abort(
                422,
                'This quiz is not connected to a valid quiz lesson.'
            );
        }


        $validated = $request->validate([
            'question_count' => [
                'required',
                'integer',
                'min:1',
                'max:10',
            ],

            'difficulty' => [
                'required',
                'in:beginner,intermediate,advanced',
            ],
        ]);


        $requestedCount =
            (int) $validated['question_count'];


        $existingCount =
            $quiz
                ->questions()
                ->count();


        if (
            $existingCount
            +
            $requestedCount
            >
            50
        ) {
            return redirect()
                ->route(
                    'teacher.quiz.builder',
                    $quiz->lesson_id
                )
                ->withErrors([
                    'question_count' =>
                        'A quiz can contain up to 50 questions. Reduce the number of questions to generate.',
                ]);
        }


        $hasSourceMaterial =
            $quiz
                ->course
                ->lessons()
                ->where(
                    'lesson_type',
                    'text'
                )
                ->where(
                    'is_published',
                    true
                )
                ->whereNotNull(
                    'content'
                )
                ->where(
                    'content',
                    '!=',
                    ''
                )
                ->exists();


        if (!$hasSourceMaterial) {
            return redirect()
                ->route(
                    'teacher.quiz.builder',
                    $quiz->lesson_id
                )
                ->with(
                    'error',
                    'AI generation needs at least one published Reading lesson with content in this course.'
                );
        }


        /*
         * Project-wide safety limits.
         *
         * These limits protect the shared Gemini quota:
         * - at most 4 AI generation requests per minute
         * - at most 20 AI generation requests per day
         *
         * One generation request may create up to 10 questions.
         */
        $minuteKey =
            'pathwise:gemini:quiz:minute';


        $dailyKey =
            'pathwise:gemini:quiz:daily:'
            .
            now()->toDateString();


        if (
            RateLimiter::tooManyAttempts(
                $minuteKey,
                4
            )
        ) {
            $seconds =
                RateLimiter::availableIn(
                    $minuteKey
                );


            return redirect()
                ->route(
                    'teacher.quiz.builder',
                    $quiz->lesson_id
                )
                ->with(
                    'error',
                    'AI generation is temporarily limited to protect the Gemini quota. Please try again in '
                    .
                    max(
                        1,
                        $seconds
                    )
                    .
                    ' seconds.'
                );
        }


        if (
            RateLimiter::tooManyAttempts(
                $dailyKey,
                20
            )
        ) {
            return redirect()
                ->route(
                    'teacher.quiz.builder',
                    $quiz->lesson_id
                )
                ->with(
                    'error',
                    'PathWise has reached its 20-request AI generation safety limit for today. Please try again tomorrow.'
                );
        }


        RateLimiter::hit(
            $minuteKey,
            60
        );


        try {
            $candidateCount =
                min(
                    10,
                    $requestedCount + 2
                );


            $generatedQuestions =
                $geminiQuizService
                    ->generateQuestions(
                        $quiz->course,
                        $candidateCount,
                        $validated['difficulty']
                    );


            /*
             * Count only a successful Gemini generation
             * against the PathWise daily safety limit.
             */
            $secondsUntilEndOfDay =
                max(
                    60,
                    (int) now()->diffInSeconds(
                        now()->endOfDay()
                    )
                );


            RateLimiter::hit(
                $dailyKey,
                $secondsUntilEndOfDay
            );


            $existingQuestionKeys =
                $quiz
                    ->questions()
                    ->pluck('question')
                    ->mapWithKeys(
                        function ($question) {
                            $key =
                                mb_strtolower(
                                    trim(
                                        preg_replace(
                                            '/\s+/u',
                                            ' ',
                                            (string) $question
                                        )
                                        ??
                                        (string) $question
                                    )
                                );


                            return [
                                $key => true,
                            ];
                        }
                    );


            $questionsToInsert = [];


            foreach (
                $generatedQuestions
                as
                $generatedQuestion
            ) {
                if (
                    count($questionsToInsert)
                    >=
                    $requestedCount
                ) {
                    break;
                }


                $questionKey =
                    mb_strtolower(
                        trim(
                            preg_replace(
                                '/\s+/u',
                                ' ',
                                (string) $generatedQuestion['question']
                            )
                            ??
                            (string) $generatedQuestion['question']
                        )
                    );


                if (
                    $existingQuestionKeys
                        ->has(
                            $questionKey
                        )
                ) {
                    continue;
                }


                $existingQuestionKeys[
                    $questionKey
                ] = true;


                $questionsToInsert[] =
                    $generatedQuestion;
            }


            if (
                empty(
                    $questionsToInsert
                )
            ) {
                return redirect()
                    ->route(
                        'teacher.quiz.builder',
                        $quiz->lesson_id
                    )
                    ->with(
                        'error',
                        'Gemini generated questions that already exist in this quiz. No duplicate questions were added.'
                    );
            }


            DB::transaction(
                function () use (
                    $quiz,
                    $questionsToInsert
                ) {
                    foreach (
                        $questionsToInsert
                        as
                        $question
                    ) {
                        QuizQuestion::create([
                            'quiz_id' =>
                                $quiz->id,

                            'question' =>
                                $question['question'],

                            'option_a' =>
                                $question['option_a'],

                            'option_b' =>
                                $question['option_b'],

                            'option_c' =>
                                $question['option_c'],

                            'option_d' =>
                                $question['option_d'],

                            'correct_answer' =>
                                $question['correct_answer'],

                            'points' =>
                                $question['points'],
                        ]);
                    }
                }
            );


            $generatedCount =
                count(
                    $questionsToInsert
                );


            $successMessage =
                $generatedCount
                .
                ' AI-generated '
                .
                (
                    $generatedCount === 1
                        ? 'question was'
                        : 'questions were'
                )
                .
                ' added successfully.';


            if (
                $generatedCount
                <
                $requestedCount
            ) {
                $successMessage .=
                    ' Gemini returned some questions that matched existing quiz questions, so duplicates were skipped.';
            }


            $successMessage .=
                ' Review and edit them before publishing the quiz.';


            return redirect()
                ->route(
                    'teacher.quiz.builder',
                    $quiz->lesson_id
                )
                ->with(
                    'success',
                    $successMessage
                );
        } catch (Throwable $exception) {
            report(
                $exception
            );


            $message =
                mb_strtolower(
                    $exception->getMessage()
                );


            if (
                str_contains(
                    $message,
                    '429'
                )
                ||
                str_contains(
                    $message,
                    'quota'
                )
                ||
                str_contains(
                    $message,
                    'rate limit'
                )
            ) {
                return redirect()
                    ->route(
                        'teacher.quiz.builder',
                        $quiz->lesson_id
                    )
                    ->with(
                        'error',
                        'Gemini is temporarily rate-limited. Your quiz was not changed. Please wait before trying again.'
                    );
            }


            return redirect()
                ->route(
                    'teacher.quiz.builder',
                    $quiz->lesson_id
                )
                ->with(
                    'error',
                    'Gemini could not generate questions right now. Your quiz was not changed. Please try again later.'
                );
        }
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


        $this->ensureTeacherCanModifyCourse(
            $quiz->course
        );


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


        $this->ensureTeacherCanModifyCourse(
            $quiz->course
        );


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


        $this->ensureTeacherCanModifyCourse(
            $quiz->course
        );


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
    | STUDENT - QUIZ ACCESS GUARD
    |--------------------------------------------------------------------------
    */

    private function ensureStudentCanAccessQuiz(
        Quiz $quiz
    ): void {
        $quiz->loadMissing([
            'course',
            'lesson',
        ]);

        $course = $quiz->course;

        if (!$course) {
            abort(
                404,
                'Course not found.'
            );
        }

        if (
            strtolower(
                trim(
                    (string) $course->status
                )
            )
            !==
            'published'
        ) {
            abort(
                403,
                'This course is not currently available to students.'
            );
        }

        if (!(bool) $quiz->is_published) {
            abort(
                403,
                'This quiz is not currently available to students.'
            );
        }

        /*
         * If this is a lesson-linked quiz, make sure the linked lesson
         * really belongs to the same course and is a quiz lesson.
         */
        if ($quiz->lesson_id) {
            if (
                !$quiz->lesson
                ||
                (int) $quiz->lesson->course_id
                    !==
                (int) $course->id
                ||
                strtolower(
                    trim(
                        (string) $quiz->lesson->lesson_type
                    )
                )
                    !==
                'quiz'
            ) {
                abort(
                    404,
                    'Quiz lesson not found.'
                );
            }
        }

        $isEnrolled = Enrollment::where(
                'student_id',
                auth()->id()
            )
            ->where(
                'course_id',
                $course->id
            )
            ->exists();

        if (!$isEnrolled) {
            abort(
                403,
                'You must be enrolled in this course to access this quiz.'
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
            'lesson',
            'questions',
        ]);


        $this->ensureStudentCanAccessQuiz(
            $quiz
        );


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
            'lesson',
            'course.category',
            'course.lessons',
        ]);


        $this->ensureStudentCanAccessQuiz(
            $quiz
        );


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
                ->syncCourseProgressAfterPassedQuiz(
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


        $progressPercentage =
            $totalLessons > 0
                ? (int) round(
                    (
                        $completedLessons
                        /
                        $totalLessons
                    )
                    *
                    100
                )
                : 0;


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
                    $progressPercentage,
            ]);
    }



    private function syncCourseProgressAfterPassedQuiz(
        Quiz $quiz
    ): void {
        $studentId =
            auth()->id();

        $course =
            $quiz->course;


        if (!$course) {
            return;
        }


        /*
         * If this quiz belongs to a quiz lesson, passing the quiz also
         * completes that lesson in the persisted lesson_progress table.
         * This keeps My Courses, teacher analytics, and the learning page
         * synchronized instead of relying only on UI-calculated progress.
         */
        if ($quiz->lesson_id) {
            LessonProgress::updateOrCreate(
                [
                    'student_id' =>
                        $studentId,

                    'lesson_id' =>
                        $quiz->lesson_id,
                ],
                [
                    'status' =>
                        'completed',

                    'completed_at' =>
                        now(),
                ]
            );
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


        $progressPercentage =
            $totalLessons > 0
                ? round(
                    (
                        $completedLessons
                        /
                        $totalLessons
                    )
                    *
                    100,
                    2
                )
                : 0;


        /*
         * Match the student learning page: a course may have one optional
         * standalone/final quiz (a published quiz with no lesson_id).
         * The course is complete only when all lessons are complete and the
         * latest final-quiz attempt is passed, when such a quiz exists.
         */
        $finalQuiz = Quiz::where(
                'course_id',
                $course->id
            )
            ->where(
                'is_published',
                true
            )
            ->whereNull(
                'lesson_id'
            )
            ->first();


        $passedFinalQuiz = true;


        if ($finalQuiz) {
            $latestFinalQuizResult =
                QuizResult::where(
                    'student_id',
                    $studentId
                )
                    ->where(
                        'quiz_id',
                        $finalQuiz->id
                    )
                    ->orderByDesc(
                        'completed_at'
                    )
                    ->orderByDesc(
                        'id'
                    )
                    ->first();


            $passedFinalQuiz =
                $latestFinalQuizResult
                &&
                strtolower(
                    (string) (
                        $latestFinalQuizResult->remarks
                        ??
                        ''
                    )
                )
                ===
                'passed';
        }


        $allLessonsCompleted =
            $totalLessons > 0
            &&
            $completedLessons >= $totalLessons;


        $courseCompleted =
            $allLessonsCompleted
            &&
            $passedFinalQuiz;


        $enrollment = Enrollment::where(
            'student_id',
            $studentId
        )
            ->where(
                'course_id',
                $course->id
            )
            ->first();


        if ($enrollment) {
            $enrollmentData = [
                'progress_percentage' =>
                    $progressPercentage,
            ];


            if ($courseCompleted) {
                $enrollmentData['status'] =
                    'completed';

                $enrollmentData['completed_at'] =
                    $enrollment->completed_at
                    ??
                    now();
            } else {
                $enrollmentData['status'] =
                    'active';
            }


            $enrollment->update(
                $enrollmentData
            );
        }


        if (
            !$courseCompleted
            ||
            !$course->certificate_available
        ) {
            return;
        }


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