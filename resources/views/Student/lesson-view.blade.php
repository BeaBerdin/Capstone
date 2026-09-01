<x-layouts::app :title="$lesson->title">

@php
    $studentId = auth()->id();

    /*
    |--------------------------------------------------------------------------
    | BASIC LESSON DATA
    |--------------------------------------------------------------------------
    */

    $course = $lesson->course;

    $lessonType = strtolower(
        $lesson->lesson_type ?? 'text'
    );

    $typeLabels = [
        'video' => 'Video Lesson',
        'text' => 'Reading',
        'document' => 'Document',
        'quiz' => 'Quiz',
    ];

    $typeLabel =
        $typeLabels[$lessonType]
        ?? ucfirst($lessonType);

    $duration =
        (int) ($lesson->duration_minutes ?? 0);


    /*
    |--------------------------------------------------------------------------
    | QUIZ LESSON
    |--------------------------------------------------------------------------
    */

    $linkedQuiz = null;
    $quizResult = null;
    $quizPassed = false;

    if ($lessonType === 'quiz') {

        $linkedQuiz = \App\Models\Quiz::where(
                'lesson_id',
                $lesson->id
            )
            ->where(
                'is_published',
                true
            )
            ->first();

        if ($linkedQuiz) {

            $quizResult = \App\Models\QuizResult::where(
                    'student_id',
                    $studentId
                )
                ->where(
                    'quiz_id',
                    $linkedQuiz->id
                )
                ->latest('completed_at')
                ->first();

            $quizPassed =
                $quizResult &&
                strtolower(
                    $quizResult->remarks ?? ''
                ) === 'passed';
        }
    }


    /*
    |--------------------------------------------------------------------------
    | COMPLETION STATUS
    |--------------------------------------------------------------------------
    */

    $isCompleted =
        strtolower(
            $progress->status ?? ''
        ) === 'completed'
        ||
        $quizPassed;

    $isInProgress =
        !$isCompleted &&
        strtolower(
            $progress->status ?? ''
        ) === 'in_progress';


    /*
    |--------------------------------------------------------------------------
    | VIDEO EMBED
    |--------------------------------------------------------------------------
    */

    $videoUrl =
        $lesson->video_url ?? null;

    $videoEmbedUrl = null;

    if ($videoUrl) {

        $parsedVideoUrl =
            parse_url($videoUrl);

        $videoHost =
            strtolower(
                $parsedVideoUrl['host']
                ?? ''
            );

        /*
         * youtube.com/watch?v=
         */
        if (
            str_contains(
                $videoHost,
                'youtube.com'
            )
            &&
            isset(
                $parsedVideoUrl['query']
            )
        ) {

            parse_str(
                $parsedVideoUrl['query'],
                $videoQuery
            );

            if (!empty($videoQuery['v'])) {

                $videoEmbedUrl =
                    'https://www.youtube.com/embed/'
                    .
                    $videoQuery['v'];
            }
        }

        /*
         * youtu.be/
         */
        if (
            str_contains(
                $videoHost,
                'youtu.be'
            )
        ) {

            $youtubeId =
                trim(
                    $parsedVideoUrl['path']
                    ?? '',
                    '/'
                );

            if ($youtubeId) {

                $videoEmbedUrl =
                    'https://www.youtube.com/embed/'
                    .
                    $youtubeId;
            }
        }

        /*
         * Already an embed URL.
         */
        if (
            str_contains(
                $videoUrl,
                'youtube.com/embed/'
            )
        ) {
            $videoEmbedUrl =
                $videoUrl;
        }
    }


    /*
    |--------------------------------------------------------------------------
    | DOCUMENT
    |--------------------------------------------------------------------------
    */

    $filePath =
        $lesson->file_path ?? null;

    $fileUrl = null;
    $fileExtension = null;

    if ($filePath) {

        $fileUrl =
            asset(
                'storage/'
                .
                ltrim(
                    $filePath,
                    '/'
                )
            );

        $fileExtension =
            strtolower(
                pathinfo(
                    $filePath,
                    PATHINFO_EXTENSION
                )
            );
    }

    $isPdf =
        $fileExtension === 'pdf';


    /*
    |--------------------------------------------------------------------------
    | PREVIOUS LESSON URL
    |--------------------------------------------------------------------------
    */

    $previousUrl = null;

    if ($previousLesson) {

        if (
            strtolower(
                $previousLesson->lesson_type
                ?? ''
            ) === 'quiz'
        ) {

            $previousQuiz =
                \App\Models\Quiz::where(
                    'lesson_id',
                    $previousLesson->id
                )
                ->where(
                    'is_published',
                    true
                )
                ->first();

            if ($previousQuiz) {

                $previousUrl =
                    route(
                        'student.quiz.take',
                        $previousQuiz
                    );

            } else {

                $previousUrl =
                    route(
                        'student.lesson.view',
                        $previousLesson
                    );
            }

        } else {

            $previousUrl =
                route(
                    'student.lesson.view',
                    $previousLesson
                );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | NEXT LESSON URL
    |--------------------------------------------------------------------------
    */

    $nextUrl = null;

    if ($nextLesson) {

        if (
            strtolower(
                $nextLesson->lesson_type
                ?? ''
            ) === 'quiz'
        ) {

            $nextQuiz =
                \App\Models\Quiz::where(
                    'lesson_id',
                    $nextLesson->id
                )
                ->where(
                    'is_published',
                    true
                )
                ->first();

            if ($nextQuiz) {

                $nextUrl =
                    route(
                        'student.quiz.take',
                        $nextQuiz
                    );

            } else {

                $nextUrl =
                    route(
                        'student.lesson.view',
                        $nextLesson
                    );
            }

        } else {

            $nextUrl =
                route(
                    'student.lesson.view',
                    $nextLesson
                );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | COURSE LESSON COUNT
    |--------------------------------------------------------------------------
    */

    $totalLessons =
        $course
            ->lessons()
            ->count();

    $lessonNumber =
        $lesson->lesson_order
        ?? 1;
@endphp


<style>
    .pw-card {
        background: #ffffff;
        border: 1px solid #e7e9ef;
        border-radius: 20px;
        box-shadow:
            0 1px 3px
            rgba(15, 23, 42, 0.035);
    }

    .pw-soft-card {
        background: #f8fafc;
        border: 1px solid #eef0f4;
        border-radius: 14px;
    }

    .pw-content {
        line-height: 1.8;
        color: #475569;
    }

    .pw-content p {
        margin-bottom: 1rem;
    }
</style>


<div class="min-h-screen bg-[#f8f9fc]">

    <main class="px-5 py-7 sm:px-6 lg:px-8 lg:py-9">

        <div class="mx-auto max-w-[1500px]">


            {{-- =====================================================
                BREADCRUMB
            ====================================================== --}}

            <div
                class="mb-5 flex flex-wrap
                       items-center gap-2
                       text-xs text-slate-400"
            >

                <a
                    href="{{ route('student.my-courses') }}"
                    class="font-medium transition
                           hover:text-violet-600"
                >
                    My Courses
                </a>

                <span>
                    ›
                </span>

                <a
                    href="{{ route('student.learn.course', $course) }}"
                    class="font-medium transition
                           hover:text-violet-600"
                >
                    {{ $course->title }}
                </a>

                <span>
                    ›
                </span>

                <span
                    class="font-semibold
                           text-slate-600"
                >
                    Lesson {{ $lessonNumber }}
                </span>

            </div>



            {{-- =====================================================
                HEADER
            ====================================================== --}}

            <div
                class="flex flex-col gap-5
                       lg:flex-row
                       lg:items-end
                       lg:justify-between"
            >

                <div>

                    <div
                        class="flex flex-wrap
                               items-center gap-2"
                    >

                        <p
                            class="text-xs font-bold
                                   uppercase
                                   tracking-[.12em]
                                   text-violet-600"
                        >
                            Lesson {{ $lessonNumber }}
                        </p>


                        <span
                            class="rounded-full
                                   bg-slate-100
                                   px-2.5 py-1
                                   text-[10px]
                                   font-semibold
                                   text-slate-600"
                        >
                            {{ $typeLabel }}
                        </span>


                        @if($isCompleted)

                            <span
                                class="inline-flex
                                       items-center gap-1.5
                                       rounded-full
                                       bg-emerald-50
                                       px-2.5 py-1
                                       text-[10px]
                                       font-bold
                                       text-emerald-700"
                            >
                                <span
                                    class="h-1.5 w-1.5
                                           rounded-full
                                           bg-emerald-500"
                                ></span>

                                Completed
                            </span>

                        @elseif($isInProgress)

                            <span
                                class="inline-flex
                                       items-center gap-1.5
                                       rounded-full
                                       bg-blue-50
                                       px-2.5 py-1
                                       text-[10px]
                                       font-bold
                                       text-blue-700"
                            >
                                <span
                                    class="h-1.5 w-1.5
                                           rounded-full
                                           bg-blue-500"
                                ></span>

                                In Progress
                            </span>

                        @endif

                    </div>


                    <h1
                        class="mt-3 max-w-4xl
                               text-3xl font-bold
                               tracking-tight
                               text-slate-950"
                    >
                        {{ $lesson->title }}
                    </h1>


                    <p
                        class="mt-2 max-w-3xl
                               text-sm leading-6
                               text-slate-500"
                    >
                        {{ $course->title }}
                    </p>

                </div>


                <a
                    href="{{ route('student.learn.course', $course) }}"
                    class="inline-flex h-11
                           self-start
                           items-center
                           justify-center
                           gap-2 rounded-xl
                           border border-slate-200
                           bg-white px-4
                           text-sm font-semibold
                           text-slate-600
                           transition
                           hover:border-violet-200
                           hover:text-violet-700"
                >

                    <svg
                        class="h-4 w-4"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path d="M19 12H5"></path>
                        <path d="m12 19-7-7 7-7"></path>
                    </svg>

                    Back to Course
                </a>

            </div>



            {{-- =====================================================
                MESSAGES
            ====================================================== --}}

            @if(session('success'))

                <div
                    class="mt-6 flex items-start
                           gap-3 rounded-2xl
                           border border-emerald-200
                           bg-emerald-50
                           px-5 py-4"
                >

                    <div
                        class="flex h-8 w-8
                               shrink-0
                               items-center
                               justify-center
                               rounded-full
                               bg-emerald-100
                               font-bold
                               text-emerald-600"
                    >
                        ✓
                    </div>


                    <div>

                        <p
                            class="text-sm font-bold
                                   text-emerald-800"
                        >
                            Lesson updated
                        </p>

                        <p
                            class="mt-1 text-xs
                                   text-emerald-700"
                        >
                            {{ session('success') }}
                        </p>

                    </div>

                </div>

            @endif


            @if(session('error'))

                <div
                    class="mt-6 flex items-start
                           gap-3 rounded-2xl
                           border border-red-200
                           bg-red-50
                           px-5 py-4"
                >

                    <div
                        class="flex h-8 w-8
                               shrink-0
                               items-center
                               justify-center
                               rounded-full
                               bg-red-100
                               font-bold
                               text-red-600"
                    >
                        !
                    </div>


                    <div>

                        <p
                            class="text-sm font-bold
                                   text-red-800"
                        >
                            Something went wrong
                        </p>

                        <p
                            class="mt-1 text-xs
                                   text-red-700"
                        >
                            {{ session('error') }}
                        </p>

                    </div>

                </div>

            @endif



            {{-- =====================================================
                MAIN GRID
            ====================================================== --}}

            <div
                class="mt-7 grid
                       grid-cols-1 gap-6
                       xl:grid-cols-[minmax(0,1fr)_330px]"
            >


                {{-- =================================================
                    LESSON CONTENT
                ================================================== --}}

                <div class="space-y-6">


                    {{-- =============================================
                        VIDEO LESSON
                    ============================================== --}}

                    @if($lessonType === 'video')

                        <section
                            class="pw-card overflow-hidden"
                        >

                            <div
                                class="flex items-center
                                       justify-between
                                       border-b
                                       border-slate-100
                                       p-5 sm:p-6"
                            >

                                <div>

                                    <p
                                        class="text-[10px]
                                               font-bold
                                               uppercase
                                               tracking-[.1em]
                                               text-violet-500"
                                    >
                                        Video Lesson
                                    </p>

                                    <h2
                                        class="mt-1
                                               text-lg font-bold
                                               text-slate-900"
                                    >
                                        Watch Lesson Video
                                    </h2>

                                </div>


                                <div
                                    class="flex h-10 w-10
                                           items-center
                                           justify-center
                                           rounded-xl
                                           bg-violet-50
                                           text-violet-600"
                                >
                                    <svg
                                        class="h-5 w-5"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                    >
                                        <circle
                                            cx="12"
                                            cy="12"
                                            r="9"
                                        ></circle>

                                        <path
                                            d="m10 8
                                               6 4-6 4z"
                                        ></path>
                                    </svg>
                                </div>

                            </div>



                            @if($videoUrl)

                                @if($videoEmbedUrl)

                                    <div
                                        class="aspect-video
                                               w-full
                                               bg-slate-950"
                                    >

                                        <iframe
                                            src="{{ $videoEmbedUrl }}"
                                            title="{{ $lesson->title }}"
                                            class="h-full w-full"
                                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                            allowfullscreen
                                        ></iframe>

                                    </div>


                                    <div class="p-5 sm:p-6">

                                        <a
                                            href="{{ $videoUrl }}"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="inline-flex h-10
                                                   items-center
                                                   justify-center gap-2
                                                   rounded-xl
                                                   border
                                                   border-slate-200
                                                   bg-white px-4
                                                   text-xs font-semibold
                                                   text-slate-600
                                                   transition
                                                   hover:border-violet-200
                                                   hover:text-violet-700"
                                        >
                                            Open Video in New Tab

                                            <svg
                                                class="h-3.5 w-3.5"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="2"
                                            >
                                                <path
                                                    d="M14 3h7v7"
                                                ></path>

                                                <path
                                                    d="M10 14
                                                       21 3"
                                                ></path>

                                                <path
                                                    d="M21 14v5
                                                       a2 2 0 01-2 2
                                                       H5a2 2 0 01-2-2
                                                       V5a2 2 0 012-2
                                                       h5"
                                                ></path>
                                            </svg>
                                        </a>

                                    </div>


                                @else

                                    <div
                                        class="flex min-h-[260px]
                                               items-center
                                               justify-center
                                               bg-slate-950
                                               p-6 text-center"
                                    >

                                        <div>

                                            <div
                                                class="mx-auto flex
                                                       h-14 w-14
                                                       items-center
                                                       justify-center
                                                       rounded-full
                                                       bg-white/10
                                                       text-white"
                                            >
                                                <svg
                                                    class="h-6 w-6"
                                                    viewBox="0 0 24 24"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    stroke-width="2"
                                                >
                                                    <circle
                                                        cx="12"
                                                        cy="12"
                                                        r="9"
                                                    ></circle>

                                                    <path
                                                        d="m10 8
                                                           6 4-6 4z"
                                                    ></path>
                                                </svg>
                                            </div>


                                            <p
                                                class="mt-4
                                                       text-sm font-semibold
                                                       text-white"
                                            >
                                                Video available externally
                                            </p>


                                            <a
                                                href="{{ $videoUrl }}"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                class="mt-4
                                                       inline-flex h-10
                                                       items-center
                                                       justify-center
                                                       rounded-xl
                                                       bg-violet-600
                                                       px-4
                                                       text-xs font-semibold
                                                       text-white
                                                       hover:bg-violet-700"
                                            >
                                                Open Lesson Video
                                            </a>

                                        </div>

                                    </div>

                                @endif


                            @else

                                <div
                                    class="px-6 py-16
                                           text-center"
                                >

                                    <div
                                        class="mx-auto flex
                                               h-12 w-12
                                               items-center
                                               justify-center
                                               rounded-2xl
                                               bg-slate-100
                                               text-slate-400"
                                    >
                                        ▶
                                    </div>


                                    <h3
                                        class="mt-4
                                               text-sm font-bold
                                               text-slate-800"
                                    >
                                        No video available
                                    </h3>


                                    <p
                                        class="mt-1
                                               text-xs
                                               text-slate-400"
                                    >
                                        This lesson currently
                                        has no video link.
                                    </p>

                                </div>

                            @endif

                        </section>

                    @endif



                    {{-- =============================================
                        DOCUMENT LESSON
                    ============================================== --}}

                    @if($lessonType === 'document')

                        <section
                            class="pw-card overflow-hidden"
                        >

                            <div
                                class="flex flex-col gap-4
                                       border-b
                                       border-slate-100
                                       p-5
                                       sm:flex-row
                                       sm:items-center
                                       sm:justify-between
                                       sm:p-6"
                            >

                                <div
                                    class="flex items-center gap-3"
                                >

                                    <div
                                        class="flex h-10 w-10
                                               shrink-0
                                               items-center
                                               justify-center
                                               rounded-xl
                                               bg-blue-50
                                               text-blue-600"
                                    >
                                        <svg
                                            class="h-5 w-5"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                        >
                                            <path
                                                d="M6 2h9
                                                   l5 5v15H6z"
                                            ></path>

                                            <path
                                                d="M14 2v6h6"
                                            ></path>
                                        </svg>
                                    </div>


                                    <div>

                                        <p
                                            class="text-[10px]
                                                   font-bold uppercase
                                                   tracking-[.1em]
                                                   text-blue-500"
                                        >
                                            Lesson Material
                                        </p>

                                        <h2
                                            class="mt-1
                                                   text-lg font-bold
                                                   text-slate-900"
                                        >
                                            Course Document
                                        </h2>

                                    </div>

                                </div>


                                @if($fileUrl)

                                    <a
                                        href="{{ $fileUrl }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="inline-flex h-10
                                               self-start
                                               items-center
                                               justify-center
                                               gap-2 rounded-xl
                                               bg-violet-600
                                               px-4
                                               text-xs font-semibold
                                               text-white transition
                                               hover:bg-violet-700"
                                    >
                                        Open Document

                                        <svg
                                            class="h-3.5 w-3.5"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                        >
                                            <path
                                                d="M14 3h7v7"
                                            ></path>

                                            <path
                                                d="M10 14
                                                   21 3"
                                            ></path>

                                            <path
                                                d="M21 14v5
                                                   a2 2 0 01-2 2H5
                                                   a2 2 0 01-2-2V5
                                                   a2 2 0 012-2h5"
                                            ></path>
                                        </svg>
                                    </a>

                                @endif

                            </div>



                            @if($fileUrl)

                                @if($isPdf)

                                    <div
                                        class="h-[700px]
                                               bg-slate-100"
                                    >

                                        <iframe
                                            src="{{ $fileUrl }}"
                                            class="h-full w-full"
                                            title="{{ $lesson->title }}"
                                        ></iframe>

                                    </div>


                                @else

                                    <div
                                        class="px-6 py-16
                                               text-center"
                                    >

                                        <div
                                            class="mx-auto flex
                                                   h-16 w-16
                                                   items-center
                                                   justify-center
                                                   rounded-2xl
                                                   bg-blue-50
                                                   text-blue-600"
                                        >
                                            <svg
                                                class="h-7 w-7"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="2"
                                            >
                                                <path
                                                    d="M6 2h9
                                                       l5 5v15H6z"
                                                ></path>

                                                <path
                                                    d="M14 2v6h6"
                                                ></path>
                                            </svg>
                                        </div>


                                        <h3
                                            class="mt-4
                                                   text-sm font-bold
                                                   text-slate-800"
                                        >
                                            Document ready
                                        </h3>


                                        <p
                                            class="mt-1 text-xs
                                                   text-slate-400"
                                        >
                                            {{
                                                strtoupper(
                                                    $fileExtension
                                                    ?? 'FILE'
                                                )
                                            }}
                                            lesson material
                                        </p>


                                        <a
                                            href="{{ $fileUrl }}"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="mt-5
                                                   inline-flex h-10
                                                   items-center
                                                   justify-center
                                                   rounded-xl
                                                   bg-violet-600
                                                   px-4
                                                   text-xs font-semibold
                                                   text-white
                                                   hover:bg-violet-700"
                                        >
                                            View Document
                                        </a>

                                    </div>

                                @endif


                            @else

                                <div
                                    class="px-6 py-16
                                           text-center"
                                >

                                    <div
                                        class="mx-auto flex
                                               h-14 w-14
                                               items-center
                                               justify-center
                                               rounded-2xl
                                               bg-slate-100
                                               text-slate-400"
                                    >
                                        ▤
                                    </div>


                                    <h3
                                        class="mt-4
                                               text-sm font-bold
                                               text-slate-800"
                                    >
                                        No document available
                                    </h3>

                                    <p
                                        class="mt-1
                                               text-xs
                                               text-slate-400"
                                    >
                                        No file has been
                                        attached to this lesson.
                                    </p>

                                </div>

                            @endif

                        </section>

                    @endif



                    {{-- =============================================
                        QUIZ LESSON
                    ============================================== --}}

                    @if($lessonType === 'quiz')

                        <section
                            class="pw-card overflow-hidden"
                        >

                            <div
                                class="border-b
                                       border-slate-100
                                       p-5 sm:p-6"
                            >

                                <div
                                    class="flex items-start
                                           gap-3"
                                >

                                    <div
                                        class="flex h-11 w-11
                                               shrink-0
                                               items-center
                                               justify-center
                                               rounded-xl
                                               bg-orange-50
                                               text-lg font-bold
                                               text-orange-500"
                                    >
                                        ?
                                    </div>


                                    <div>

                                        <p
                                            class="text-[10px]
                                                   font-bold uppercase
                                                   tracking-[.1em]
                                                   text-orange-500"
                                        >
                                            Assessment
                                        </p>

                                        <h2
                                            class="mt-1
                                                   text-lg font-bold
                                                   text-slate-900"
                                        >
                                            {{
                                                $linkedQuiz?->title
                                                ?? $lesson->title
                                            }}
                                        </h2>


                                        @if($linkedQuiz)

                                            <p
                                                class="mt-2 text-xs
                                                       text-slate-500"
                                            >
                                                Passing score:
                                                <strong
                                                    class="text-slate-700"
                                                >
                                                    {{
                                                        $linkedQuiz
                                                            ->passing_score
                                                    }}%
                                                </strong>
                                            </p>

                                        @endif

                                    </div>

                                </div>

                            </div>



                            <div class="p-5 sm:p-6">

                                @if($linkedQuiz)

                                    @if($linkedQuiz->description)

                                        <p
                                            class="max-w-3xl
                                                   text-sm leading-7
                                                   text-slate-500"
                                        >
                                            {{
                                                $linkedQuiz
                                                    ->description
                                            }}
                                        </p>

                                    @endif



                                    <div
                                        class="mt-5 grid
                                               grid-cols-2 gap-3
                                               sm:grid-cols-3"
                                    >

                                        <div
                                            class="pw-soft-card
                                                   p-4"
                                        >

                                            <p
                                                class="text-[9px]
                                                       font-bold uppercase
                                                       tracking-wide
                                                       text-slate-400"
                                            >
                                                Questions
                                            </p>

                                            <p
                                                class="mt-1
                                                       text-xl font-bold
                                                       text-slate-800"
                                            >
                                                {{
                                                    $linkedQuiz
                                                        ->questions()
                                                        ->count()
                                                }}
                                            </p>

                                        </div>


                                        <div
                                            class="pw-soft-card
                                                   p-4"
                                        >

                                            <p
                                                class="text-[9px]
                                                       font-bold uppercase
                                                       tracking-wide
                                                       text-slate-400"
                                            >
                                                Passing
                                            </p>

                                            <p
                                                class="mt-1
                                                       text-xl font-bold
                                                       text-violet-600"
                                            >
                                                {{
                                                    $linkedQuiz
                                                        ->passing_score
                                                }}%
                                            </p>

                                        </div>


                                        <div
                                            class="pw-soft-card
                                                   col-span-2
                                                   p-4
                                                   sm:col-span-1"
                                        >

                                            <p
                                                class="text-[9px]
                                                       font-bold uppercase
                                                       tracking-wide
                                                       text-slate-400"
                                            >
                                                Time Limit
                                            </p>

                                            <p
                                                class="mt-1
                                                       text-xl font-bold
                                                       text-blue-600"
                                            >
                                                @if(
                                                    $linkedQuiz
                                                        ->time_limit_minutes
                                                )

                                                    {{
                                                        $linkedQuiz
                                                            ->time_limit_minutes
                                                    }}
                                                    min

                                                @else

                                                    None

                                                @endif
                                            </p>

                                        </div>

                                    </div>



                                    @if($quizResult)

                                        <div
                                            class="mt-5
                                                   rounded-2xl
                                                   border p-4
                                                   {{
                                                       $quizPassed
                                                       ? 'border-emerald-200 bg-emerald-50'
                                                       : 'border-red-200 bg-red-50'
                                                   }}"
                                        >

                                            <div
                                                class="flex items-center
                                                       justify-between
                                                       gap-4"
                                            >

                                                <div>

                                                    <p
                                                        class="text-xs
                                                               font-bold
                                                               {{
                                                                   $quizPassed
                                                                   ? 'text-emerald-700'
                                                                   : 'text-red-600'
                                                               }}"
                                                    >
                                                        Latest Result
                                                    </p>


                                                    <p
                                                        class="mt-1
                                                               text-2xl
                                                               font-bold
                                                               {{
                                                                   $quizPassed
                                                                   ? 'text-emerald-700'
                                                                   : 'text-red-600'
                                                               }}"
                                                    >
                                                        {{
                                                            number_format(
                                                                (float) (
                                                                    $quizResult
                                                                        ->percentage
                                                                    ?? 0
                                                                ),
                                                                1
                                                            )
                                                        }}%
                                                    </p>

                                                </div>


                                                <span
                                                    class="rounded-full
                                                           px-3 py-1.5
                                                           text-[10px]
                                                           font-bold
                                                           {{
                                                               $quizPassed
                                                               ? 'bg-emerald-100 text-emerald-700'
                                                               : 'bg-red-100 text-red-600'
                                                           }}"
                                                >
                                                    {{
                                                        $quizPassed
                                                        ? 'Passed'
                                                        : 'Failed'
                                                    }}
                                                </span>

                                            </div>

                                        </div>

                                    @endif



                                    <a
                                        href="{{ route('student.quiz.take', $linkedQuiz) }}"
                                        class="mt-5
                                               inline-flex h-11
                                               items-center
                                               justify-center
                                               gap-2 rounded-xl
                                               bg-violet-600
                                               px-5
                                               text-sm font-semibold
                                               text-white transition
                                               hover:bg-violet-700"
                                    >

                                        @if($quizResult)
                                            Retake Quiz
                                        @else
                                            Take Quiz
                                        @endif

                                        <svg
                                            class="h-4 w-4"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                        >
                                            <path
                                                d="m9 18
                                                   6-6-6-6"
                                            ></path>
                                        </svg>

                                    </a>


                                @else

                                    <div
                                        class="py-10
                                               text-center"
                                    >

                                        <div
                                            class="mx-auto flex
                                                   h-14 w-14
                                                   items-center
                                                   justify-center
                                                   rounded-2xl
                                                   bg-orange-50
                                                   text-xl font-bold
                                                   text-orange-500"
                                        >
                                            !
                                        </div>


                                        <h3
                                            class="mt-4
                                                   text-sm font-bold
                                                   text-slate-800"
                                        >
                                            Quiz unavailable
                                        </h3>


                                        <p
                                            class="mt-1
                                                   text-xs
                                                   text-slate-400"
                                        >
                                            The instructor has not
                                            published this quiz yet.
                                        </p>

                                    </div>

                                @endif

                            </div>

                        </section>

                    @endif



                    {{-- =============================================
                        LESSON CONTENT / READING
                    ============================================== --}}

                    @if(
                        $lessonType !== 'quiz'
                        &&
                        $lesson->content
                    )

                        <section
                            class="pw-card overflow-hidden"
                        >

                            <div
                                class="flex items-center
                                       justify-between
                                       border-b
                                       border-slate-100
                                       p-5 sm:p-6"
                            >

                                <div>

                                    <p
                                        class="text-[10px]
                                               font-bold uppercase
                                               tracking-[.1em]
                                               text-violet-500"
                                    >
                                        Lesson Content
                                    </p>

                                    <h2
                                        class="mt-1
                                               text-lg font-bold
                                               text-slate-900"
                                    >
                                        {{
                                            $lessonType === 'text'
                                            ? 'Reading Material'
                                            : 'Lesson Notes'
                                        }}
                                    </h2>

                                </div>


                                <div
                                    class="flex h-10 w-10
                                           items-center
                                           justify-center
                                           rounded-xl
                                           bg-slate-100
                                           text-slate-500"
                                >
                                    Aa
                                </div>

                            </div>


                            <div
                                class="pw-content
                                       p-5 text-sm
                                       sm:p-7"
                            >
                                {!! nl2br(
                                    e(
                                        $lesson->content
                                    )
                                ) !!}
                            </div>

                        </section>


                    @elseif(
                        $lessonType === 'text'
                        &&
                        !$lesson->content
                    )

                        <section
                            class="pw-card
                                   px-6 py-16
                                   text-center"
                        >

                            <div
                                class="mx-auto flex
                                       h-14 w-14
                                       items-center
                                       justify-center
                                       rounded-2xl
                                       bg-slate-100
                                       text-slate-400"
                            >
                                Aa
                            </div>


                            <h3
                                class="mt-4
                                       text-sm font-bold
                                       text-slate-800"
                            >
                                No reading content
                            </h3>


                            <p
                                class="mt-1
                                       text-xs
                                       text-slate-400"
                            >
                                This lesson currently
                                has no written content.
                            </p>

                        </section>

                    @endif



                    {{-- =============================================
                        COMPLETION
                    ============================================== --}}

                    @if($lessonType !== 'quiz')

                        <section class="pw-card p-5 sm:p-6">

                            @if($isCompleted)

                                <div
                                    class="flex flex-col gap-4
                                           sm:flex-row
                                           sm:items-center
                                           sm:justify-between"
                                >

                                    <div
                                        class="flex items-start
                                               gap-3"
                                    >

                                        <div
                                            class="flex h-10 w-10
                                                   shrink-0
                                                   items-center
                                                   justify-center
                                                   rounded-full
                                                   bg-emerald-100
                                                   font-bold
                                                   text-emerald-600"
                                        >
                                            ✓
                                        </div>


                                        <div>

                                            <h3
                                                class="text-sm
                                                       font-bold
                                                       text-emerald-800"
                                            >
                                                Lesson completed
                                            </h3>


                                            <p
                                                class="mt-1
                                                       text-xs
                                                       leading-5
                                                       text-emerald-600"
                                            >
                                                You have completed this lesson.
                                                You can review it anytime.
                                            </p>

                                        </div>

                                    </div>


                                    @if($nextUrl)

                                        <a
                                            href="{{ $nextUrl }}"
                                            class="inline-flex h-10
                                                   self-start
                                                   items-center
                                                   justify-center
                                                   gap-2 rounded-xl
                                                   bg-violet-600
                                                   px-4
                                                   text-xs font-semibold
                                                   text-white
                                                   transition
                                                   hover:bg-violet-700"
                                        >
                                            Next Lesson

                                            <svg
                                                class="h-3.5 w-3.5"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="2"
                                            >
                                                <path
                                                    d="m9 18
                                                       6-6-6-6"
                                                ></path>
                                            </svg>
                                        </a>

                                    @endif

                                </div>


                            @else

                                <div
                                    class="flex flex-col gap-5
                                           sm:flex-row
                                           sm:items-center
                                           sm:justify-between"
                                >

                                    <div>

                                        <h3
                                            class="text-sm
                                                   font-bold
                                                   text-slate-800"
                                        >
                                            Finished this lesson?
                                        </h3>


                                        <p
                                            class="mt-1 max-w-xl
                                                   text-xs leading-5
                                                   text-slate-500"
                                        >
                                            Mark the lesson as complete
                                            to update your course progress.
                                        </p>

                                    </div>


                                    <form
                                        action="{{ route('student.lesson.complete', $lesson) }}"
                                        method="POST"
                                    >
                                        @csrf

                                        <button
                                            type="submit"
                                            class="inline-flex h-11
                                                   items-center
                                                   justify-center
                                                   gap-2 rounded-xl
                                                   bg-violet-600
                                                   px-5
                                                   text-sm font-semibold
                                                   text-white
                                                   transition
                                                   hover:bg-violet-700"
                                        >
                                            <svg
                                                class="h-4 w-4"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="2"
                                            >
                                                <path
                                                    d="M20 6
                                                       9 17l-5-5"
                                                ></path>
                                            </svg>

                                            Mark as Complete
                                        </button>

                                    </form>

                                </div>

                            @endif

                        </section>

                    @endif



                    {{-- =============================================
                        PREVIOUS / NEXT NAVIGATION
                    ============================================== --}}

                    <section
                        class="flex flex-col
                               gap-3
                               sm:flex-row
                               sm:items-center
                               sm:justify-between"
                    >

                        <div>

                            @if($previousUrl)

                                <a
                                    href="{{ $previousUrl }}"
                                    class="inline-flex h-11
                                           items-center
                                           justify-center
                                           gap-2 rounded-xl
                                           border
                                           border-slate-200
                                           bg-white px-4
                                           text-sm font-semibold
                                           text-slate-600
                                           transition
                                           hover:border-violet-200
                                           hover:text-violet-700"
                                >
                                    <svg
                                        class="h-4 w-4"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                    >
                                        <path
                                            d="m15 18
                                               -6-6 6-6"
                                        ></path>
                                    </svg>

                                    Previous Lesson
                                </a>

                            @endif

                        </div>



                        <div>

                            @if($nextUrl)

                                <a
                                    href="{{ $nextUrl }}"
                                    class="inline-flex h-11
                                           items-center
                                           justify-center
                                           gap-2 rounded-xl
                                           bg-violet-600
                                           px-4
                                           text-sm font-semibold
                                           text-white
                                           transition
                                           hover:bg-violet-700"
                                >
                                    Next Lesson

                                    <svg
                                        class="h-4 w-4"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                    >
                                        <path
                                            d="m9 18
                                               6-6-6-6"
                                        ></path>
                                    </svg>
                                </a>


                            @else

                                <a
                                    href="{{ route('student.learn.course', $course) }}"
                                    class="inline-flex h-11
                                           items-center
                                           justify-center
                                           gap-2 rounded-xl
                                           bg-emerald-600
                                           px-4
                                           text-sm font-semibold
                                           text-white
                                           transition
                                           hover:bg-emerald-700"
                                >
                                    Finish Course View

                                    <svg
                                        class="h-4 w-4"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                    >
                                        <path
                                            d="M20 6
                                               9 17l-5-5"
                                        ></path>
                                    </svg>
                                </a>

                            @endif

                        </div>

                    </section>

                </div>



                {{-- =================================================
                    SIDEBAR
                ================================================== --}}

                <aside class="space-y-5">


                    {{-- LESSON INFO --}}
                    <section
                        class="pw-card p-5
                               xl:sticky xl:top-6"
                    >

                        <div
                            class="flex items-start
                                   justify-between"
                        >

                            <div>

                                <h2
                                    class="text-sm font-bold
                                           text-slate-900"
                                >
                                    Lesson Details
                                </h2>


                                <p
                                    class="mt-1
                                           text-xs
                                           text-slate-500"
                                >
                                    Current lesson information.
                                </p>

                            </div>


                            <div
                                class="flex h-9 w-9
                                       items-center
                                       justify-center
                                       rounded-xl
                                       bg-violet-50
                                       text-violet-600"
                            >
                                @if($lessonType === 'video')
                                    ▶
                                @elseif($lessonType === 'document')
                                    ▤
                                @elseif($lessonType === 'quiz')
                                    ?
                                @else
                                    Aa
                                @endif
                            </div>

                        </div>



                        <div class="mt-5 space-y-4">


                            {{-- COURSE --}}
                            <div
                                class="flex items-center
                                       justify-between
                                       gap-4"
                            >

                                <span
                                    class="text-xs
                                           text-slate-500"
                                >
                                    Course
                                </span>


                                <span
                                    class="max-w-[160px]
                                           truncate
                                           text-right
                                           text-xs
                                           font-bold
                                           text-slate-700"
                                >
                                    {{ $course->title }}
                                </span>

                            </div>



                            {{-- LESSON --}}
                            <div
                                class="flex items-center
                                       justify-between
                                       gap-4"
                            >

                                <span
                                    class="text-xs
                                           text-slate-500"
                                >
                                    Lesson
                                </span>


                                <span
                                    class="text-xs
                                           font-bold
                                           text-slate-700"
                                >
                                    {{ $lessonNumber }}
                                    of
                                    {{ $totalLessons }}
                                </span>

                            </div>



                            {{-- TYPE --}}
                            <div
                                class="flex items-center
                                       justify-between
                                       gap-4"
                            >

                                <span
                                    class="text-xs
                                           text-slate-500"
                                >
                                    Type
                                </span>


                                <span
                                    class="rounded-full
                                           bg-violet-50
                                           px-2.5 py-1
                                           text-[10px]
                                           font-bold
                                           text-violet-700"
                                >
                                    {{ $typeLabel }}
                                </span>

                            </div>



                            {{-- DURATION --}}
                            <div
                                class="flex items-center
                                       justify-between
                                       gap-4"
                            >

                                <span
                                    class="text-xs
                                           text-slate-500"
                                >
                                    Duration
                                </span>


                                <span
                                    class="text-xs
                                           font-bold
                                           text-slate-700"
                                >
                                    @if($duration > 0)

                                        {{ $duration }} min

                                    @else

                                        Self-paced

                                    @endif
                                </span>

                            </div>



                            {{-- STATUS --}}
                            <div
                                class="flex items-center
                                       justify-between
                                       gap-4"
                            >

                                <span
                                    class="text-xs
                                           text-slate-500"
                                >
                                    Status
                                </span>


                                @if($isCompleted)

                                    <span
                                        class="inline-flex
                                               items-center gap-1.5
                                               rounded-full
                                               bg-emerald-50
                                               px-2.5 py-1
                                               text-[10px]
                                               font-bold
                                               text-emerald-700"
                                    >
                                        <span
                                            class="h-1.5 w-1.5
                                                   rounded-full
                                                   bg-emerald-500"
                                        ></span>

                                        Completed
                                    </span>


                                @else

                                    <span
                                        class="inline-flex
                                               items-center gap-1.5
                                               rounded-full
                                               bg-blue-50
                                               px-2.5 py-1
                                               text-[10px]
                                               font-bold
                                               text-blue-700"
                                    >
                                        <span
                                            class="h-1.5 w-1.5
                                                   rounded-full
                                                   bg-blue-500"
                                        ></span>

                                        In Progress
                                    </span>

                                @endif

                            </div>

                        </div>



                        {{-- PROGRESS --}}
                        <div
                            class="mt-5
                                   rounded-2xl
                                   bg-slate-50
                                   p-4"
                        >

                            <div
                                class="flex items-center
                                       justify-between"
                            >

                                <span
                                    class="text-[10px]
                                           font-semibold
                                           text-slate-400"
                                >
                                    COURSE POSITION
                                </span>


                                <span
                                    class="text-xs
                                           font-bold
                                           text-violet-600"
                                >
                                    {{
                                        $totalLessons > 0
                                        ? number_format(
                                            min(
                                                ($lessonNumber / $totalLessons) * 100,
                                                100
                                            ),
                                            0
                                        )
                                        : 0
                                    }}%
                                </span>

                            </div>


                            <div
                                class="mt-3 h-2
                                       overflow-hidden
                                       rounded-full
                                       bg-slate-200"
                            >

                                <div
                                    class="h-full
                                           rounded-full
                                           bg-violet-600"
                                    style="
                                        width:
                                        {{
                                            $totalLessons > 0
                                            ? min(
                                                ($lessonNumber / $totalLessons) * 100,
                                                100
                                            )
                                            : 0
                                        }}%;
                                    "
                                ></div>

                            </div>

                        </div>



                        {{-- BACK --}}
                        <a
                            href="{{ route('student.learn.course', $course) }}"
                            class="mt-5 inline-flex
                                   h-10 w-full
                                   items-center
                                   justify-center
                                   rounded-xl
                                   border
                                   border-slate-200
                                   bg-white
                                   text-xs font-semibold
                                   text-slate-600
                                   transition
                                   hover:border-violet-200
                                   hover:text-violet-700"
                        >
                            View All Lessons
                        </a>

                    </section>

                </aside>

            </div>

        </div>

    </main>

</div>

</x-layouts::app>