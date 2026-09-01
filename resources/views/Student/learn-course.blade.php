<x-layouts::app :title="$course->title">

@php
    $studentId = auth()->id();

    /*
    |--------------------------------------------------------------------------
    | ENROLLMENT
    |--------------------------------------------------------------------------
    */

    $enrollment = \App\Models\Enrollment::where('student_id', $studentId)
        ->where('course_id', $course->id)
        ->first();


    /*
    |--------------------------------------------------------------------------
    | LESSON PROGRESS
    |--------------------------------------------------------------------------
    */

    $lessonProgressRecords = \App\Models\LessonProgress::where('student_id', $studentId)
        ->whereIn('lesson_id', $lessons->pluck('id'))
        ->get()
        ->keyBy('lesson_id');


    /*
    |--------------------------------------------------------------------------
    | COURSE QUIZZES
    |--------------------------------------------------------------------------
    */

    $courseQuizzes = \App\Models\Quiz::where('course_id', $course->id)
        ->where('is_published', true)
        ->with('questions')
        ->get();

    /*
     * Quizzes linked directly to quiz lessons.
     */
    $lessonQuizzes = $courseQuizzes
        ->whereNotNull('lesson_id')
        ->keyBy('lesson_id');

    /*
     * Optional standalone/final quiz.
     */
    $finalQuiz = $courseQuizzes
        ->whereNull('lesson_id')
        ->first();


    /*
    |--------------------------------------------------------------------------
    | QUIZ RESULTS
    |--------------------------------------------------------------------------
    */

    $quizResults = \App\Models\QuizResult::where('student_id', $studentId)
        ->whereIn('quiz_id', $courseQuizzes->pluck('id'))
        ->orderByDesc('completed_at')
        ->get();

    $latestQuizResults = $quizResults
        ->groupBy('quiz_id')
        ->map(fn ($items) => $items->first());


    /*
    |--------------------------------------------------------------------------
    | LESSON COMPLETION
    |--------------------------------------------------------------------------
    */

    $completedLessonIds = collect();

    foreach ($lessons as $lesson) {

        $progressRecord = $lessonProgressRecords->get($lesson->id);

        if (
            $progressRecord &&
            strtolower($progressRecord->status ?? '') === 'completed'
        ) {
            $completedLessonIds->push($lesson->id);
            continue;
        }

        /*
         * A quiz lesson is considered complete in the UI
         * once the student passes its linked quiz.
         */
        if ($lesson->lesson_type === 'quiz') {

            $linkedQuiz = $lessonQuizzes->get($lesson->id);

            if ($linkedQuiz) {

                $result = $latestQuizResults->get($linkedQuiz->id);

                if (
                    $result &&
                    strtolower($result->remarks ?? '') === 'passed'
                ) {
                    $completedLessonIds->push($lesson->id);
                }
            }
        }
    }

    $completedLessonIds = $completedLessonIds->unique();

    $totalLessons = $lessons->count();

    $completedLessons = $completedLessonIds->count();

    $lessonProgress = $totalLessons > 0
        ? round(($completedLessons / $totalLessons) * 100)
        : 0;

    $lessonProgress = min(
        max($lessonProgress, 0),
        100
    );

    $allLessonsCompleted =
        $totalLessons > 0 &&
        $completedLessons >= $totalLessons;


    /*
    |--------------------------------------------------------------------------
    | FINAL QUIZ
    |--------------------------------------------------------------------------
    */

    $finalQuizResult = $finalQuiz
        ? $latestQuizResults->get($finalQuiz->id)
        : null;

    $passedFinalQuiz =
        !$finalQuiz ||
        (
            $finalQuizResult &&
            strtolower($finalQuizResult->remarks ?? '') === 'passed'
        );

    $failedFinalQuiz =
        $finalQuizResult &&
        strtolower($finalQuizResult->remarks ?? '') === 'failed';


    /*
    |--------------------------------------------------------------------------
    | COURSE COMPLETION
    |--------------------------------------------------------------------------
    */

    $courseCompleted =
        $allLessonsCompleted &&
        $passedFinalQuiz;


    /*
    |--------------------------------------------------------------------------
    | STATS
    |--------------------------------------------------------------------------
    */

    $inProgressLessons = $lessons
        ->filter(function ($lesson) use (
            $lessonProgressRecords,
            $completedLessonIds
        ) {

            if ($completedLessonIds->contains($lesson->id)) {
                return false;
            }

            $record = $lessonProgressRecords->get($lesson->id);

            return $record &&
                strtolower($record->status ?? '') === 'in_progress';
        })
        ->count();

    $notStartedLessons = max(
        $totalLessons -
        $completedLessons -
        $inProgressLessons,
        0
    );

    $totalMinutes = $lessons->sum(
        fn ($lesson) => (int) ($lesson->duration_minutes ?? 0)
    );


    /*
    |--------------------------------------------------------------------------
    | COURSE INFORMATION
    |--------------------------------------------------------------------------
    */

    $thumbnail = $course->thumbnail ?? null;

    $thumbnailUrl = null;

    if ($thumbnail) {

        if (
            \Illuminate\Support\Str::startsWith(
                $thumbnail,
                ['http://', 'https://']
            )
        ) {
            $thumbnailUrl = $thumbnail;
        } else {
            $thumbnailUrl = asset(
                'storage/' . ltrim($thumbnail, '/')
            );
        }
    }

    $teacherName =
        $course->teacher?->name
        ?? 'PathWise Instructor';


    /*
    |--------------------------------------------------------------------------
    | RECOMMENDATION
    |--------------------------------------------------------------------------
    */

    $latestRecommendation = \App\Models\AIRecommendation::with('course.category')
        ->where('student_id', $studentId)
        ->latest()
        ->first();


    /*
    |--------------------------------------------------------------------------
    | LESSON TYPE LABELS
    |--------------------------------------------------------------------------
    */

    $typeLabels = [
        'video' => 'Video',
        'text' => 'Reading',
        'document' => 'Document',
        'quiz' => 'Quiz',
    ];
@endphp


<style>
    .pw-card {
        background: #ffffff;
        border: 1px solid #e7e9ef;
        border-radius: 20px;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.035);
    }

    .pw-soft-card {
        background: #f8fafc;
        border: 1px solid #eef0f4;
        border-radius: 14px;
    }

    .pw-lesson {
        transition:
            background-color 160ms ease,
            border-color 160ms ease;
    }
</style>


<div class="min-h-screen bg-[#f8f9fc]">

    <main class="px-5 py-7 sm:px-6 lg:px-8 lg:py-9">

        <div class="mx-auto max-w-[1500px]">


            {{-- =====================================================
                BREADCRUMB
            ====================================================== --}}

            <div class="mb-5 flex flex-wrap items-center gap-2 text-xs text-slate-400">

                <a
                    href="{{ route('student.my-courses') }}"
                    class="font-medium transition hover:text-violet-600"
                >
                    My Courses
                </a>

                <span>›</span>

                <span>
                    {{ $course->title }}
                </span>

                <span>›</span>

                <span class="font-semibold text-slate-600">
                    Learn
                </span>

            </div>



            {{-- =====================================================
                PAGE HEADER
            ====================================================== --}}

            <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">

                <div>

                    <p class="text-xs font-bold uppercase tracking-[.12em] text-violet-600">
                        Course Learning
                    </p>

                    <h1 class="mt-2 max-w-4xl text-3xl font-bold tracking-tight text-slate-950">
                        {{ $course->title }}
                    </h1>

                    <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500">
                        Continue your lessons, complete assessments,
                        and monitor your learning progress.
                    </p>

                </div>


                <div class="flex flex-wrap items-center gap-2">

                    <a
                        href="{{ route('student.course.show', $course) }}"
                        class="inline-flex h-11 items-center justify-center
                               rounded-xl border border-slate-200
                               bg-white px-4 text-sm font-semibold
                               text-slate-600 transition
                               hover:border-violet-200
                               hover:text-violet-700"
                    >
                        Course Details
                    </a>


                    <a
                        href="{{ route('student.my-courses') }}"
                        class="inline-flex h-11 items-center justify-center gap-2
                               rounded-xl bg-violet-600 px-4
                               text-sm font-semibold text-white
                               transition hover:bg-violet-700"
                    >
                        ← My Courses
                    </a>

                </div>

            </div>



            {{-- =====================================================
                COURSE HERO
            ====================================================== --}}

            <section class="pw-card mt-7 overflow-hidden">

                <div class="grid grid-cols-1 lg:grid-cols-[290px_minmax(0,1fr)]">


                    {{-- IMAGE --}}
                    <div class="relative min-h-[220px] overflow-hidden">

                        @if($thumbnailUrl)

                            <img
                                src="{{ $thumbnailUrl }}"
                                alt="{{ $course->title }}"
                                class="h-full w-full object-cover"
                            >

                            <div
                                class="absolute inset-0
                                       bg-linear-to-t
                                       from-slate-950/45
                                       to-transparent"
                            ></div>

                        @else

                            <div
                                class="flex h-full min-h-[220px] w-full
                                       items-center justify-center
                                       bg-linear-to-br
                                       from-violet-500
                                       via-indigo-500
                                       to-blue-500"
                            >

                                <svg
                                    class="h-14 w-14 text-white/80"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.4"
                                >
                                    <path d="M4 19.5A2.5 2.5 0 016.5 17H20"></path>
                                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"></path>
                                </svg>

                            </div>

                        @endif

                    </div>



                    {{-- HERO CONTENT --}}
                    <div class="flex flex-col justify-center p-6 sm:p-7">

                        <div class="flex flex-wrap items-center gap-2">

                            @if($course->category)

                                <span
                                    class="rounded-full bg-violet-50
                                           px-2.5 py-1
                                           text-[10px] font-bold
                                           uppercase tracking-wide
                                           text-violet-700"
                                >
                                    {{ $course->category->name }}
                                </span>

                            @endif


                            <span
                                class="rounded-full bg-slate-100
                                       px-2.5 py-1
                                       text-[10px] font-semibold
                                       text-slate-600"
                            >
                                {{
                                    ucfirst(
                                        $course->difficulty_level
                                        ?? 'beginner'
                                    )
                                }}
                            </span>


                            @if($courseCompleted)

                                <span
                                    class="inline-flex items-center gap-1.5
                                           rounded-full bg-emerald-50
                                           px-2.5 py-1
                                           text-[10px] font-bold
                                           text-emerald-700"
                                >
                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                    Completed
                                </span>

                            @else

                                <span
                                    class="inline-flex items-center gap-1.5
                                           rounded-full bg-blue-50
                                           px-2.5 py-1
                                           text-[10px] font-bold
                                           text-blue-700"
                                >
                                    <span class="h-1.5 w-1.5 rounded-full bg-blue-500"></span>
                                    In Progress
                                </span>

                            @endif

                        </div>


                        <h2 class="mt-3 text-2xl font-bold tracking-tight text-slate-900">
                            {{ $course->title }}
                        </h2>


                        <p class="mt-2 text-sm text-slate-500">
                            Instructor:
                            <span class="font-semibold text-slate-700">
                                {{ $teacherName }}
                            </span>
                        </p>



                        {{-- PROGRESS --}}
                        <div class="mt-6 max-w-3xl">

                            <div class="flex items-center justify-between">

                                <div>

                                    <p class="text-xs font-semibold text-slate-500">
                                        Course Progress
                                    </p>

                                    <p class="mt-1 text-2xl font-bold text-slate-900">
                                        {{ $lessonProgress }}%
                                    </p>

                                </div>


                                <p class="text-xs font-semibold text-slate-500">
                                    {{ $completedLessons }}
                                    of
                                    {{ $totalLessons }}
                                    lessons
                                </p>

                            </div>


                            <div class="mt-3 h-2.5 overflow-hidden rounded-full bg-slate-100">

                                <div
                                    class="h-full rounded-full
                                           {{ $courseCompleted
                                               ? 'bg-emerald-500'
                                               : 'bg-violet-600'
                                           }}"
                                    style="width: {{ $lessonProgress }}%;"
                                ></div>

                            </div>

                        </div>

                    </div>

                </div>

            </section>



            {{-- =====================================================
                STATS
            ====================================================== --}}

            <section class="mt-5 grid grid-cols-2 gap-4 xl:grid-cols-4">


                {{-- TOTAL LESSONS --}}
                <div class="pw-card p-5">

                    <p class="text-xs font-semibold text-slate-500">
                        Total Lessons
                    </p>

                    <div class="mt-2 flex items-end justify-between">

                        <p class="text-3xl font-bold text-slate-900">
                            {{ $totalLessons }}
                        </p>


                        <div
                            class="flex h-9 w-9 items-center justify-center
                                   rounded-xl bg-violet-50 text-violet-600"
                        >
                            <svg
                                class="h-4 w-4"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path d="M4 19.5A2.5 2.5 0 016.5 17H20"></path>
                                <path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"></path>
                            </svg>
                        </div>

                    </div>

                </div>



                {{-- COMPLETED --}}
                <div class="pw-card p-5">

                    <p class="text-xs font-semibold text-slate-500">
                        Completed
                    </p>

                    <div class="mt-2 flex items-end justify-between">

                        <p class="text-3xl font-bold text-emerald-600">
                            {{ $completedLessons }}
                        </p>


                        <div
                            class="flex h-9 w-9 items-center justify-center
                                   rounded-xl bg-emerald-50
                                   font-bold text-emerald-600"
                        >
                            ✓
                        </div>

                    </div>

                </div>



                {{-- IN PROGRESS --}}
                <div class="pw-card p-5">

                    <p class="text-xs font-semibold text-slate-500">
                        In Progress
                    </p>

                    <div class="mt-2 flex items-end justify-between">

                        <p class="text-3xl font-bold text-blue-600">
                            {{ $inProgressLessons }}
                        </p>


                        <div
                            class="flex h-9 w-9 items-center justify-center
                                   rounded-xl bg-blue-50 text-blue-600"
                        >
                            <svg
                                class="h-4 w-4"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <circle cx="12" cy="12" r="9"></circle>
                                <path d="M12 7v5l3 2"></path>
                            </svg>
                        </div>

                    </div>

                </div>



                {{-- TIME --}}
                <div class="pw-card p-5">

                    <p class="text-xs font-semibold text-slate-500">
                        Course Duration
                    </p>

                    <div class="mt-2 flex items-end justify-between">

                        <p class="text-3xl font-bold text-orange-500">
                            {{ $totalMinutes }}

                            <span class="text-sm font-semibold text-slate-400">
                                min
                            </span>
                        </p>


                        <div
                            class="flex h-9 w-9 items-center justify-center
                                   rounded-xl bg-orange-50 text-orange-500"
                        >
                            <svg
                                class="h-4 w-4"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <circle cx="12" cy="12" r="9"></circle>
                                <path d="M12 7v5l3 2"></path>
                            </svg>
                        </div>

                    </div>

                </div>

            </section>



            {{-- =====================================================
                MAIN LEARNING AREA
            ====================================================== --}}

            <div
                class="mt-6 grid grid-cols-1 gap-6
                       xl:grid-cols-[minmax(0,1fr)_350px]"
            >


                {{-- =================================================
                    LESSONS
                ================================================== --}}

                <section class="pw-card overflow-hidden">


                    {{-- LESSON HEADER --}}
                    <div
                        class="flex flex-col gap-4
                               border-b border-slate-100
                               p-5 sm:flex-row
                               sm:items-center
                               sm:justify-between
                               sm:p-6"
                    >

                        <div>

                            <h2 class="text-lg font-bold text-slate-900">
                                Course Lessons
                            </h2>

                            <p class="mt-1 text-xs text-slate-500">
                                Work through each lesson and assessment.
                            </p>

                        </div>


                        <span
                            class="self-start rounded-full
                                   bg-violet-50 px-3 py-1.5
                                   text-xs font-bold text-violet-700"
                        >
                            {{ $lessonProgress }}% complete
                        </span>

                    </div>



                    @if($lessons->isNotEmpty())

                        <div class="divide-y divide-slate-100">

                            @foreach($lessons as $lesson)

                                @php
                                    $progressRecord =
                                        $lessonProgressRecords->get($lesson->id);

                                    $linkedQuiz =
                                        $lessonQuizzes->get($lesson->id);

                                    $linkedQuizResult =
                                        $linkedQuiz
                                            ? $latestQuizResults->get($linkedQuiz->id)
                                            : null;

                                    $isCompleted =
                                        $completedLessonIds->contains($lesson->id);

                                    $status =
                                        $isCompleted
                                            ? 'completed'
                                            : (
                                                $progressRecord &&
                                                strtolower($progressRecord->status ?? '') === 'in_progress'
                                                    ? 'in_progress'
                                                    : 'not_started'
                                            );

                                    $lessonType =
                                        strtolower(
                                            $lesson->lesson_type
                                            ?? 'text'
                                        );

                                    $typeLabel =
                                        $typeLabels[$lessonType]
                                        ?? ucfirst($lessonType);

                                    $lessonUrl =
                                        $lessonType === 'quiz' &&
                                        $linkedQuiz
                                            ? route(
                                                'student.quiz.take',
                                                $linkedQuiz
                                            )
                                            : route(
                                                'student.lesson.view',
                                                $lesson
                                            );
                                @endphp


                                <article
                                    class="pw-lesson p-5
                                           hover:bg-slate-50/70
                                           sm:p-6"
                                >

                                    <div
                                        class="flex flex-col gap-4
                                               sm:flex-row
                                               sm:items-center
                                               sm:justify-between"
                                    >


                                        {{-- LEFT --}}
                                        <div class="flex min-w-0 items-start gap-4">


                                            {{-- ICON --}}
                                            <div
                                                class="flex h-11 w-11 shrink-0
                                                       items-center justify-center
                                                       rounded-xl
                                                       {{
                                                           $isCompleted
                                                           ? 'bg-emerald-50 text-emerald-600'
                                                           : (
                                                               $lessonType === 'quiz'
                                                               ? 'bg-orange-50 text-orange-500'
                                                               : (
                                                                   $lessonType === 'video'
                                                                   ? 'bg-violet-50 text-violet-600'
                                                                   : (
                                                                       $lessonType === 'document'
                                                                       ? 'bg-blue-50 text-blue-600'
                                                                       : 'bg-slate-100 text-slate-500'
                                                                   )
                                                               )
                                                           )
                                                       }}"
                                            >

                                                @if($isCompleted)

                                                    ✓

                                                @elseif($lessonType === 'video')

                                                    <svg
                                                        class="h-4 w-4"
                                                        viewBox="0 0 24 24"
                                                        fill="none"
                                                        stroke="currentColor"
                                                        stroke-width="2"
                                                    >
                                                        <circle cx="12" cy="12" r="9"></circle>
                                                        <path d="m10 8 6 4-6 4z"></path>
                                                    </svg>

                                                @elseif($lessonType === 'quiz')

                                                    <span class="font-bold">
                                                        ?
                                                    </span>

                                                @elseif($lessonType === 'document')

                                                    <svg
                                                        class="h-4 w-4"
                                                        viewBox="0 0 24 24"
                                                        fill="none"
                                                        stroke="currentColor"
                                                        stroke-width="2"
                                                    >
                                                        <path d="M6 2h9l5 5v15H6z"></path>
                                                        <path d="M14 2v6h6"></path>
                                                    </svg>

                                                @else

                                                    <svg
                                                        class="h-4 w-4"
                                                        viewBox="0 0 24 24"
                                                        fill="none"
                                                        stroke="currentColor"
                                                        stroke-width="2"
                                                    >
                                                        <path d="M4 19.5A2.5 2.5 0 016.5 17H20"></path>
                                                        <path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"></path>
                                                    </svg>

                                                @endif

                                            </div>



                                            {{-- INFO --}}
                                            <div class="min-w-0">

                                                <div class="flex flex-wrap items-center gap-2">

                                                    <p
                                                        class="text-[10px]
                                                               font-bold uppercase
                                                               tracking-[.08em]
                                                               text-slate-400"
                                                    >
                                                        Lesson
                                                        {{ $lesson->lesson_order }}
                                                    </p>


                                                    @if($lesson->is_preview)

                                                        <span
                                                            class="rounded-full
                                                                   bg-violet-50
                                                                   px-2 py-0.5
                                                                   text-[9px]
                                                                   font-bold uppercase
                                                                   text-violet-600"
                                                        >
                                                            Preview
                                                        </span>

                                                    @endif

                                                </div>


                                                <h3
                                                    class="mt-1
                                                           text-sm font-bold
                                                           text-slate-800"
                                                >
                                                    {{ $lesson->title }}
                                                </h3>


                                                <div
                                                    class="mt-2
                                                           flex flex-wrap
                                                           items-center gap-2
                                                           text-[11px]
                                                           text-slate-400"
                                                >

                                                    <span>
                                                        {{ $typeLabel }}
                                                    </span>


                                                    @if($lesson->duration_minutes)

                                                        <span>
                                                            •
                                                        </span>

                                                        <span>
                                                            {{ $lesson->duration_minutes }}
                                                            min
                                                        </span>

                                                    @endif


                                                    @if($lessonType === 'quiz' && $linkedQuiz)

                                                        <span>
                                                            •
                                                        </span>

                                                        <span>
                                                            {{ $linkedQuiz->questions->count() }}
                                                            {{
                                                                \Illuminate\Support\Str::plural(
                                                                    'question',
                                                                    $linkedQuiz->questions->count()
                                                                )
                                                            }}
                                                        </span>

                                                    @endif

                                                </div>



                                                {{-- QUIZ SCORE --}}
                                                @if($lessonType === 'quiz' && $linkedQuizResult)

                                                    <p
                                                        class="mt-2 text-[11px]
                                                               font-semibold
                                                               {{
                                                                   strtolower($linkedQuizResult->remarks ?? '') === 'passed'
                                                                   ? 'text-emerald-600'
                                                                   : 'text-red-500'
                                                               }}"
                                                    >
                                                        Latest score:
                                                        {{
                                                            number_format(
                                                                (float) $linkedQuizResult->percentage,
                                                                1
                                                            )
                                                        }}%
                                                    </p>

                                                @endif

                                            </div>

                                        </div>



                                        {{-- RIGHT --}}
                                        <div
                                            class="flex items-center
                                                   justify-between gap-3
                                                   pl-15 sm:pl-0"
                                        >


                                            {{-- STATUS --}}
                                            @if($status === 'completed')

                                                <span
                                                    class="inline-flex items-center
                                                           gap-1.5 rounded-full
                                                           bg-emerald-50
                                                           px-3 py-1.5
                                                           text-[10px] font-bold
                                                           text-emerald-700"
                                                >
                                                    <span
                                                        class="h-1.5 w-1.5
                                                               rounded-full
                                                               bg-emerald-500"
                                                    ></span>

                                                    Completed
                                                </span>

                                            @elseif($status === 'in_progress')

                                                <span
                                                    class="inline-flex items-center
                                                           gap-1.5 rounded-full
                                                           bg-blue-50
                                                           px-3 py-1.5
                                                           text-[10px] font-bold
                                                           text-blue-700"
                                                >
                                                    <span
                                                        class="h-1.5 w-1.5
                                                               rounded-full
                                                               bg-blue-500"
                                                    ></span>

                                                    In Progress
                                                </span>

                                            @else

                                                <span
                                                    class="inline-flex items-center
                                                           gap-1.5 rounded-full
                                                           bg-slate-100
                                                           px-3 py-1.5
                                                           text-[10px] font-bold
                                                           text-slate-500"
                                                >
                                                    <span
                                                        class="h-1.5 w-1.5
                                                               rounded-full
                                                               bg-slate-400"
                                                    ></span>

                                                    Not Started
                                                </span>

                                            @endif



                                            {{-- BUTTON --}}
                                            @if($lessonType === 'quiz' && !$linkedQuiz)

                                                <span
                                                    class="inline-flex h-9
                                                           items-center justify-center
                                                           rounded-lg bg-slate-100
                                                           px-3 text-xs font-semibold
                                                           text-slate-400"
                                                >
                                                    Quiz unavailable
                                                </span>

                                            @else

                                                <a
                                                    href="{{ $lessonUrl }}"
                                                    class="inline-flex h-9
                                                           items-center justify-center
                                                           gap-1.5 rounded-lg
                                                           bg-violet-600
                                                           px-3
                                                           text-xs font-semibold
                                                           text-white transition
                                                           hover:bg-violet-700"
                                                >

                                                    @if($lessonType === 'quiz')

                                                        {{
                                                            $linkedQuizResult
                                                            ? 'Retake Quiz'
                                                            : 'Take Quiz'
                                                        }}

                                                    @elseif($isCompleted)

                                                        Review

                                                    @elseif($status === 'in_progress')

                                                        Continue

                                                    @else

                                                        Start

                                                    @endif


                                                    <svg
                                                        class="h-3.5 w-3.5"
                                                        viewBox="0 0 24 24"
                                                        fill="none"
                                                        stroke="currentColor"
                                                        stroke-width="2"
                                                    >
                                                        <path d="m9 18 6-6-6-6"></path>
                                                    </svg>

                                                </a>

                                            @endif

                                        </div>

                                    </div>

                                </article>

                            @endforeach

                        </div>


                    @else


                        <div class="px-6 py-16 text-center">

                            <div
                                class="mx-auto flex h-14 w-14
                                       items-center justify-center
                                       rounded-2xl bg-violet-50
                                       text-violet-600"
                            >
                                <svg
                                    class="h-6 w-6"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path d="M4 19.5A2.5 2.5 0 016.5 17H20"></path>
                                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"></path>
                                </svg>
                            </div>


                            <h3 class="mt-4 text-sm font-bold text-slate-800">
                                No lessons available yet
                            </h3>


                            <p class="mt-1 text-xs text-slate-400">
                                Course lessons will appear here once published.
                            </p>

                        </div>

                    @endif

                </section>



                {{-- =================================================
                    RIGHT SIDEBAR
                ================================================== --}}

                <aside class="space-y-5">


                    {{-- LEARNING PROGRESS --}}
                    <section class="pw-card p-5">

                        <div class="flex items-start justify-between gap-3">

                            <div>

                                <h2 class="text-sm font-bold text-slate-900">
                                    Learning Progress
                                </h2>

                                <p class="mt-1 text-xs text-slate-500">
                                    Your course activity.
                                </p>

                            </div>


                            <div
                                class="flex h-9 w-9
                                       items-center justify-center
                                       rounded-xl bg-violet-50
                                       text-violet-600"
                            >
                                <svg
                                    class="h-4 w-4"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path d="M3 3v18h18"></path>
                                    <path d="m7 16 4-5 4 3 5-7"></path>
                                </svg>
                            </div>

                        </div>



                        <div class="mt-5 rounded-2xl bg-violet-50/70 p-5">

                            <p
                                class="text-[10px] font-bold
                                       uppercase tracking-[.08em]
                                       text-violet-500"
                            >
                                Overall Progress
                            </p>


                            <div class="mt-2 flex items-end justify-between">

                                <p class="text-3xl font-bold text-violet-900">
                                    {{ $lessonProgress }}%
                                </p>


                                <span class="text-xs font-semibold text-violet-600">
                                    {{ $completedLessons }}/{{ $totalLessons }}
                                </span>

                            </div>


                            <div class="mt-4 h-2.5 overflow-hidden rounded-full bg-violet-100">

                                <div
                                    class="h-full rounded-full bg-violet-600"
                                    style="width: {{ $lessonProgress }}%;"
                                ></div>

                            </div>

                        </div>



                        <div class="mt-4 grid grid-cols-3 gap-2">

                            <div class="rounded-xl bg-slate-50 p-3 text-center">

                                <p class="text-lg font-bold text-emerald-600">
                                    {{ $completedLessons }}
                                </p>

                                <p class="mt-1 text-[9px] font-semibold text-slate-400">
                                    Done
                                </p>

                            </div>


                            <div class="rounded-xl bg-slate-50 p-3 text-center">

                                <p class="text-lg font-bold text-blue-600">
                                    {{ $inProgressLessons }}
                                </p>

                                <p class="mt-1 text-[9px] font-semibold text-slate-400">
                                    Active
                                </p>

                            </div>


                            <div class="rounded-xl bg-slate-50 p-3 text-center">

                                <p class="text-lg font-bold text-orange-500">
                                    {{ $notStartedLessons }}
                                </p>

                                <p class="mt-1 text-[9px] font-semibold text-slate-400">
                                    Remaining
                                </p>

                            </div>

                        </div>

                    </section>



                    {{-- COURSE REQUIREMENTS --}}
                    <section class="pw-card p-5">

                        <h2 class="text-sm font-bold text-slate-900">
                            Course Requirements
                        </h2>


                        <div class="mt-4 space-y-3">


                            {{-- LESSONS --}}
                            <div
                                class="flex items-start gap-3
                                       rounded-xl border
                                       border-slate-100 p-3"
                            >

                                <div
                                    class="flex h-8 w-8 shrink-0
                                           items-center justify-center
                                           rounded-lg
                                           {{
                                               $allLessonsCompleted
                                               ? 'bg-emerald-50 text-emerald-600'
                                               : 'bg-slate-100 text-slate-500'
                                           }}"
                                >
                                    {{ $allLessonsCompleted ? '✓' : '1' }}
                                </div>


                                <div>

                                    <p class="text-xs font-semibold text-slate-700">
                                        Complete all lessons
                                    </p>

                                    <p class="mt-1 text-[10px] text-slate-400">
                                        {{ $completedLessons }}
                                        of
                                        {{ $totalLessons }}
                                        completed
                                    </p>

                                </div>

                            </div>



                            {{-- FINAL QUIZ --}}
                            @if($finalQuiz)

                                <div
                                    class="flex items-start gap-3
                                           rounded-xl border
                                           border-slate-100 p-3"
                                >

                                    <div
                                        class="flex h-8 w-8 shrink-0
                                               items-center justify-center
                                               rounded-lg
                                               {{
                                                   $passedFinalQuiz
                                                   ? 'bg-emerald-50 text-emerald-600'
                                                   : 'bg-orange-50 text-orange-500'
                                               }}"
                                    >
                                        {{ $passedFinalQuiz ? '✓' : '2' }}
                                    </div>


                                    <div>

                                        <p class="text-xs font-semibold text-slate-700">
                                            Pass final assessment
                                        </p>

                                        <p class="mt-1 text-[10px] text-slate-400">
                                            Passing score:
                                            {{ $finalQuiz->passing_score }}%
                                        </p>

                                    </div>

                                </div>

                            @endif



                            {{-- CERTIFICATE --}}
                            @if($course->certificate_available)

                                <div
                                    class="flex items-start gap-3
                                           rounded-xl border
                                           border-slate-100 p-3"
                                >

                                    <div
                                        class="flex h-8 w-8 shrink-0
                                               items-center justify-center
                                               rounded-lg
                                               {{
                                                   $courseCompleted
                                                   ? 'bg-emerald-50 text-emerald-600'
                                                   : 'bg-violet-50 text-violet-600'
                                               }}"
                                    >
                                        <svg
                                            class="h-4 w-4"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                        >
                                            <circle cx="12" cy="8" r="5"></circle>
                                            <path d="M8.5 12.5 7 22l5-3 5 3-1.5-9.5"></path>
                                        </svg>
                                    </div>


                                    <div>

                                        <p class="text-xs font-semibold text-slate-700">
                                            Earn certificate
                                        </p>

                                        <p class="mt-1 text-[10px] text-slate-400">
                                            Complete all course requirements.
                                        </p>

                                    </div>

                                </div>

                            @endif

                        </div>

                    </section>

                </aside>

            </div>



            {{-- =====================================================
                FINAL QUIZ
                Only appears when a quiz is NOT linked to a lesson.
            ====================================================== --}}

            @if($finalQuiz)

                <section class="pw-card mt-6 overflow-hidden">

                    <div
                        class="flex flex-col gap-4
                               border-b border-slate-100
                               p-5
                               sm:flex-row
                               sm:items-center
                               sm:justify-between
                               sm:p-6"
                    >

                        <div class="flex items-center gap-3">

                            <div
                                class="flex h-10 w-10
                                       items-center justify-center
                                       rounded-xl bg-orange-50
                                       text-orange-500"
                            >
                                ?
                            </div>


                            <div>

                                <p
                                    class="text-[10px] font-bold
                                           uppercase tracking-[.08em]
                                           text-orange-500"
                                >
                                    Final Assessment
                                </p>

                                <h2 class="mt-1 text-lg font-bold text-slate-900">
                                    {{ $finalQuiz->title }}
                                </h2>

                            </div>

                        </div>


                        <span
                            class="self-start rounded-full
                                   bg-slate-100 px-3 py-1.5
                                   text-[10px] font-semibold
                                   text-slate-600"
                        >
                            Passing Score:
                            {{ $finalQuiz->passing_score }}%
                        </span>

                    </div>



                    <div class="p-5 sm:p-6">

                        @if($finalQuiz->description)

                            <p class="max-w-3xl text-sm leading-6 text-slate-500">
                                {{ $finalQuiz->description }}
                            </p>

                        @endif



                        @if($finalQuizResult)

                            <div
                                class="mt-5 rounded-2xl border p-4
                                       {{
                                           $passedFinalQuiz
                                           ? 'border-emerald-200 bg-emerald-50'
                                           : 'border-red-200 bg-red-50'
                                       }}"
                            >

                                <div class="flex items-start justify-between gap-4">

                                    <div>

                                        <p
                                            class="text-xs font-bold
                                                   {{
                                                       $passedFinalQuiz
                                                       ? 'text-emerald-700'
                                                       : 'text-red-600'
                                                   }}"
                                        >
                                            Latest Result
                                        </p>

                                        <p
                                            class="mt-2 text-2xl font-bold
                                                   {{
                                                       $passedFinalQuiz
                                                       ? 'text-emerald-700'
                                                       : 'text-red-600'
                                                   }}"
                                        >
                                            {{
                                                number_format(
                                                    (float) $finalQuizResult->percentage,
                                                    1
                                                )
                                            }}%
                                        </p>

                                    </div>


                                    <span
                                        class="rounded-full px-3 py-1.5
                                               text-[10px] font-bold
                                               {{
                                                   $passedFinalQuiz
                                                   ? 'bg-emerald-100 text-emerald-700'
                                                   : 'bg-red-100 text-red-600'
                                               }}"
                                    >
                                        {{
                                            $passedFinalQuiz
                                            ? 'Passed'
                                            : 'Failed'
                                        }}
                                    </span>

                                </div>

                            </div>

                        @endif



                        @if($allLessonsCompleted)

                            <a
                                href="{{ route('student.quiz.take', $finalQuiz) }}"
                                class="mt-5 inline-flex h-11
                                       items-center justify-center gap-2
                                       rounded-xl
                                       {{
                                           $failedFinalQuiz
                                           ? 'bg-red-600 hover:bg-red-700'
                                           : 'bg-violet-600 hover:bg-violet-700'
                                       }}
                                       px-5 text-sm font-semibold
                                       text-white transition"
                            >

                                @if($finalQuizResult)

                                    Retake Final Quiz

                                @else

                                    Take Final Quiz

                                @endif


                                <svg
                                    class="h-4 w-4"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path d="m9 18 6-6-6-6"></path>
                                </svg>

                            </a>


                        @else


                            <div
                                class="mt-5 flex items-start gap-3
                                       rounded-2xl
                                       border border-orange-200
                                       bg-orange-50 p-4"
                            >

                                <div
                                    class="flex h-8 w-8 shrink-0
                                           items-center justify-center
                                           rounded-full bg-orange-100
                                           text-orange-600"
                                >
                                    !
                                </div>


                                <div>

                                    <p class="text-xs font-bold text-orange-800">
                                        Final quiz locked
                                    </p>

                                    <p class="mt-1 text-[11px] leading-5 text-orange-700">
                                        Complete all course lessons before taking
                                        the final assessment.
                                    </p>

                                </div>

                            </div>

                        @endif

                    </div>

                </section>

            @endif



            {{-- =====================================================
                COURSE COMPLETED
            ====================================================== --}}

            @if($courseCompleted)

                <section
                    class="pw-card mt-6
                           border-emerald-200
                           bg-emerald-50/50
                           p-5 sm:p-6"
                >

                    <div
                        class="flex flex-col gap-5
                               sm:flex-row
                               sm:items-center
                               sm:justify-between"
                    >

                        <div class="flex items-start gap-4">

                            <div
                                class="flex h-12 w-12 shrink-0
                                       items-center justify-center
                                       rounded-2xl
                                       bg-emerald-100
                                       font-bold
                                       text-emerald-600"
                            >
                                ✓
                            </div>


                            <div>

                                <p
                                    class="text-[10px] font-bold
                                           uppercase tracking-[.1em]
                                           text-emerald-600"
                                >
                                    Course Completed
                                </p>

                                <h2
                                    class="mt-1 text-xl font-bold
                                           text-emerald-900"
                                >
                                    Congratulations!
                                </h2>

                                <p
                                    class="mt-1 max-w-xl
                                           text-sm leading-6
                                           text-emerald-700"
                                >
                                    You have successfully completed
                                    the course requirements.
                                </p>

                            </div>

                        </div>


                        @if($course->certificate_available)

                            <a
                                href="{{ route('student.certificates') }}"
                                class="inline-flex h-11
                                       self-start items-center
                                       justify-center gap-2
                                       rounded-xl bg-emerald-600
                                       px-5 text-sm font-semibold
                                       text-white transition
                                       hover:bg-emerald-700"
                            >

                                <svg
                                    class="h-4 w-4"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <circle cx="12" cy="8" r="5"></circle>
                                    <path d="M8.5 12.5 7 22l5-3 5 3-1.5-9.5"></path>
                                </svg>

                                View Certificate
                            </a>

                        @endif

                    </div>

                </section>

            @endif



            {{-- =====================================================
                AI RECOMMENDATION
            ====================================================== --}}

            @if(
                $latestRecommendation &&
                $latestRecommendation->course &&
                $latestRecommendation->course->id !== $course->id
            )

                @php
                    $recommendedCourse =
                        $latestRecommendation->course;

                    $recommendationScore =
                        min(
                            max(
                                (float) (
                                    $latestRecommendation->recommendation_score
                                    ?? 0
                                ),
                                0
                            ),
                            100
                        );

                    $recommendThumbnail =
                        $recommendedCourse->thumbnail ?? null;

                    $recommendThumbnailUrl = null;

                    if ($recommendThumbnail) {

                        if (
                            \Illuminate\Support\Str::startsWith(
                                $recommendThumbnail,
                                ['http://', 'https://']
                            )
                        ) {
                            $recommendThumbnailUrl =
                                $recommendThumbnail;
                        } else {
                            $recommendThumbnailUrl =
                                asset(
                                    'storage/' .
                                    ltrim(
                                        $recommendThumbnail,
                                        '/'
                                    )
                                );
                        }
                    }
                @endphp


                <section class="pw-card mt-6 overflow-hidden">

                    <div
                        class="border-b border-slate-100
                               p-5 sm:p-6"
                    >

                        <div class="flex items-center justify-between">

                            <div>

                                <p
                                    class="text-[10px] font-bold
                                           uppercase tracking-[.1em]
                                           text-violet-600"
                                >
                                    PathWise Recommendation
                                </p>

                                <h2 class="mt-1 text-lg font-bold text-slate-900">
                                    Continue Your Learning Journey
                                </h2>

                                <p class="mt-1 text-xs text-slate-500">
                                    Personalized from your recent learning performance.
                                </p>

                            </div>


                            <div
                                class="flex h-10 w-10
                                       items-center justify-center
                                       rounded-xl bg-violet-50
                                       text-violet-600"
                            >
                                ✦
                            </div>

                        </div>

                    </div>



                    <div
                        class="grid grid-cols-1
                               gap-5 p-5
                               md:grid-cols-[190px_minmax(0,1fr)]
                               sm:p-6"
                    >


                        {{-- IMAGE --}}
                        <div
                            class="h-36 overflow-hidden
                                   rounded-2xl"
                        >

                            @if($recommendThumbnailUrl)

                                <img
                                    src="{{ $recommendThumbnailUrl }}"
                                    alt="{{ $recommendedCourse->title }}"
                                    class="h-full w-full object-cover"
                                >

                            @else

                                <div
                                    class="flex h-full w-full
                                           items-center justify-center
                                           bg-linear-to-br
                                           from-violet-500
                                           via-indigo-500
                                           to-blue-500"
                                >
                                    <svg
                                        class="h-8 w-8 text-white/80"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.5"
                                    >
                                        <path d="M4 19.5A2.5 2.5 0 016.5 17H20"></path>
                                        <path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"></path>
                                    </svg>
                                </div>

                            @endif

                        </div>



                        {{-- INFO --}}
                        <div>

                            <p
                                class="text-[10px] font-bold
                                       uppercase tracking-[.08em]
                                       text-violet-500"
                            >
                                {{
                                    $recommendedCourse->category?->name
                                    ?? 'Recommended Course'
                                }}
                            </p>


                            <h3
                                class="mt-2 text-lg font-bold
                                       text-slate-900"
                            >
                                {{ $recommendedCourse->title }}
                            </h3>


                            @if($latestRecommendation->reason)

                                <p
                                    class="mt-2 max-w-3xl
                                           text-xs leading-6
                                           text-slate-500"
                                >
                                    {{ $latestRecommendation->reason }}
                                </p>

                            @endif



                            <div class="mt-4 max-w-md">

                                <div
                                    class="flex items-center
                                           justify-between"
                                >

                                    <span
                                        class="text-[10px]
                                               font-semibold
                                               text-slate-400"
                                    >
                                        Recommendation match
                                    </span>

                                    <span
                                        class="text-xs font-bold
                                               text-violet-600"
                                    >
                                        {{
                                            number_format(
                                                $recommendationScore,
                                                0
                                            )
                                        }}%
                                    </span>

                                </div>


                                <div
                                    class="mt-2 h-2
                                           overflow-hidden
                                           rounded-full
                                           bg-slate-100"
                                >

                                    <div
                                        class="h-full
                                               rounded-full
                                               bg-violet-600"
                                        style="
                                            width:
                                            {{ $recommendationScore }}%;
                                        "
                                    ></div>

                                </div>

                            </div>



                            <a
                                href="{{ route('student.course.show', $recommendedCourse) }}"
                                class="mt-5 inline-flex h-10
                                       items-center justify-center
                                       gap-2 rounded-xl
                                       bg-violet-600 px-4
                                       text-xs font-semibold
                                       text-white transition
                                       hover:bg-violet-700"
                            >
                                View Recommended Course

                                <svg
                                    class="h-3.5 w-3.5"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path d="m9 18 6-6-6-6"></path>
                                </svg>
                            </a>

                        </div>

                    </div>

                </section>

            @endif

        </div>

    </main>

</div>

</x-layouts::app>