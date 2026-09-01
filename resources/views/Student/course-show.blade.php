<x-layouts::app :title="$course->title">

@php
    /*
    |--------------------------------------------------------------------------
    | STUDENT / ENROLLMENT
    |--------------------------------------------------------------------------
    */

    $enrollment = \App\Models\Enrollment::where(
            'student_id',
            auth()->id()
        )
        ->where(
            'course_id',
            $course->id
        )
        ->first();

    $isEnrolled = (bool) $enrollment;


    /*
    |--------------------------------------------------------------------------
    | PENDING TRANSACTION
    |--------------------------------------------------------------------------
    */

    $pendingTransaction = \App\Models\Transaction::where(
            'student_id',
            auth()->id()
        )
        ->where(
            'course_id',
            $course->id
        )
        ->where(
            'status',
            'pending'
        )
        ->latest()
        ->first();


    /*
    |--------------------------------------------------------------------------
    | COURSE DATA
    |--------------------------------------------------------------------------
    */

    $lessons = $course
        ->lessons()
        ->orderBy('lesson_order')
        ->get();

    $totalLessons = $lessons->count();

    $previewLessons = $lessons
        ->where('is_preview', true)
        ->count();

    $totalMinutes = $lessons->sum(
        fn ($lesson) =>
            (int) ($lesson->duration_minutes ?? 0)
    );

    $price = (float) ($course->price ?? 0);

    $isFree = $price <= 0;


    /*
    |--------------------------------------------------------------------------
    | THUMBNAIL
    |--------------------------------------------------------------------------
    */

    $thumbnail = $course->thumbnail ?? null;

    $thumbnailUrl = null;

    if ($thumbnail) {

        if (
            \Illuminate\Support\Str::startsWith(
                $thumbnail,
                [
                    'http://',
                    'https://',
                ]
            )
        ) {
            $thumbnailUrl = $thumbnail;
        } else {

            $thumbnailUrl = asset(
                'storage/' .
                ltrim(
                    $thumbnail,
                    '/'
                )
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | INSTRUCTOR
    |--------------------------------------------------------------------------
    */

    $teacherName =
        $course->teacher?->name
        ?? 'PathWise Instructor';

    $teacherInitials = collect(
        preg_split(
            '/\s+/',
            trim($teacherName)
        )
    )
        ->filter()
        ->take(2)
        ->map(
            fn ($part) =>
                strtoupper(
                    substr(
                        $part,
                        0,
                        1
                    )
                )
        )
        ->implode('');

    if (!$teacherInitials) {
        $teacherInitials = 'PW';
    }


    /*
    |--------------------------------------------------------------------------
    | PROGRESS
    |--------------------------------------------------------------------------
    */

    $courseProgress = $enrollment
        ? (float) ($enrollment->progress_percentage ?? 0)
        : 0;

    $courseProgress = min(
        max(
            $courseProgress,
            0
        ),
        100
    );


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
        box-shadow:
            0 1px 3px
            rgba(15, 23, 42, 0.035);
    }

    .pw-soft-card {
        background: #f8fafc;
        border: 1px solid #eef0f4;
        border-radius: 14px;
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
                    href="{{ route('student.marketplace') }}"
                    class="font-medium transition
                           hover:text-violet-600"
                >
                    Marketplace
                </a>

                <span>
                    ›
                </span>

                <span>
                    {{ $course->category?->name ?? 'Course' }}
                </span>

                <span>
                    ›
                </span>

                <span
                    class="font-semibold
                           text-slate-600"
                >
                    {{ $course->title }}
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

                    <p
                        class="text-xs font-bold
                               uppercase
                               tracking-[.12em]
                               text-violet-600"
                    >
                        Course Details
                    </p>


                    <h1
                        class="mt-2 max-w-4xl
                               text-3xl font-bold
                               tracking-tight
                               text-slate-950"
                    >
                        {{ $course->title }}
                    </h1>


                    <p
                        class="mt-2 max-w-3xl
                               text-sm leading-6
                               text-slate-500"
                    >
                        Review the course information,
                        curriculum, instructor, and
                        enrollment options.
                    </p>

                </div>


                <a
                    href="{{ route('student.marketplace') }}"
                    class="inline-flex h-11
                           self-start items-center
                           justify-center gap-2
                           rounded-xl
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

                    Back to Marketplace
                </a>

            </div>



            {{-- =====================================================
                MAIN COURSE HERO
            ====================================================== --}}

            <div
                class="mt-7 grid grid-cols-1
                       gap-6
                       xl:grid-cols-[minmax(0,1fr)_380px]"
            >


                {{-- =================================================
                    COURSE OVERVIEW
                ================================================== --}}

                <section class="pw-card overflow-hidden">


                    {{-- IMAGE --}}
                    <div
                        class="relative h-[280px]
                               overflow-hidden
                               bg-slate-100
                               sm:h-[360px]"
                    >

                        @if($thumbnailUrl)

                            <img
                                src="{{ $thumbnailUrl }}"
                                alt="{{ $course->title }}"
                                class="h-full w-full
                                       object-cover"
                            >

                            <div
                                class="absolute inset-0
                                       bg-gradient-to-t
                                       from-slate-950/50
                                       via-transparent
                                       to-transparent"
                            ></div>

                        @else

                            <div
                                class="flex h-full w-full
                                       items-center
                                       justify-center
                                       bg-gradient-to-br
                                       from-violet-500
                                       via-indigo-500
                                       to-blue-500"
                            >

                                <svg
                                    class="h-20 w-20
                                           text-white/70"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.2"
                                >
                                    <path
                                        d="M4 19.5A2.5 2.5
                                           0 016.5 17H20"
                                    ></path>

                                    <path
                                        d="M6.5 2H20v20H6.5
                                           A2.5 2.5 0 014
                                           19.5v-15A2.5 2.5
                                           0 016.5 2z"
                                    ></path>
                                </svg>

                            </div>

                        @endif



                        {{-- CATEGORY --}}
                        <div
                            class="absolute left-5 top-5
                                   flex flex-wrap gap-2"
                        >

                            <span
                                class="rounded-full
                                       bg-white/95
                                       px-3 py-1.5
                                       text-[10px]
                                       font-bold uppercase
                                       tracking-[.08em]
                                       text-violet-700
                                       shadow-sm
                                       backdrop-blur"
                            >
                                {{
                                    $course->category?->name
                                    ?? 'General'
                                }}
                            </span>


                            <span
                                class="rounded-full
                                       bg-slate-950/70
                                       px-3 py-1.5
                                       text-[10px]
                                       font-semibold
                                       text-white
                                       backdrop-blur"
                            >
                                {{
                                    ucfirst(
                                        $course->difficulty_level
                                        ?? 'beginner'
                                    )
                                }}
                            </span>

                        </div>



                        {{-- PRICE --}}
                        <div
                            class="absolute
                                   bottom-5 right-5"
                        >

                            @if($isFree)

                                <span
                                    class="rounded-full
                                           bg-emerald-500
                                           px-4 py-2
                                           text-xs font-bold
                                           text-white shadow"
                                >
                                    FREE COURSE
                                </span>

                            @else

                                <span
                                    class="rounded-full
                                           bg-white/95
                                           px-4 py-2
                                           text-sm font-bold
                                           text-slate-900
                                           shadow backdrop-blur"
                                >
                                    ₱{{ number_format($price, 2) }}
                                </span>

                            @endif

                        </div>

                    </div>



                    {{-- COURSE DETAILS --}}
                    <div class="p-5 sm:p-7">

                        <div
                            class="flex flex-col gap-5
                                   lg:flex-row
                                   lg:items-start
                                   lg:justify-between"
                        >

                            <div class="max-w-3xl">

                                <p
                                    class="text-xs font-bold
                                           uppercase
                                           tracking-[.1em]
                                           text-violet-500"
                                >
                                    About this course
                                </p>


                                <h2
                                    class="mt-2
                                           text-2xl font-bold
                                           text-slate-900"
                                >
                                    {{ $course->title }}
                                </h2>


                                <p
                                    class="mt-4
                                           whitespace-pre-line
                                           text-sm leading-7
                                           text-slate-500"
                                >
                                    {{
                                        $course->description
                                        ?: 'No course description is currently available.'
                                    }}
                                </p>

                            </div>

                        </div>



                        {{-- INSTRUCTOR --}}
                        <div
                            class="mt-6 flex
                                   items-center gap-3
                                   border-t
                                   border-slate-100
                                   pt-5"
                        >

                            <div
                                class="flex h-11 w-11
                                       shrink-0 items-center
                                       justify-center
                                       rounded-full
                                       bg-violet-100
                                       text-xs font-bold
                                       text-violet-700"
                            >
                                {{ $teacherInitials }}
                            </div>


                            <div>

                                <p
                                    class="text-[10px]
                                           font-semibold
                                           uppercase
                                           tracking-[.08em]
                                           text-slate-400"
                                >
                                    Instructor
                                </p>

                                <p
                                    class="mt-1 text-sm
                                           font-bold
                                           text-slate-800"
                                >
                                    {{ $teacherName }}
                                </p>

                            </div>

                        </div>

                    </div>

                </section>



                {{-- =================================================
                    ENROLLMENT CARD
                ================================================== --}}

                <aside>

                    <section
                        class="pw-card
                               overflow-hidden
                               xl:sticky xl:top-6"
                    >

                        <div
                            class="border-b
                                   border-slate-100
                                   p-5"
                        >

                            <p
                                class="text-xs font-bold
                                       uppercase
                                       tracking-[.1em]
                                       text-violet-500"
                            >
                                Enrollment
                            </p>


                            @if($isFree)

                                <p
                                    class="mt-2
                                           text-3xl font-bold
                                           text-emerald-600"
                                >
                                    Free
                                </p>

                                <p
                                    class="mt-1 text-xs
                                           text-slate-400"
                                >
                                    No payment required
                                </p>

                            @else

                                <p
                                    class="mt-2
                                           text-3xl font-bold
                                           text-slate-900"
                                >
                                    ₱{{ number_format($price, 2) }}
                                </p>

                                <p
                                    class="mt-1 text-xs
                                           text-slate-400"
                                >
                                    One-time course purchase
                                </p>

                            @endif

                        </div>



                        {{-- COURSE INFO --}}
                        <div class="p-5">

                            <div class="space-y-4">


                                {{-- LEVEL --}}
                                <div
                                    class="flex items-center
                                           justify-between"
                                >

                                    <div
                                        class="flex items-center
                                               gap-3"
                                    >

                                        <div
                                            class="flex h-9 w-9
                                                   items-center
                                                   justify-center
                                                   rounded-xl
                                                   bg-violet-50
                                                   text-violet-600"
                                        >
                                            <svg
                                                class="h-4 w-4"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="2"
                                            >
                                                <path
                                                    d="M13 10V3L4 14
                                                       h7v7l9-11h-7z"
                                                ></path>
                                            </svg>
                                        </div>


                                        <span
                                            class="text-xs
                                                   text-slate-500"
                                        >
                                            Difficulty
                                        </span>

                                    </div>


                                    <span
                                        class="text-xs
                                               font-bold
                                               text-slate-700"
                                    >
                                        {{
                                            ucfirst(
                                                $course->difficulty_level
                                                ?? 'Beginner'
                                            )
                                        }}
                                    </span>

                                </div>



                                {{-- HOURS --}}
                                <div
                                    class="flex items-center
                                           justify-between"
                                >

                                    <div
                                        class="flex items-center
                                               gap-3"
                                    >

                                        <div
                                            class="flex h-9 w-9
                                                   items-center
                                                   justify-center
                                                   rounded-xl
                                                   bg-blue-50
                                                   text-blue-600"
                                        >
                                            <svg
                                                class="h-4 w-4"
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
                                                    d="M12 7v5l3 2"
                                                ></path>
                                            </svg>
                                        </div>


                                        <span
                                            class="text-xs
                                                   text-slate-500"
                                        >
                                            Estimated time
                                        </span>

                                    </div>


                                    <span
                                        class="text-xs
                                               font-bold
                                               text-slate-700"
                                    >
                                        @if($course->estimated_hours)

                                            {{
                                                $course->estimated_hours
                                            }}
                                            hrs

                                        @else

                                            Flexible

                                        @endif
                                    </span>

                                </div>



                                {{-- LESSONS --}}
                                <div
                                    class="flex items-center
                                           justify-between"
                                >

                                    <div
                                        class="flex items-center
                                               gap-3"
                                    >

                                        <div
                                            class="flex h-9 w-9
                                                   items-center
                                                   justify-center
                                                   rounded-xl
                                                   bg-emerald-50
                                                   text-emerald-600"
                                        >
                                            <svg
                                                class="h-4 w-4"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="2"
                                            >
                                                <path
                                                    d="M4 19.5A2.5
                                                       2.5 0 016.5
                                                       17H20"
                                                ></path>

                                                <path
                                                    d="M6.5 2H20v20H6.5
                                                       A2.5 2.5 0 014
                                                       19.5v-15A2.5 2.5
                                                       0 016.5 2z"
                                                ></path>
                                            </svg>
                                        </div>


                                        <span
                                            class="text-xs
                                                   text-slate-500"
                                        >
                                            Lessons
                                        </span>

                                    </div>


                                    <span
                                        class="text-xs
                                               font-bold
                                               text-slate-700"
                                    >
                                        {{ $totalLessons }}
                                    </span>

                                </div>



                                {{-- CERTIFICATE --}}
                                <div
                                    class="flex items-center
                                           justify-between"
                                >

                                    <div
                                        class="flex items-center
                                               gap-3"
                                    >

                                        <div
                                            class="flex h-9 w-9
                                                   items-center
                                                   justify-center
                                                   rounded-xl
                                                   bg-orange-50
                                                   text-orange-500"
                                        >
                                            <svg
                                                class="h-4 w-4"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="2"
                                            >
                                                <circle
                                                    cx="12"
                                                    cy="8"
                                                    r="5"
                                                ></circle>

                                                <path
                                                    d="M8.5 12.5
                                                       7 22l5-3
                                                       5 3-1.5-9.5"
                                                ></path>
                                            </svg>
                                        </div>


                                        <span
                                            class="text-xs
                                                   text-slate-500"
                                        >
                                            Certificate
                                        </span>

                                    </div>


                                    <span
                                        class="text-xs
                                               font-bold
                                               {{
                                                   $course->certificate_available
                                                   ? 'text-emerald-600'
                                                   : 'text-slate-500'
                                               }}"
                                    >
                                        {{
                                            $course->certificate_available
                                            ? 'Available'
                                            : 'Not included'
                                        }}
                                    </span>

                                </div>

                            </div>



                            {{-- =========================================
                                ACTION
                            ========================================== --}}

                            @if($isEnrolled)

                                <div
                                    class="mt-6
                                           rounded-2xl
                                           border
                                           border-emerald-100
                                           bg-emerald-50
                                           p-4"
                                >

                                    <div
                                        class="flex items-start
                                               gap-3"
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
                                                class="text-xs
                                                       font-bold
                                                       text-emerald-800"
                                            >
                                                You're enrolled
                                            </p>

                                            <p
                                                class="mt-1
                                                       text-[11px]
                                                       leading-5
                                                       text-emerald-700"
                                            >
                                                Continue your course
                                                whenever you're ready.
                                            </p>

                                        </div>

                                    </div>

                                </div>



                                {{-- PROGRESS --}}
                                <div class="mt-4">

                                    <div
                                        class="flex items-center
                                               justify-between"
                                    >

                                        <span
                                            class="text-xs
                                                   font-semibold
                                                   text-slate-500"
                                        >
                                            Your progress
                                        </span>


                                        <span
                                            class="text-xs
                                                   font-bold
                                                   text-violet-600"
                                        >
                                            {{
                                                number_format(
                                                    $courseProgress,
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
                                                   {{
                                                       $courseProgress >= 100
                                                       ? 'bg-emerald-500'
                                                       : 'bg-violet-600'
                                                   }}"
                                            style="
                                                width:
                                                {{ $courseProgress }}%;
                                            "
                                        ></div>

                                    </div>

                                </div>



                                <a
                                    href="{{ route('student.learn.course', $course) }}"
                                    class="mt-5
                                           inline-flex h-11
                                           w-full items-center
                                           justify-center gap-2
                                           rounded-xl
                                           bg-violet-600
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

                                    Continue Learning
                                </a>



                            @elseif($pendingTransaction)

                                <div
                                    class="mt-6
                                           rounded-2xl
                                           border
                                           border-orange-100
                                           bg-orange-50
                                           p-4"
                                >

                                    <div
                                        class="flex items-start
                                               gap-3"
                                    >

                                        <div
                                            class="flex h-8 w-8
                                                   shrink-0
                                                   items-center
                                                   justify-center
                                                   rounded-full
                                                   bg-orange-100
                                                   text-orange-600"
                                        >
                                            <svg
                                                class="h-4 w-4"
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
                                                    d="M12 7v5l3 2"
                                                ></path>
                                            </svg>
                                        </div>


                                        <div>

                                            <p
                                                class="text-xs
                                                       font-bold
                                                       text-orange-800"
                                            >
                                                Payment pending
                                            </p>

                                            <p
                                                class="mt-1
                                                       text-[11px]
                                                       leading-5
                                                       text-orange-700"
                                            >
                                                Your payment transaction
                                                is currently being processed.
                                            </p>

                                        </div>

                                    </div>

                                </div>



                                <a
                                    href="{{ route('student.transactions.show', $pendingTransaction) }}"
                                    class="mt-4
                                           inline-flex h-11
                                           w-full items-center
                                           justify-center
                                           rounded-xl
                                           bg-orange-500
                                           text-sm font-semibold
                                           text-white
                                           transition
                                           hover:bg-orange-600"
                                >
                                    View Transaction
                                </a>



                            @elseif(!$isFree)

                                <div
                                    class="mt-6
                                           rounded-2xl
                                           border
                                           border-blue-100
                                           bg-blue-50
                                           p-4"
                                >

                                    <p
                                        class="text-xs
                                               font-bold
                                               text-blue-800"
                                    >
                                        Premium course
                                    </p>

                                    <p
                                        class="mt-1
                                               text-[11px]
                                               leading-5
                                               text-blue-700"
                                    >
                                        Purchase this course
                                        to unlock its full
                                        learning content.
                                    </p>

                                </div>



                                <form
                                    action="{{ route('student.transactions.store', $course) }}"
                                    method="POST"
                                    class="mt-4"
                                >
                                    @csrf

                                    <button
                                        type="submit"
                                        class="inline-flex
                                               h-11 w-full
                                               items-center
                                               justify-center gap-2
                                               rounded-xl
                                               bg-violet-600
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
                                            <rect
                                                x="3"
                                                y="5"
                                                width="18"
                                                height="14"
                                                rx="2"
                                            ></rect>

                                            <path d="M3 10h18"></path>
                                        </svg>

                                        Purchase Course
                                    </button>

                                </form>



                            @else

                                <div
                                    class="mt-6
                                           rounded-2xl
                                           border
                                           border-emerald-100
                                           bg-emerald-50
                                           p-4"
                                >

                                    <p
                                        class="text-xs
                                               font-bold
                                               text-emerald-800"
                                    >
                                        Free enrollment
                                    </p>

                                    <p
                                        class="mt-1
                                               text-[11px]
                                               leading-5
                                               text-emerald-700"
                                    >
                                        Enroll now and get
                                        immediate access to
                                        the course.
                                    </p>

                                </div>



                                <form
                                    action="{{ route('student.enroll', $course) }}"
                                    method="POST"
                                    class="mt-4"
                                >
                                    @csrf

                                    <button
                                        type="submit"
                                        class="inline-flex
                                               h-11 w-full
                                               items-center
                                               justify-center gap-2
                                               rounded-xl
                                               bg-violet-600
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
                                                d="M9 11l3 3
                                                   L22 4"
                                            ></path>

                                            <path
                                                d="M21 12v7
                                                   a2 2 0 01-2 2
                                                   H5a2 2 0 01-2-2
                                                   V5a2 2 0 012-2h11"
                                            ></path>
                                        </svg>

                                        Enroll for Free
                                    </button>

                                </form>

                            @endif



                            {{-- INTRO VIDEO --}}
                            @if($course->intro_video)

                                <a
                                    href="{{ $course->intro_video }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="mt-3
                                           inline-flex h-10
                                           w-full items-center
                                           justify-center gap-2
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
                                    <svg
                                        class="h-4 w-4"
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

                                    Watch Course Introduction
                                </a>

                            @endif

                        </div>

                    </section>

                </aside>

            </div>



            {{-- =====================================================
                COURSE STATS
            ====================================================== --}}

            <section
                class="mt-6 grid
                       grid-cols-2 gap-4
                       lg:grid-cols-4"
            >


                {{-- LESSONS --}}
                <div class="pw-card p-5">

                    <p
                        class="text-xs
                               font-semibold
                               text-slate-500"
                    >
                        Lessons
                    </p>


                    <div
                        class="mt-2 flex
                               items-end
                               justify-between"
                    >

                        <p
                            class="text-3xl font-bold
                                   text-slate-900"
                        >
                            {{ $totalLessons }}
                        </p>


                        <div
                            class="flex h-9 w-9
                                   items-center
                                   justify-center
                                   rounded-xl
                                   bg-violet-50
                                   text-violet-600"
                        >
                            <svg
                                class="h-4 w-4"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path
                                    d="M4 19.5A2.5
                                       2.5 0 016.5
                                       17H20"
                                ></path>

                                <path
                                    d="M6.5 2H20v20H6.5
                                       A2.5 2.5 0 014
                                       19.5v-15A2.5 2.5
                                       0 016.5 2z"
                                ></path>
                            </svg>
                        </div>

                    </div>

                </div>



                {{-- DURATION --}}
                <div class="pw-card p-5">

                    <p
                        class="text-xs
                               font-semibold
                               text-slate-500"
                    >
                        Content Duration
                    </p>


                    <div
                        class="mt-2 flex
                               items-end
                               justify-between"
                    >

                        <p
                            class="text-3xl font-bold
                                   text-blue-600"
                        >
                            {{ $totalMinutes }}
                            <span
                                class="text-sm
                                       font-semibold
                                       text-slate-400"
                            >
                                min
                            </span>
                        </p>


                        <div
                            class="flex h-9 w-9
                                   items-center
                                   justify-center
                                   rounded-xl
                                   bg-blue-50
                                   text-blue-600"
                        >
                            <svg
                                class="h-4 w-4"
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
                                    d="M12 7v5l3 2"
                                ></path>
                            </svg>
                        </div>

                    </div>

                </div>



                {{-- PREVIEWS --}}
                <div class="pw-card p-5">

                    <p
                        class="text-xs
                               font-semibold
                               text-slate-500"
                    >
                        Preview Lessons
                    </p>


                    <div
                        class="mt-2 flex
                               items-end
                               justify-between"
                    >

                        <p
                            class="text-3xl font-bold
                                   text-emerald-600"
                        >
                            {{ $previewLessons }}
                        </p>


                        <div
                            class="flex h-9 w-9
                                   items-center
                                   justify-center
                                   rounded-xl
                                   bg-emerald-50
                                   text-emerald-600"
                        >
                            ▶
                        </div>

                    </div>

                </div>



                {{-- LEVEL --}}
                <div class="pw-card p-5">

                    <p
                        class="text-xs
                               font-semibold
                               text-slate-500"
                    >
                        Difficulty
                    </p>


                    <div
                        class="mt-2 flex
                               items-end
                               justify-between"
                    >

                        <p
                            class="text-lg font-bold
                                   text-orange-500"
                        >
                            {{
                                ucfirst(
                                    $course->difficulty_level
                                    ?? 'Beginner'
                                )
                            }}
                        </p>


                        <div
                            class="flex h-9 w-9
                                   items-center
                                   justify-center
                                   rounded-xl
                                   bg-orange-50
                                   text-orange-500"
                        >
                            ⚡
                        </div>

                    </div>

                </div>

            </section>



            {{-- =====================================================
                COURSE CURRICULUM
            ====================================================== --}}

            <section
                class="pw-card mt-6
                       overflow-hidden"
            >

                <div
                    class="flex flex-col gap-3
                           border-b
                           border-slate-100
                           p-5
                           sm:flex-row
                           sm:items-center
                           sm:justify-between
                           sm:p-6"
                >

                    <div>

                        <h2
                            class="text-lg font-bold
                                   text-slate-900"
                        >
                            Course Curriculum
                        </h2>


                        <p
                            class="mt-1 text-xs
                                   text-slate-500"
                        >
                            {{
                                $totalLessons
                            }}

                            {{
                                \Illuminate\Support\Str::plural(
                                    'lesson',
                                    $totalLessons
                                )
                            }}

                            included in this course.
                        </p>

                    </div>


                    @if($isEnrolled)

                        <a
                            href="{{ route('student.learn.course', $course) }}"
                            class="inline-flex h-10
                                   self-start items-center
                                   justify-center
                                   rounded-xl
                                   bg-violet-600
                                   px-4
                                   text-xs font-semibold
                                   text-white
                                   transition
                                   hover:bg-violet-700"
                        >
                            Open Course
                        </a>

                    @endif

                </div>



                @if($lessons->isNotEmpty())

                    <div
                        class="divide-y
                               divide-slate-100"
                    >

                        @foreach($lessons as $lesson)

                            @php
                                $lessonType =
                                    $lesson->lesson_type
                                    ?? 'text';

                                $typeLabel =
                                    $typeLabels[$lessonType]
                                    ?? ucfirst($lessonType);

                                $isPreview =
                                    (bool) $lesson->is_preview;
                            @endphp


                            <div
                                class="flex flex-col gap-4
                                       p-5 transition
                                       hover:bg-slate-50/60
                                       sm:flex-row
                                       sm:items-center
                                       sm:justify-between
                                       sm:p-6"
                            >

                                <div
                                    class="flex min-w-0
                                           items-start gap-4"
                                >


                                    {{-- NUMBER --}}
                                    <div
                                        class="flex h-10 w-10
                                               shrink-0 items-center
                                               justify-center
                                               rounded-xl
                                               {{
                                                   $lessonType === 'quiz'
                                                   ? 'bg-orange-50 text-orange-500'
                                                   : (
                                                       $lessonType === 'video'
                                                       ? 'bg-violet-50 text-violet-600'
                                                       : (
                                                           $lessonType === 'document'
                                                           ? 'bg-blue-50 text-blue-600'
                                                           : 'bg-emerald-50 text-emerald-600'
                                                       )
                                                   )
                                               }}
                                               text-xs font-bold"
                                    >

                                        @if($lessonType === 'video')
                                            ▶
                                        @elseif($lessonType === 'quiz')
                                            ?
                                        @elseif($lessonType === 'document')
                                            ▤
                                        @else
                                            Aa
                                        @endif

                                    </div>



                                    {{-- CONTENT --}}
                                    <div class="min-w-0">

                                        <div
                                            class="flex flex-wrap
                                                   items-center gap-2"
                                        >

                                            <p
                                                class="text-[10px]
                                                       font-bold uppercase
                                                       tracking-[.08em]
                                                       text-slate-400"
                                            >
                                                Lesson
                                                {{ $lesson->lesson_order }}
                                            </p>


                                            @if($isPreview)

                                                <span
                                                    class="rounded-full
                                                           bg-violet-50
                                                           px-2 py-0.5
                                                           text-[9px]
                                                           font-bold
                                                           uppercase
                                                           tracking-wide
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
                                            class="mt-2 flex
                                                   flex-wrap
                                                   items-center
                                                   gap-3
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
                                                    {{
                                                        $lesson
                                                            ->duration_minutes
                                                    }}
                                                    min
                                                </span>

                                            @endif

                                        </div>

                                    </div>

                                </div>



                                {{-- RIGHT SIDE --}}
                                <div
                                    class="flex shrink-0
                                           items-center gap-2
                                           pl-14 sm:pl-0"
                                >

                                    @if($isEnrolled)

                                        <a
                                            href="{{ route('student.lesson.view', $lesson) }}"
                                            class="inline-flex h-9
                                                   items-center
                                                   justify-center
                                                   gap-1.5
                                                   rounded-lg
                                                   border
                                                   border-violet-200
                                                   bg-white
                                                   px-3
                                                   text-xs font-semibold
                                                   text-violet-700
                                                   transition
                                                   hover:bg-violet-50"
                                        >
                                            Open Lesson

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

                                    @elseif($isPreview)

                                        <span
                                            class="inline-flex h-9
                                                   items-center
                                                   justify-center
                                                   rounded-lg
                                                   bg-violet-50
                                                   px-3
                                                   text-xs font-semibold
                                                   text-violet-600"
                                        >
                                            Preview available
                                        </span>

                                    @else

                                        <span
                                            class="inline-flex h-9
                                                   items-center
                                                   justify-center
                                                   gap-1.5
                                                   rounded-lg
                                                   bg-slate-100
                                                   px-3
                                                   text-xs font-semibold
                                                   text-slate-500"
                                        >
                                            <svg
                                                class="h-3.5 w-3.5"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="2"
                                            >
                                                <rect
                                                    x="5"
                                                    y="10"
                                                    width="14"
                                                    height="10"
                                                    rx="2"
                                                ></rect>

                                                <path
                                                    d="M8 10V7
                                                       a4 4 0 018 0v3"
                                                ></path>
                                            </svg>

                                            Locked
                                        </span>

                                    @endif

                                </div>

                            </div>

                        @endforeach

                    </div>


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
                                   bg-violet-50
                                   text-violet-600"
                        >

                            <svg
                                class="h-6 w-6"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path
                                    d="M4 19.5A2.5
                                       2.5 0 016.5
                                       17H20"
                                ></path>

                                <path
                                    d="M6.5 2H20v20H6.5
                                       A2.5 2.5 0 014
                                       19.5v-15A2.5
                                       2.5 0 016.5 2z"
                                ></path>
                            </svg>

                        </div>


                        <h3
                            class="mt-4 text-sm
                                   font-bold
                                   text-slate-800"
                        >
                            No lessons yet
                        </h3>


                        <p
                            class="mt-1 text-xs
                                   text-slate-400"
                        >
                            Course lessons will
                            appear here once added.
                        </p>

                    </div>

                @endif

            </section>



            {{-- =====================================================
                FINAL COURSE INFO
            ====================================================== --}}

            <section
                class="pw-card mt-6 p-5 sm:p-6"
            >

                <div
                    class="grid grid-cols-1
                           gap-6
                           lg:grid-cols-3"
                >


                    {{-- LEARN AT OWN PACE --}}
                    <div
                        class="flex items-start gap-3"
                    >

                        <div
                            class="flex h-10 w-10
                                   shrink-0 items-center
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
                                    d="M12 7v5l3 2"
                                ></path>
                            </svg>
                        </div>


                        <div>

                            <h3
                                class="text-sm
                                       font-bold
                                       text-slate-800"
                            >
                                Learn at your pace
                            </h3>

                            <p
                                class="mt-1 text-xs
                                       leading-5
                                       text-slate-500"
                            >
                                Complete lessons based
                                on your own learning schedule.
                            </p>

                        </div>

                    </div>



                    {{-- MULTIPLE TYPES --}}
                    <div
                        class="flex items-start gap-3"
                    >

                        <div
                            class="flex h-10 w-10
                                   shrink-0 items-center
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
                                <rect
                                    x="3"
                                    y="3"
                                    width="7"
                                    height="7"
                                    rx="1"
                                ></rect>

                                <rect
                                    x="14"
                                    y="3"
                                    width="7"
                                    height="7"
                                    rx="1"
                                ></rect>

                                <rect
                                    x="3"
                                    y="14"
                                    width="7"
                                    height="7"
                                    rx="1"
                                ></rect>

                                <rect
                                    x="14"
                                    y="14"
                                    width="7"
                                    height="7"
                                    rx="1"
                                ></rect>
                            </svg>
                        </div>


                        <div>

                            <h3
                                class="text-sm
                                       font-bold
                                       text-slate-800"
                            >
                                Mixed learning content
                            </h3>

                            <p
                                class="mt-1 text-xs
                                       leading-5
                                       text-slate-500"
                            >
                                Learn through readings,
                                videos, documents, and quizzes.
                            </p>

                        </div>

                    </div>



                    {{-- CERTIFICATE --}}
                    <div
                        class="flex items-start gap-3"
                    >

                        <div
                            class="flex h-10 w-10
                                   shrink-0 items-center
                                   justify-center
                                   rounded-xl
                                   bg-emerald-50
                                   text-emerald-600"
                        >
                            ✓
                        </div>


                        <div>

                            <h3
                                class="text-sm
                                       font-bold
                                       text-slate-800"
                            >
                                {{
                                    $course->certificate_available
                                    ? 'Certificate available'
                                    : 'Track your progress'
                                }}
                            </h3>

                            <p
                                class="mt-1 text-xs
                                       leading-5
                                       text-slate-500"
                            >
                                @if($course->certificate_available)

                                    Complete the course
                                    requirements to earn
                                    your PathWise certificate.

                                @else

                                    Follow your lesson
                                    completion and quiz
                                    performance in PathWise.

                                @endif
                            </p>

                        </div>

                    </div>

                </div>

            </section>

        </div>

    </main>

</div>

</x-layouts::app>