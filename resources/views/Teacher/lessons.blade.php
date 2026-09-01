<x-layouts::app :title="$course->title . ' - Lessons'">

@php
    $totalLessons = $lessons->count();

    $publishedLessons =
        $lessons->where('is_published', true)->count();

    $draftLessons =
        $totalLessons - $publishedLessons;

    $previewLessons =
        $lessons->where('is_preview', true)->count();


    /*
    |--------------------------------------------------------------------------
    | COURSE COVER
    |--------------------------------------------------------------------------
    */

    $courseThumbnail =
        $course->thumbnail ?? null;

    $courseThumbnailUrl = null;

    if ($courseThumbnail) {

        if (
            \Illuminate\Support\Str::startsWith(
                $courseThumbnail,
                ['http://', 'https://']
            )
        ) {
            $courseThumbnailUrl =
                $courseThumbnail;
        } else {
            $courseThumbnailUrl =
                asset(
                    'storage/' .
                    ltrim($courseThumbnail, '/')
                );
        }
    }
@endphp


<style>
    .pw-lesson-row {
        transition:
            border-color 160ms ease,
            background-color 160ms ease,
            box-shadow 160ms ease;
    }

    .pw-lesson-row:hover {
        border-color: #ddd6fe;
        box-shadow: 0 8px 25px rgba(76, 29, 149, .05);
    }

    .pw-lesson-hidden {
        display: none !important;
    }
</style>


<div class="min-h-screen bg-[#f8f9fc]">

    <main class="px-5 py-7 sm:px-6 lg:px-8 lg:py-9">

        <div class="mx-auto max-w-[1500px]">


            {{-- =========================================================
                BREADCRUMB
            ========================================================== --}}

            <div
                class="mb-5 flex flex-wrap items-center
                       gap-2 text-xs text-slate-400"
            >

                <a
                    href="{{ route('teacher.my-courses') }}"
                    class="font-medium hover:text-violet-600"
                >
                    My Courses
                </a>

                <span>›</span>

                <a
                    href="{{ route('teacher.lessons.index') }}"
                    class="font-medium hover:text-violet-600"
                >
                    Lessons
                </a>

                <span>›</span>

                <span class="font-semibold text-slate-600">
                    {{ $course->title }}
                </span>

            </div>



            {{-- =========================================================
                HEADER
            ========================================================== --}}

            <div
                class="flex flex-col gap-5
                       lg:flex-row lg:items-end
                       lg:justify-between"
            >

                <div>

                    <p
                        class="text-xs font-bold uppercase
                               tracking-[.12em] text-violet-600"
                    >
                        Lesson Manager
                    </p>

                    <h1
                        class="mt-2 text-3xl font-bold
                               tracking-tight text-slate-950"
                    >
                        Lessons
                    </h1>

                    <p class="mt-2 text-sm text-slate-500">
                        Build and organize the learning journey for
                        <span class="font-semibold text-slate-700">
                            {{ $course->title }}
                        </span>.
                    </p>

                </div>


                <a
                    href="{{ route('teacher.lessons.create', $course) }}"
                    class="inline-flex h-11 items-center
                           justify-center gap-2 self-start
                           rounded-xl bg-gradient-to-r
                           from-violet-600 to-indigo-600
                           px-5 text-sm font-semibold
                           text-white shadow-md
                           shadow-violet-200 transition
                           hover:-translate-y-0.5
                           hover:shadow-lg"
                >

                    <svg
                        class="h-4.5 w-4.5"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path d="M12 5v14M5 12h14"></path>
                    </svg>

                    Add Lesson

                </a>

            </div>



            {{-- =========================================================
                SUCCESS MESSAGE
            ========================================================== --}}

            @if(session('success'))

                <div
                    class="mt-6 rounded-2xl border
                           border-emerald-200 bg-emerald-50
                           px-5 py-4 text-sm font-medium
                           text-emerald-700"
                >
                    ✓ {{ session('success') }}
                </div>

            @endif



            {{-- =========================================================
                COURSE SUMMARY
            ========================================================== --}}

            <section
                class="mt-7 overflow-hidden rounded-2xl
                       border border-slate-200 bg-white
                       shadow-sm"
            >

                <div
                    class="flex flex-col md:flex-row"
                >

                    {{-- Course thumbnail --}}
                    <div
                        class="relative h-[190px]
                               w-full shrink-0 overflow-hidden
                               bg-slate-100 md:h-auto
                               md:w-[290px]"
                    >

                        @if($courseThumbnailUrl)

                            <img
                                src="{{ $courseThumbnailUrl }}"
                                alt="{{ $course->title }}"
                                class="h-full w-full object-cover"
                            >

                        @else

                            <div
                                class="relative flex h-full min-h-[180px]
                                       w-full items-center justify-center
                                       overflow-hidden
                                       bg-gradient-to-br
                                       from-violet-600
                                       via-indigo-600
                                       to-blue-600"
                            >

                                <div
                                    class="absolute -right-12
                                           -top-12 h-40 w-40
                                           rounded-full border-[28px]
                                           border-white/10"
                                ></div>


                                <div
                                    class="flex h-16 w-16
                                           items-center justify-center
                                           rounded-2xl bg-white/15
                                           text-white backdrop-blur"
                                >
                                    <svg
                                        class="h-8 w-8"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.8"
                                    >
                                        <path
                                            d="M12 6.042A8.967 8.967
                                               0 006 3.75c-1.052 0
                                               -2.062.18-3
                                               .512v14.25A8.987
                                               8.987 0 016
                                               18c2.305 0
                                               4.408.867 6
                                               2.292m0-14.25a8.966
                                               8.966 0 016-2.292"
                                        />
                                    </svg>
                                </div>

                            </div>

                        @endif

                    </div>



                    {{-- Course info --}}
                    <div class="flex-1 p-5 sm:p-6">

                        <div
                            class="flex flex-col gap-5
                                   lg:flex-row
                                   lg:items-start
                                   lg:justify-between"
                        >

                            <div class="max-w-2xl">

                                <div
                                    class="flex flex-wrap
                                           items-center gap-2"
                                >

                                    <span
                                        class="text-xs font-semibold
                                               text-violet-600"
                                    >
                                        {{
                                            $course->category->name
                                            ?? 'Course'
                                        }}
                                    </span>


                                    @if($course->difficulty_level)

                                        <span class="text-slate-300">
                                            •
                                        </span>

                                        <span
                                            class="text-xs font-medium
                                                   text-slate-500"
                                        >
                                            {{
                                                ucfirst(
                                                    $course->difficulty_level
                                                )
                                            }}
                                        </span>

                                    @endif

                                </div>


                                <h2
                                    class="mt-2 text-xl font-bold
                                           text-slate-950"
                                >
                                    {{ $course->title }}
                                </h2>


                                <p
                                    class="mt-2 line-clamp-2
                                           text-sm leading-6
                                           text-slate-500"
                                >
                                    {{
                                        $course->description
                                        ?: 'Organize the learning content for this course.'
                                    }}
                                </p>

                            </div>



                            <div
                                class="flex shrink-0
                                       items-center gap-2"
                            >

                                @if($course->status === 'published')

                                    <span
                                        class="inline-flex items-center
                                               gap-1.5 rounded-full
                                               bg-emerald-50 px-3
                                               py-1.5 text-xs
                                               font-semibold
                                               text-emerald-700"
                                    >
                                        <span
                                            class="h-1.5 w-1.5
                                                   rounded-full
                                                   bg-emerald-500"
                                        ></span>

                                        Published
                                    </span>

                                @else

                                    <span
                                        class="inline-flex items-center
                                               gap-1.5 rounded-full
                                               bg-slate-100 px-3
                                               py-1.5 text-xs
                                               font-semibold
                                               text-slate-600"
                                    >
                                        {{ ucfirst($course->status ?? 'Draft') }}
                                    </span>

                                @endif

                            </div>

                        </div>



                        {{-- stats --}}
                        <div
                            class="mt-6 grid grid-cols-2
                                   gap-3 sm:grid-cols-4"
                        >

                            <div
                                class="rounded-xl bg-violet-50
                                       px-4 py-3"
                            >
                                <p
                                    class="text-[10px] font-bold
                                           uppercase tracking-wide
                                           text-violet-500"
                                >
                                    Lessons
                                </p>

                                <p
                                    class="mt-1 text-xl font-bold
                                           text-violet-700"
                                >
                                    {{ $totalLessons }}
                                </p>
                            </div>


                            <div
                                class="rounded-xl bg-emerald-50
                                       px-4 py-3"
                            >
                                <p
                                    class="text-[10px] font-bold
                                           uppercase tracking-wide
                                           text-emerald-500"
                                >
                                    Published
                                </p>

                                <p
                                    class="mt-1 text-xl font-bold
                                           text-emerald-700"
                                >
                                    {{ $publishedLessons }}
                                </p>
                            </div>


                            <div
                                class="rounded-xl bg-orange-50
                                       px-4 py-3"
                            >
                                <p
                                    class="text-[10px] font-bold
                                           uppercase tracking-wide
                                           text-orange-500"
                                >
                                    Draft
                                </p>

                                <p
                                    class="mt-1 text-xl font-bold
                                           text-orange-600"
                                >
                                    {{ $draftLessons }}
                                </p>
                            </div>


                            <div
                                class="rounded-xl bg-blue-50
                                       px-4 py-3"
                            >
                                <p
                                    class="text-[10px] font-bold
                                           uppercase tracking-wide
                                           text-blue-500"
                                >
                                    Previews
                                </p>

                                <p
                                    class="mt-1 text-xl font-bold
                                           text-blue-700"
                                >
                                    {{ $previewLessons }}
                                </p>
                            </div>

                        </div>

                    </div>

                </div>

            </section>



            {{-- =========================================================
                SEARCH / FILTER
            ========================================================== --}}

            <section
                class="mt-6 rounded-2xl border
                       border-slate-200 bg-white
                       p-4 shadow-sm"
            >

                <div
                    class="flex flex-col gap-3
                           lg:flex-row lg:items-center"
                >

                    <div class="relative flex-1">

                        <svg
                            class="absolute left-4 top-1/2
                                   h-4.5 w-4.5
                                   -translate-y-1/2
                                   text-slate-400"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <circle cx="11" cy="11" r="7"></circle>
                            <path d="m20 20-3.5-3.5"></path>
                        </svg>


                        <input
                            id="lessonSearch"
                            type="text"
                            placeholder="Search lessons..."
                            class="h-11 w-full rounded-xl
                                   border border-slate-200
                                   bg-white pl-11 pr-4
                                   text-sm outline-none
                                   focus:border-violet-300
                                   focus:ring-4
                                   focus:ring-violet-100"
                        >

                    </div>



                    <select
                        id="lessonTypeFilter"
                        class="h-11 rounded-xl
                               border border-slate-200
                               bg-white px-4 text-sm
                               text-slate-600 outline-none
                               focus:border-violet-300
                               focus:ring-4
                               focus:ring-violet-100"
                    >
                        <option value="all">
                            All Types
                        </option>

                        <option value="video">
                            Video
                        </option>

                        <option value="text">
                            Reading / Text
                        </option>

                        <option value="document">
                            Document
                        </option>

                        <option value="quiz">
                            Quiz
                        </option>
                    </select>



                    <select
                        id="lessonStatusFilter"
                        class="h-11 rounded-xl
                               border border-slate-200
                               bg-white px-4 text-sm
                               text-slate-600 outline-none
                               focus:border-violet-300
                               focus:ring-4
                               focus:ring-violet-100"
                    >
                        <option value="all">
                            All Status
                        </option>

                        <option value="published">
                            Published
                        </option>

                        <option value="draft">
                            Draft
                        </option>
                    </select>

                </div>

            </section>



            {{-- =========================================================
                LESSON LIST
            ========================================================== --}}

            <section class="mt-5 space-y-3" id="lessonList">

                @forelse($lessons as $lesson)

                    @php
                        $type =
                            strtolower(
                                $lesson->lesson_type ?? 'text'
                            );

                        /*
                        |--------------------------------------------------------------------------
                        | YOUTUBE THUMBNAIL
                        |--------------------------------------------------------------------------
                        */

                        $youtubeThumbnail = null;

                        if (
                            $type === 'video'
                            &&
                            !empty($lesson->video_url)
                        ) {

                            $url =
                                $lesson->video_url;

                            $videoId = null;

                            if (
                                preg_match(
                                    '/youtu\.be\/([^?&]+)/',
                                    $url,
                                    $matches
                                )
                            ) {
                                $videoId =
                                    $matches[1];

                            } elseif (
                                preg_match(
                                    '/[?&]v=([^?&]+)/',
                                    $url,
                                    $matches
                                )
                            ) {
                                $videoId =
                                    $matches[1];

                            } elseif (
                                preg_match(
                                    '/youtube\.com\/embed\/([^?&]+)/',
                                    $url,
                                    $matches
                                )
                            ) {
                                $videoId =
                                    $matches[1];
                            }

                            if ($videoId) {

                                $youtubeThumbnail =
                                    'https://img.youtube.com/vi/'
                                    . $videoId
                                    . '/hqdefault.jpg';
                            }
                        }


                        $typeStyle = match($type) {
                            'video' =>
                                [
                                    'bg' => 'bg-violet-50',
                                    'text' => 'text-violet-600',
                                    'label' => 'Video',
                                ],

                            'document' =>
                                [
                                    'bg' => 'bg-teal-50',
                                    'text' => 'text-teal-600',
                                    'label' => 'Document',
                                ],

                            'quiz' =>
                                [
                                    'bg' => 'bg-orange-50',
                                    'text' => 'text-orange-600',
                                    'label' => 'Quiz',
                                ],

                            default =>
                                [
                                    'bg' => 'bg-blue-50',
                                    'text' => 'text-blue-600',
                                    'label' => 'Reading',
                                ],
                        };
                    @endphp


                    <article
                        data-lesson-row
                        data-title="{{ strtolower($lesson->title) }}"
                        data-content="{{ strtolower(strip_tags($lesson->content ?? '')) }}"
                        data-type="{{ $type }}"
                        data-status="{{ $lesson->is_published ? 'published' : 'draft' }}"
                        class="pw-lesson-row rounded-2xl
                               border border-slate-200
                               bg-white p-4 shadow-sm
                               sm:p-5"
                    >

                        <div
                            class="flex flex-col gap-4
                                   lg:flex-row lg:items-center"
                        >


                            {{-- DRAG / ORDER --}}
                            <div
                                class="hidden shrink-0
                                       items-center gap-3
                                       lg:flex"
                            >

                                <div
                                    class="flex w-8
                                           justify-center
                                           text-slate-300"
                                    title="Lesson order"
                                >
                                    <svg
                                        class="h-5 w-5"
                                        viewBox="0 0 24 24"
                                        fill="currentColor"
                                    >
                                        <circle cx="8" cy="5" r="1.2"></circle>
                                        <circle cx="16" cy="5" r="1.2"></circle>
                                        <circle cx="8" cy="12" r="1.2"></circle>
                                        <circle cx="16" cy="12" r="1.2"></circle>
                                        <circle cx="8" cy="19" r="1.2"></circle>
                                        <circle cx="16" cy="19" r="1.2"></circle>
                                    </svg>
                                </div>


                                <div
                                    class="flex h-10 w-10
                                           items-center justify-center
                                           rounded-full
                                           bg-slate-50
                                           text-sm font-bold
                                           text-slate-600"
                                >
                                    {{ $lesson->lesson_order }}
                                </div>

                            </div>



                            {{-- TYPE ICON --}}
                            <div
                                class="flex h-14 w-14 shrink-0
                                       items-center justify-center
                                       rounded-2xl
                                       {{ $typeStyle['bg'] }}
                                       {{ $typeStyle['text'] }}"
                            >

                                @if($type === 'video')

                                    <svg
                                        class="h-6 w-6"
                                        viewBox="0 0 24 24"
                                        fill="currentColor"
                                    >
                                        <path d="M8 5v14l11-7z"></path>
                                    </svg>

                                @elseif($type === 'quiz')

                                    <svg
                                        class="h-6 w-6"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                    >
                                        <circle cx="12" cy="12" r="9"></circle>
                                        <path d="M9.5 9a2.6 2.6 0 015 1c0 2-2.5 2-2.5 4"></path>
                                        <path d="M12 17h.01"></path>
                                    </svg>

                                @elseif($type === 'document')

                                    <svg
                                        class="h-6 w-6"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                    >
                                        <path d="M6 2h8l4 4v16H6z"></path>
                                        <path d="M14 2v5h5"></path>
                                    </svg>

                                @else

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

                                @endif

                            </div>



                            {{-- LESSON INFO --}}
                            <div class="min-w-0 flex-1">

                                <div
                                    class="flex flex-wrap
                                           items-center gap-2"
                                >

                                    <span
                                        class="text-[10px]
                                               font-bold uppercase
                                               tracking-wide
                                               {{ $typeStyle['text'] }}"
                                    >
                                        {{ $typeStyle['label'] }}
                                    </span>


                                    @if($lesson->is_preview)

                                        <span
                                            class="rounded-full
                                                   bg-blue-50 px-2
                                                   py-0.5 text-[10px]
                                                   font-semibold
                                                   text-blue-600"
                                        >
                                            Free Preview
                                        </span>

                                    @endif

                                </div>


                                <h3
                                    class="mt-1.5 text-[16px]
                                           font-bold text-slate-900"
                                >
                                    {{ $lesson->title }}
                                </h3>


                                @if($lesson->content)

                                    <p
                                        class="mt-1 line-clamp-2
                                               text-xs leading-5
                                               text-slate-500"
                                    >
                                        {{
                                            \Illuminate\Support\Str::limit(
                                                strip_tags($lesson->content),
                                                140
                                            )
                                        }}
                                    </p>

                                @else

                                    <p
                                        class="mt-1 text-xs
                                               text-slate-400"
                                    >
                                        No lesson description added.
                                    </p>

                                @endif

                            </div>



                            {{-- MEDIA PREVIEW --}}
                            <div
                                class="hidden h-[76px] w-[135px]
                                       shrink-0 overflow-hidden
                                       rounded-xl border
                                       border-slate-100
                                       bg-slate-50 xl:block"
                            >

                                @if($youtubeThumbnail)

                                    <div class="relative h-full w-full">

                                        <img
                                            src="{{ $youtubeThumbnail }}"
                                            alt="{{ $lesson->title }}"
                                            class="h-full w-full
                                                   object-cover"
                                        >

                                        <div
                                            class="absolute inset-0
                                                   flex items-center
                                                   justify-center
                                                   bg-black/10"
                                        >
                                            <div
                                                class="flex h-8 w-8
                                                       items-center
                                                       justify-center
                                                       rounded-full
                                                       bg-white/95
                                                       text-violet-600
                                                       shadow"
                                            >
                                                ▶
                                            </div>
                                        </div>

                                    </div>

                                @elseif($type === 'document')

                                    <div
                                        class="flex h-full
                                               items-center
                                               justify-center
                                               bg-teal-50
                                               text-teal-500"
                                    >
                                        <svg
                                            class="h-8 w-8"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="1.7"
                                        >
                                            <path d="M6 2h8l4 4v16H6z"></path>
                                            <path d="M14 2v5h5"></path>
                                        </svg>
                                    </div>

                                @elseif($type === 'quiz')

                                    <div
                                        class="flex h-full
                                               items-center
                                               justify-center
                                               bg-orange-50
                                               text-orange-500"
                                    >
                                        <span
                                            class="text-3xl
                                                   font-black"
                                        >
                                            ?
                                        </span>
                                    </div>

                                @else

                                    <div
                                        class="flex h-full
                                               items-center
                                               justify-center
                                               bg-blue-50
                                               text-blue-500"
                                    >
                                        <svg
                                            class="h-8 w-8"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="1.7"
                                        >
                                            <path d="M4 19.5A2.5 2.5 0 016.5 17H20"></path>
                                            <path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"></path>
                                        </svg>
                                    </div>

                                @endif

                            </div>



                            {{-- META --}}
                            <div
                                class="flex shrink-0
                                       items-center gap-4
                                       lg:min-w-[180px]"
                            >

                                <div>

                                    @if($lesson->duration_minutes)

                                        <p
                                            class="text-xs font-medium
                                                   text-slate-600"
                                        >
                                            {{ $lesson->duration_minutes }}
                                            min
                                        </p>

                                    @else

                                        <p
                                            class="text-xs
                                                   text-slate-400"
                                        >
                                            —
                                        </p>

                                    @endif


                                    @if($lesson->is_published)

                                        <p
                                            class="mt-1 flex
                                                   items-center gap-1
                                                   text-[10px]
                                                   font-semibold
                                                   text-emerald-600"
                                        >
                                            <span
                                                class="h-1.5 w-1.5
                                                       rounded-full
                                                       bg-emerald-500"
                                            ></span>

                                            Published
                                        </p>

                                    @else

                                        <p
                                            class="mt-1 flex
                                                   items-center gap-1
                                                   text-[10px]
                                                   font-semibold
                                                   text-slate-400"
                                        >
                                            <span
                                                class="h-1.5 w-1.5
                                                       rounded-full
                                                       bg-slate-400"
                                            ></span>

                                            Draft
                                        </p>

                                    @endif

                                </div>

                            </div>



                            {{-- ACTIONS --}}
                            <div
                                class="flex shrink-0
                                       items-center gap-2"
                            >

                                <a
                                    href="{{ route('teacher.lessons.edit', $lesson) }}"
                                    class="inline-flex h-10
                                           items-center justify-center
                                           rounded-xl border
                                           border-violet-200
                                           px-4 text-xs
                                           font-semibold
                                           text-violet-700
                                           transition
                                           hover:bg-violet-50"
                                >
                                    Edit
                                </a>


                                <form
                                    action="{{ route('teacher.lessons.delete', $lesson) }}"
                                    method="POST"
                                    onsubmit="
                                        return confirm(
                                            'Delete this lesson? This action cannot be undone.'
                                        )
                                    "
                                >
                                    @csrf
                                    @method('DELETE')

                                    <button
                                        type="submit"
                                        class="flex h-10 w-10
                                               items-center
                                               justify-center
                                               rounded-xl
                                               border
                                               border-slate-200
                                               text-slate-400
                                               transition
                                               hover:border-red-200
                                               hover:bg-red-50
                                               hover:text-red-500"
                                        title="Delete lesson"
                                    >
                                        <svg
                                            class="h-4 w-4"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                        >
                                            <path d="M3 6h18"></path>
                                            <path d="M8 6V4h8v2"></path>
                                            <path d="M19 6l-1 15H6L5 6"></path>
                                            <path d="M10 11v6M14 11v6"></path>
                                        </svg>
                                    </button>

                                </form>

                            </div>

                        </div>

                    </article>


                @empty

                    <div
                        class="rounded-3xl border-2
                               border-dashed border-slate-200
                               bg-white px-6 py-16
                               text-center"
                    >

                        <div
                            class="mx-auto flex h-16 w-16
                                   items-center justify-center
                                   rounded-2xl bg-violet-50
                                   text-violet-600"
                        >
                            <svg
                                class="h-8 w-8"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path d="M12 5v14M5 12h14"></path>
                            </svg>
                        </div>


                        <h3
                            class="mt-5 text-lg font-bold
                                   text-slate-900"
                        >
                            No lessons yet
                        </h3>


                        <p
                            class="mx-auto mt-2 max-w-md
                                   text-sm leading-6
                                   text-slate-500"
                        >
                            Start building this course by adding
                            your first video, reading, document,
                            or quiz lesson.
                        </p>


                        <a
                            href="{{ route('teacher.lessons.create', $course) }}"
                            class="mt-6 inline-flex h-11
                                   items-center gap-2
                                   rounded-xl bg-violet-600
                                   px-5 text-sm font-semibold
                                   text-white hover:bg-violet-700"
                        >
                            + Add First Lesson
                        </a>

                    </div>

                @endforelse

            </section>



            {{-- NO FILTER RESULTS --}}
            <div
                id="noLessonResults"
                class="mt-5 hidden rounded-2xl
                       border-2 border-dashed
                       border-slate-200 bg-white
                       py-12 text-center"
            >
                <p class="text-sm font-semibold text-slate-700">
                    No lessons match your filters.
                </p>

                <p class="mt-1 text-xs text-slate-400">
                    Try changing the search, type, or status.
                </p>
            </div>



            {{-- ADD LESSON FOOTER --}}
            @if($totalLessons > 0)

                <a
                    href="{{ route('teacher.lessons.create', $course) }}"
                    class="mt-5 flex min-h-[76px]
                           items-center justify-center
                           rounded-2xl border-2
                           border-dashed border-violet-200
                           bg-violet-50/30
                           text-sm font-semibold
                           text-violet-600 transition
                           hover:border-violet-300
                           hover:bg-violet-50"
                >
                    + Add another lesson
                </a>

            @endif

        </div>

    </main>

</div>


<script>

    function initializeLessonManager() {

        const search =
            document.getElementById(
                'lessonSearch'
            );

        const typeFilter =
            document.getElementById(
                'lessonTypeFilter'
            );

        const statusFilter =
            document.getElementById(
                'lessonStatusFilter'
            );

        const list =
            document.getElementById(
                'lessonList'
            );

        const empty =
            document.getElementById(
                'noLessonResults'
            );


        if (!list) return;


        function filterLessons() {

            const query =
                search
                    ? search.value
                        .toLowerCase()
                        .trim()
                    : '';

            const type =
                typeFilter
                    ? typeFilter.value
                    : 'all';

            const status =
                statusFilter
                    ? statusFilter.value
                    : 'all';


            const rows =
                list.querySelectorAll(
                    '[data-lesson-row]'
                );

            let visible = 0;


            rows.forEach(function(row) {

                const haystack =
                    (row.dataset.title || '')
                    +
                    ' '
                    +
                    (row.dataset.content || '');

                const matchesSearch =
                    !query
                    ||
                    haystack.includes(query);

                const matchesType =
                    type === 'all'
                    ||
                    row.dataset.type === type;

                const matchesStatus =
                    status === 'all'
                    ||
                    row.dataset.status === status;


                const show =
                    matchesSearch
                    &&
                    matchesType
                    &&
                    matchesStatus;


                row.classList.toggle(
                    'pw-lesson-hidden',
                    !show
                );


                if (show) {
                    visible++;
                }

            });


            if (empty) {

                empty.classList.toggle(
                    'hidden',
                    visible !== 0
                    ||
                    rows.length === 0
                );

            }

        }


        if (search) {
            search.oninput =
                filterLessons;
        }

        if (typeFilter) {
            typeFilter.onchange =
                filterLessons;
        }

        if (statusFilter) {
            statusFilter.onchange =
                filterLessons;
        }


        filterLessons();

    }


    document.addEventListener(
        'DOMContentLoaded',
        initializeLessonManager
    );


    document.addEventListener(
        'livewire:navigated',
        initializeLessonManager
    );

</script>

</x-layouts::app>