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
use App\Support\AiAvailability;
use App\Exceptions\AiResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class QuizController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | ADMIN - QUIZZES
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        $quizzes = Quiz::with([
            'course',
            'lesson',
            'questions',
        ])
            ->manageableBy($request->user())
            ->latest()
            ->get();

        return view(
            'quizzes.index',
            compact('quizzes')
        );
    }


    public function create(Request $request)
    {
        $courses = Course::manageableBy($request->user())->orderBy('title')->get();

        $lessons = Lesson::whereHas('course', fn ($course) => $course->manageableBy($request->user()))
            ->orderBy('title')
            ->get();

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
                'integer',
                'exists:courses,id',
            ],

            'lesson_id' => [
                'nullable',
                'integer',
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


        $this->authorizeQuizDestination($request, $validated);

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


    public function edit(Request $request, Quiz $quiz)
    {
        abort_unless(Quiz::manageableBy($request->user())->whereKey($quiz)->exists(), 403);

        $courses =
            Course::manageableBy($request->user())->orderBy('title')->get();

        $lessons = Lesson::whereHas('course', fn ($course) => $course->manageableBy($request->user()))
            ->orderBy('title')
            ->get();

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
        abort_unless(Quiz::manageableBy($request->user())->whereKey($quiz)->exists(), 403);

        $validated = $request->validate([
            'course_id' => [
                'required',
                'integer',
                'exists:courses,id',
            ],

            'lesson_id' => [
                'nullable',
                'integer',
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


        $this->authorizeQuizDestination($request, $validated);

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


    public function destroy(Request $request, Quiz $quiz)
    {
        abort_unless(Quiz::manageableBy($request->user())->whereKey($quiz)->exists(), 403);

        $quiz->delete();

        return redirect()
            ->route('quizzes.index')
            ->with(
                'success',
                'Quiz deleted successfully.'
            );
    }

    private function authorizeQuizDestination(Request $request, array $validated): void
    {
        Course::authorizeManagement($request->user(), $validated['course_id']);

        if (isset($validated['lesson_id'])) {
            abort_unless(
                Lesson::whereKey($validated['lesson_id'])->where('course_id', $validated['course_id'])->exists(),
                422,
                'Selected lesson does not belong to the selected course.'
            );
        }
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

    private function ensureTeacherCanModifyQuiz(Quiz $quiz): void
    {
        abort_unless($quiz->course, 403);

        $this->ensureTeacherCanModifyCourse($quiz->course);

        abort_unless($quiz->hasConsistentLesson(), 403);
    }


    /*
    |--------------------------------------------------------------------------
    | TEACHER - QUIZ BUILDER
    |--------------------------------------------------------------------------
    */

    public function teacherBuilder(
        Lesson $lesson
    ) {
        $lesson->load('course.category');
        abort_unless($lesson->course, 404);


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


        $quiz = Quiz::with(['questions' => fn ($questions) => $questions->orderBy('id')])
            ->where(
                'lesson_id',
                $lesson->id
            )
            ->orderBy('id')
            ->first();

        abort_if($quiz && (int) $quiz->course_id !== (int) $lesson->course_id, 403);

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


        $quizRevision = $this->quizRevision($quiz);

        return view(
            'Teacher.quiz-builder',
            compact(
                'lesson',
                'quiz',
                'quizCanBeModified',
                'quizRevision'
            )
        );
    }



    /*
    |--------------------------------------------------------------------------
    | TEACHER - SAVE QUIZ SETTINGS
    |--------------------------------------------------------------------------
    */

    public function teacherSaveQuiz(Request $request, Lesson $lesson)
    {
        $lesson->load('course');
        abort_unless($lesson->course, 404);
        $this->ensureTeacherCanModifyCourse($lesson->course);
        abort_unless($lesson->lesson_type === 'quiz', 422, 'This lesson is not a quiz lesson.');

        $questionRules = $this->questionRules();
        $questionFields = implode(',', array_keys($questionRules));
        $hasNewQuestion = $this->hasNewQuestionInput($request->input('new_question'));

        $rules = [
            'revision' => ['required', 'string', 'size:64'],
            'intent' => ['nullable', 'in:save,publish,unpublish'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'passing_score' => ['required', 'integer', 'min:1', 'max:100'],
            'time_limit_minutes' => ['nullable', 'integer', 'min:1', 'max:2147483647'],
            'questions' => ['sometimes', 'array'],
            'questions.*' => ['required', 'array:'.$questionFields],
            'new_question' => $hasNewQuestion ? ['required', 'array:'.$questionFields] : ['exclude'],
        ];

        $attributes = [];
        foreach ($questionRules as $field => $fieldRules) {
            $rules['questions.*.'.$field] = $fieldRules;
            $attributes['questions.*.'.$field] = str_replace('_', ' ', $field);

            if ($hasNewQuestion) {
                $rules['new_question.'.$field] = $fieldRules;
                $attributes['new_question.'.$field] = 'new question '.str_replace('_', ' ', $field);
            }
        }

        $validated = $request->validate($rules, [], $attributes);
        $intent = $validated['intent'] ?? 'save';

        DB::transaction(function () use ($lesson, $validated, $intent) {
            $course = Course::lockForUpdate()->findOrFail($lesson->course_id);
            $this->ensureTeacherCanModifyCourse($course);
            $currentLesson = Lesson::lockForUpdate()->findOrFail($lesson->id);
            abort_unless((int) $currentLesson->course_id === (int) $course->id, 409, 'This lesson moved while you were editing.');
            abort_unless($currentLesson->lesson_type === 'quiz', 422, 'This lesson is no longer a quiz lesson.');

            $quiz = Quiz::where('lesson_id', $currentLesson->id)->orderBy('id')->lockForUpdate()->first();
            abort_if($quiz && (int) $quiz->course_id !== (int) $course->id, 403);

            $quiz?->load(['questions' => fn ($questions) => $questions->orderBy('id')->lockForUpdate()]);
            $this->requireQuizRevision($quiz, $validated['revision']);

            $questions = $validated['questions'] ?? [];
            $existingQuestions = $quiz?->questions ?? collect();
            $expectedIds = $existingQuestions->pluck('id')->map(fn ($id) => (string) $id)->sort()->values()->all();
            $submittedIds = collect(array_keys($questions))->map(fn ($id) => (string) $id)->sort()->values()->all();
            if ($expectedIds !== $submittedIds) {
                throw ValidationException::withMessages(['quiz' => 'The complete question list could not be saved. Reopen the quiz and try again.']);
            }

            foreach ($questions as $id => $question) {
                $this->validateCorrectOption($question, 'questions.'.$id.'.correct_answer');
            }
            if (isset($validated['new_question'])) {
                $this->validateCorrectOption($validated['new_question'], 'new_question.correct_answer');
            }

            $published = match ($intent) {
                'publish' => true,
                'unpublish' => false,
                default => (bool) $quiz?->is_published,
            };
            if ($published && $existingQuestions->isEmpty() && ! isset($validated['new_question'])) {
                throw ValidationException::withMessages(['quiz' => 'Add at least one complete question before publishing the quiz.']);
            }

            $quiz ??= new Quiz();
            $quiz->fill([
                'course_id' => $course->id,
                'lesson_id' => $currentLesson->id,
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'passing_score' => $validated['passing_score'],
                'time_limit_minutes' => $validated['time_limit_minutes'] ?? null,
                'is_published' => $published,
            ]);
            $quiz->save();

            foreach ($existingQuestions as $question) {
                $question->update($this->questionValues($questions[$question->id]));
            }
            if (isset($validated['new_question'])) {
                $quiz->questions()->create($this->questionValues($validated['new_question']));
            }
        }, 3);

        $request->session()->flash('quiz_saved', true);
        if ($intent !== 'save') {
            $request->session()->flash('success', $intent === 'publish' ? 'Quiz published.' : 'Quiz returned to draft.');
        }

        if ($request->expectsJson()) {
            return response()->json(['saved' => true]);
        }

        return redirect()->route('teacher.quiz.builder', $lesson);
    }

    private function hasNewQuestionInput(mixed $input): bool
    {
        if (! is_array($input)) {
            return $input !== null;
        }

        foreach (['question', 'option_a', 'option_b', 'option_c', 'option_d', 'correct_answer'] as $field) {
            if (filled($input[$field] ?? null)) {
                return true;
            }
        }

        return array_key_exists('points', $input) && ! in_array($input['points'], [1, '1'], true);
    }

    private function quizRevision(?Quiz $quiz): string
    {
        return hash('sha256', json_encode([
            $quiz?->getAttributes(),
            $quiz?->questions->map->getAttributes()->all(),
        ], JSON_THROW_ON_ERROR));
    }

    private function requireQuizRevision(?Quiz $quiz, string $revision): void
    {
        if (! hash_equals($this->quizRevision($quiz), $revision)) {
            throw ValidationException::withMessages([
                'quiz' => 'The saved quiz has changed. Copy any edits you want to keep, then reopen the quiz before saving again.',
            ]);
        }
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
        // Gemini can take more than 30 seconds for 5 or 10 questions; the
        // service stops waiting after GeminiQuizService::REQUEST_BUDGET_SECONDS.
        set_time_limit(150);

        $quiz->load([
            'course',
            'lesson',
        ]);


        $this->ensureTeacherCanModifyQuiz($quiz);


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
            'revision' => ['required', 'string', 'size:64'],
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


        $quiz->load(['questions' => fn ($questions) => $questions->orderBy('id')]);
        $this->requireQuizRevision($quiz, $validated['revision']);

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
            AiAvailability::logUnavailable('quiz generation', null, [
                'limit' => 'PathWise per-minute AI generation limit',
                'retry_after_seconds' => RateLimiter::availableIn($minuteKey),
                'teacher_id' => auth()->id(),
            ]);

            return redirect()
                ->route(
                    'teacher.quiz.builder',
                    $quiz->lesson_id
                )
                ->with(
                    'error',
                    AiAvailability::MESSAGE
                )->withInput($request->except('_token'));
        }


        if (
            RateLimiter::tooManyAttempts(
                $dailyKey,
                20
            )
        ) {
            AiAvailability::logUnavailable('quiz generation', null, [
                'limit' => 'PathWise daily AI generation limit',
                'teacher_id' => auth()->id(),
            ]);

            return redirect()
                ->route(
                    'teacher.quiz.builder',
                    $quiz->lesson_id
                )
                ->with(
                    'error',
                    AiAvailability::MESSAGE
                )->withInput($request->except('_token'));
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


            $generatedCount = $this->saveGeneratedQuestions(
                $quiz,
                $generatedQuestions,
                $requestedCount,
                $validated['revision']
            );

            if ($generatedCount === 0) {
                return redirect()->route('teacher.quiz.builder', $quiz->lesson_id)
                    ->with('error', 'Gemini generated questions that already exist in this quiz. No duplicate questions were added.');
            }

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
                    ' Only new questions within the quiz limit were saved.';
            }


            if ($quiz->is_published) {
                $successMessage .= ' The quiz was returned to draft.';
            }

            $successMessage .= ' The questions are saved. Review and edit them before publishing the quiz.';


            return redirect()
                ->route(
                    'teacher.quiz.builder',
                    $quiz->lesson_id
                )
                ->with('quiz_saved', true)
                ->with('success', $successMessage);
        } catch (ValidationException|HttpExceptionInterface $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            // Gemini unreachable, over quota or rate-limited, or answering
            // with an HTTP error: the friendly message only; the details
            // go to the log.
            if (AiAvailability::isUnavailable($exception)) {
                AiAvailability::logUnavailable('quiz generation', $exception, [
                    'quiz_id' => $quiz->id,
                    'teacher_id' => auth()->id(),
                ]);

                return redirect()
                    ->route(
                        'teacher.quiz.builder',
                        $quiz->lesson_id
                    )
                    ->with(
                        'error',
                        AiAvailability::MESSAGE
                    )->withInput($request->except('_token'));
            }

            // Do not turn programming or persistence failures into AI messages.
            if (! $exception instanceof AiResponseException) throw $exception;
            AiAvailability::logUnavailable('quiz generation', $exception, ['quiz_id' => $quiz->id]);


            return redirect()
                ->route(
                    'teacher.quiz.builder',
                    $quiz->lesson_id
                )
                ->with(
                    'error',
                    'Gemini could not generate questions right now. Your quiz was not changed. Please try again later.'
                )->withInput($request->except('_token'));
        }
    }



    /*
    |--------------------------------------------------------------------------
    | TEACHER - ADD QUESTION
    |--------------------------------------------------------------------------
    */

    public function teacherStoreQuestion(Request $request, Quiz $quiz)
    {
        $this->ensureTeacherCanModifyQuiz($quiz);
        $validated = $request->validate($this->questionRules() + ['revision' => ['required', 'string', 'size:64']]);
        $this->validateCorrectOption($validated);

        DB::transaction(function () use ($quiz, $validated) {
            $current = $this->lockTeacherQuiz($quiz, $validated['revision']);
            unset($validated['revision']);
            $current->questions()->create($this->questionValues($validated));
        }, 3);

        return redirect()->route('teacher.quiz.builder', $quiz->lesson_id)
            ->with('success', 'Question added successfully.');
    }

    public function teacherUpdateQuestion(Request $request, Quiz $quiz, QuizQuestion $question)
    {
        $this->ensureTeacherCanModifyQuiz($quiz);
        abort_unless((int) $question->quiz_id === (int) $quiz->id, 404);
        $validated = $request->validate($this->questionRules() + ['revision' => ['required', 'string', 'size:64']]);
        $this->validateCorrectOption($validated);

        DB::transaction(function () use ($quiz, $question, $validated) {
            $current = $this->lockTeacherQuiz($quiz, $validated['revision']);
            $target = $current->questions->find($question->id);
            abort_unless($target, 404);
            unset($validated['revision']);
            $target->update($this->questionValues($validated));
        }, 3);

        return redirect()->route('teacher.quiz.builder', $quiz->lesson_id)
            ->with('success', 'Question updated.');
    }

    public function teacherDeleteQuestion(Request $request, Quiz $quiz, QuizQuestion $question)
    {
        $this->ensureTeacherCanModifyQuiz($quiz);
        abort_unless((int) $question->quiz_id === (int) $quiz->id, 404);
        $validated = $request->validate(['revision' => ['required', 'string', 'size:64']]);

        DB::transaction(function () use ($quiz, $question, $validated) {
            $current = $this->lockTeacherQuiz($quiz, $validated['revision']);
            $target = $current->questions->find($question->id);
            abort_unless($target, 404);
            if ($current->is_published && $current->questions->count() === 1) {
                throw ValidationException::withMessages([
                    'quiz' => 'Return the quiz to draft or add another question before deleting its last question.',
                ]);
            }
            $target->delete();
        }, 3);

        return redirect()->route('teacher.quiz.builder', $quiz->lesson_id)
            ->with('success', 'Question deleted.');
    }

    private function lockTeacherQuiz(Quiz $quiz, string $revision): Quiz
    {
        $course = Course::lockForUpdate()->findOrFail($quiz->course_id);
        $this->ensureTeacherCanModifyCourse($course);
        $lesson = $quiz->lesson_id ? Lesson::lockForUpdate()->find($quiz->lesson_id) : null;
        abort_unless(
            $lesson && $lesson->lesson_type === 'quiz' && (int) $lesson->course_id === (int) $course->id,
            422,
            'This quiz is no longer linked to a valid quiz lesson.'
        );

        $current = Quiz::lockForUpdate()->findOrFail($quiz->id);
        abort_unless((int) $current->course_id === (int) $course->id
            && (int) $current->lesson_id === (int) $lesson->id, 409, 'This quiz moved while you were editing.');

        $current->load(['questions' => fn ($questions) => $questions->orderBy('id')->lockForUpdate()]);
        $this->requireQuizRevision($current, $revision);

        return $current;
    }

    private function saveGeneratedQuestions(Quiz $quiz, array $generated, int $requestedCount, string $revision): int
    {
        return DB::transaction(function () use ($quiz, $generated, $requestedCount, $revision) {
            $current = $this->lockTeacherQuiz($quiz, $revision);

            $limit = min($requestedCount, 50 - $current->questions->count());
            if ($limit <= 0) {
                throw ValidationException::withMessages(['question_count' => 'This quiz already has 50 or more questions.']);
            }

            $seen = $current->questions->mapWithKeys(fn (QuizQuestion $question) => [
                $this->questionKey($question->question) => true,
            ])->all();

            $saved = 0;
            foreach ($generated as $question) {
                $key = $this->questionKey($question['question']);
                if (isset($seen[$key])) {
                    continue;
                }

                $current->questions()->create($question);
                $seen[$key] = true;
                if (++$saved === $limit) {
                    break;
                }
            }

            // New questions must not become available as a side effect of
            // AI generation. The teacher can publish the reviewed quiz from
            // the builder after generation completes.
            if ($saved > 0 && $current->is_published) {
                $current->is_published = false;
                $current->save();
            }

            return $saved;
        }, 3);
    }

    private function questionKey(string $question): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/u', ' ', $question) ?? $question));
    }

    private function questionRules(): array
    {
        return [
            'question' => ['required', 'string'],
            'option_a' => ['required', 'string', 'max:255'],
            'option_b' => ['required', 'string', 'max:255'],
            'option_c' => ['nullable', 'string', 'max:255'],
            'option_d' => ['nullable', 'string', 'max:255'],
            'correct_answer' => ['required', 'in:A,B,C,D'],
            'points' => ['required', 'integer', 'min:1', 'max:100'],
        ];
    }

    private function questionValues(array $question): array
    {
        return $question + ['option_c' => null, 'option_d' => null];
    }

    private function validateCorrectOption(array $validated, string $errorKey = 'correct_answer'): void
    {
        $answer = $validated['correct_answer'];
        $field = 'option_'.strtolower($answer);

        if (blank($validated[$field] ?? null)) {
            throw ValidationException::withMessages([$errorKey => 'Fill in option '.$answer.' for the selected correct answer.']);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | STUDENT - QUIZ ACCESS GUARD
    |--------------------------------------------------------------------------
    */

    private function loadStudentQuiz(Quiz $quiz, array $relations): Quiz
    {
        return DB::transaction(function () use ($quiz, $relations) {
            // Read the current publication state and questions together with
            // the builder's writes serialized on the same quiz row.
            $current = Quiz::sharedLock()->findOrFail($quiz->id);
            $current->load($relations);
            $this->ensureStudentCanAccessQuiz($current);

            return $current;
        }, 3);
    }

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
            abort_unless($quiz->lesson->is_published, 403, 'This quiz lesson is not currently available to students.');
        }

        // Cancelled enrollments no longer give access (Enrollment::ACCESS_STATUSES).
        $isEnrolled = Enrollment::where(
                'student_id',
                auth()->id()
            )
            ->where(
                'course_id',
                $course->id
            )
            ->withAccess()
            ->exists();

        if (!$isEnrolled) {
            abort(
                403,
                'You must have an active enrollment in this course to access this quiz.'
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
        $quiz = $this->loadStudentQuiz($quiz, ['course', 'lesson', 'questions']);


        /*
         * Quiz time limit (Manuscript Table 13: time_limit_minutes).
         * The start time is kept on the server, so refreshing the page
         * does not reset the timer.
         */
        $deadline = $this->startOrResumeTimedAttempt(
            $quiz
        );

        $secondsRemaining = $deadline !== null
            ? max(0, $deadline - now()->getTimestamp())
            : null;


        return view(
            'Student.take-quiz',
            compact('quiz', 'secondsRemaining')
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
        $quiz = $this->loadStudentQuiz($quiz, ['questions', 'lesson', 'course.category', 'course.lessons']);


        if (
            $redirect = $this->rejectIfTimeLimitExceeded(
                $quiz
            )
        ) {
            return $redirect;
        }


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


        // This timed attempt is finished; the next visit starts a new one.
        $this->forgetTimedAttempt(
            $quiz
        );


        app(
            RecommendationService::class
        )->generate(
            (int) auth()->id(),
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



    /*
    |--------------------------------------------------------------------------
    | QUIZ TIME LIMIT HELPERS
    |--------------------------------------------------------------------------
    |
    | The attempt start time is stored in the student's session when the quiz
    | page is opened. On submit, the server checks the time itself, so the
    | limit cannot be bypassed by changing the countdown in the browser.
    | A short grace period covers the automatic submission's network delay.
    */

    private const QUIZ_TIME_GRACE_SECONDS = 30;

    private function timedAttemptKey(
        Quiz $quiz
    ): string {
        return 'quiz_attempts.' . $quiz->id . '.started_at';
    }

    /**
     * Returns the attempt deadline as a Unix timestamp, or null when the
     * quiz has no time limit.
     */
    private function startOrResumeTimedAttempt(
        Quiz $quiz
    ): ?int {
        $limitMinutes = (int) $quiz->time_limit_minutes;

        $key = $this->timedAttemptKey($quiz);

        if ($limitMinutes <= 0) {
            session()->forget($key);

            return null;
        }

        $now = now()->getTimestamp();

        $startedAt = session($key);

        $attemptIsUsable =
            is_int($startedAt)
            && $now <= $startedAt
                + ($limitMinutes * 60)
                + self::QUIZ_TIME_GRACE_SECONDS;

        if (! $attemptIsUsable) {
            $startedAt = $now;

            session()->put($key, $startedAt);
        }

        return $startedAt + ($limitMinutes * 60);
    }

    /**
     * Returns a redirect when a timed attempt is missing or too late,
     * otherwise null (the submission may be graded).
     */
    private function rejectIfTimeLimitExceeded(
        Quiz $quiz
    ) {
        $limitMinutes = (int) $quiz->time_limit_minutes;

        if ($limitMinutes <= 0) {
            return null;
        }

        $key = $this->timedAttemptKey($quiz);

        $startedAt = session($key);

        if (! is_int($startedAt)) {
            return redirect()
                ->route('student.quiz.take', $quiz)
                ->with(
                    'error',
                    'Your quiz session was not found or has expired. Please start the quiz again.'
                );
        }

        $deadline = $startedAt
            + ($limitMinutes * 60)
            + self::QUIZ_TIME_GRACE_SECONDS;

        if (now()->timestamp > $deadline) {
            session()->forget($key);

            return redirect()
                ->route('student.quiz.take', $quiz)
                ->with(
                    'error',
                    'The time limit of ' . $limitMinutes
                    . ' minute(s) was exceeded, so this attempt was not recorded. You may start a new attempt.'
                );
        }

        return null;
    }

    private function forgetTimedAttempt(
        Quiz $quiz
    ): void {
        session()->forget(
            $this->timedAttemptKey($quiz)
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
            // Only an active enrollment is updated: failing a retake must
            // not downgrade a completed course, and a cancelled enrollment
            // must stay cancelled.
            ->where(
                'status',
                'active'
            )
            ->update([
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


        $certificate = Certificate::firstOrCreate(
            [
                'student_id' =>
                    $studentId,

                'course_id' =>
                    $course->id,
            ],
            [
                'certificate_number' =>
                    Certificate::generateNumber(),

                'issued_date' =>
                    now()->toDateString(),

                'status' =>
                    'issued',
            ]
        );

        // Only a newly issued certificate notifies the student.
        if ($certificate->wasRecentlyCreated) {
            $certificate->notifyStudent();
        }
    }
}