<x-layouts::app :title="'Lessons'">

@php
    $totalLessons = $courses->sum(fn ($course) => $course->lessons->count());

    $publishedLessons = $courses->sum(function ($course) {
        return $course->lessons->where('is_published', true)->count();
    });

    $draftLessons = $totalLessons - $publishedLessons;
@endphp

<style>
    .pw-course-card {
        transition: transform 180ms ease, box-shadow 180ms ease, border-color 180ms ease;
    }

    .pw-course-card:hover {
        transform: translateY(-3px);
        border-color: #ddd6fe;
        box-shadow: 0 18px 45px rgba(76, 29, 149, .08);
    }

    .pw-course-card:hover .pw-course-cover {
        transform: scale(1.035);
    }

    .pw-course-cover {
        transition: transform 350ms ease;
    }
</style>


<div class="min-h-screen bg-[#f8f9fc]">

    <main class="px-5 py-7 sm:px-6 lg:px-8 lg:py-9">

        <div class="mx-auto max-w-[1500px]">


            {{-- HEADER --}}
            <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">

                <div>

                    <p class="text-xs font-bold uppercase tracking-[.12em] text-violet-600">
                        Course Content
                    </p>

                    <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-950">
                        Lessons
                    </h1>

                    <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">
                        Select a course to organize its videos, readings,
                        documents, quizzes, and other learning materials.
                    </p>

                </div>


                <div class="relative w-full lg:w-[320px]">

                    <svg
                        class="absolute left-4 top-1/2 h-4.5 w-4.5 -translate-y-1/2 text-slate-400"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <circle cx="11" cy="11" r="7"></circle>
                        <path d="m20 20-3.5-3.5"></path>
                    </svg>

                    <input
                        id="courseSearch"
                        type="text"
                        placeholder="Search your courses..."
                        class="h-11 w-full rounded-xl border border-slate-200
                               bg-white pl-11 pr-4 text-sm outline-none
                               transition placeholder:text-slate-400
                               focus:border-violet-300 focus:ring-4
                               focus:ring-violet-100"
                    >

                </div>

            </div>



            {{-- STATS --}}
            <section class="mt-7 grid grid-cols-2 gap-4 lg:grid-cols-4">

                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold text-slate-500">
                        Courses
                    </p>

                    <p class="mt-2 text-3xl font-bold text-slate-950">
                        {{ $courses->count() }}
                    </p>

                    <p class="mt-1 text-xs text-slate-400">
                        Available to manage
                    </p>
                </div>


                <div class="rounded-2xl border border-violet-100 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold text-slate-500">
                        Total Lessons
                    </p>

                    <p class="mt-2 text-3xl font-bold text-violet-600">
                        {{ $totalLessons }}
                    </p>

                    <p class="mt-1 text-xs text-slate-400">
                        Across all courses
                    </p>
                </div>


                <div class="rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold text-slate-500">
                        Published
                    </p>

                    <p class="mt-2 text-3xl font-bold text-emerald-600">
                        {{ $publishedLessons }}
                    </p>

                    <p class="mt-1 text-xs text-slate-400">
                        Visible to students
                    </p>
                </div>


                <div class="rounded-2xl border border-orange-100 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold text-slate-500">
                        Draft
                    </p>

                    <p class="mt-2 text-3xl font-bold text-orange-500">
                        {{ $draftLessons }}
                    </p>

                    <p class="mt-1 text-xs text-slate-400">
                        Still being prepared
                    </p>
                </div>

            </section>



            {{-- COURSE CARDS --}}
            <section class="mt-7">

                <div
                    id="courseGrid"
                    class="grid grid-cols-1 gap-5 md:grid-cols-2 2xl:grid-cols-3"
                >

                    @forelse($courses as $course)

                        @php
                            $thumbnail = $course->thumbnail ?? null;

                            $thumbnailUrl = null;

                            if ($thumbnail) {
                                if (\Illuminate\Support\Str::startsWith($thumbnail, ['http://', 'https://'])) {
                                    $thumbnailUrl = $thumbnail;
                                } else {
                                    $thumbnailUrl = asset('storage/' . ltrim($thumbnail, '/'));
                                }
                            }

                            $lessonCount = $course->lessons->count();

                            $publishedCount = $course->lessons
                                ->where('is_published', true)
                                ->count();

                            $completion =
                                $lessonCount > 0
                                    ? round(($publishedCount / $lessonCount) * 100)
                                    : 0;
                        @endphp


                        <article
                            data-course-card
                            data-title="{{ strtolower($course->title) }}"
                            data-description="{{ strtolower($course->description ?? '') }}"
                            class="pw-course-card overflow-hidden rounded-2xl
                                   border border-slate-200 bg-white"
                        >

                            {{-- COVER --}}
                            <div class="relative h-[180px] overflow-hidden bg-slate-100">

                                @if($thumbnailUrl)

                                    <img
                                        src="{{ $thumbnailUrl }}"
                                        alt="{{ $course->title }}"
                                        class="pw-course-cover h-full w-full object-cover"
                                    >

                                @else

                                    <div
                                        class="pw-course-cover relative flex h-full w-full
                                               items-center justify-center overflow-hidden
                                               bg-gradient-to-br from-violet-600
                                               via-indigo-600 to-blue-600"
                                    >

                                        <div
                                            class="absolute -right-12 -top-12 h-40 w-40
                                                   rounded-full border-[28px] border-white/10"
                                        ></div>

                                        <div
                                            class="absolute -bottom-14 -left-10 h-44 w-44
                                                   rounded-full bg-white/10"
                                        ></div>


                                        <div class="relative text-center text-white">

                                            <div
                                                class="mx-auto flex h-14 w-14 items-center
                                                       justify-center rounded-2xl bg-white/15
                                                       backdrop-blur"
                                            >
                                                <svg
                                                    class="h-7 w-7"
                                                    viewBox="0 0 24 24"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    stroke-width="1.8"
                                                >
                                                    <path
                                                        d="M4 19.5A2.5 2.5 0 016.5 17H20
                                                           M6.5 2H20v20H6.5A2.5 2.5 0
                                                           014 19.5v-15A2.5 2.5 0 016.5 2z"
                                                    />
                                                </svg>
                                            </div>

                                            <p
                                                class="mt-3 text-[10px] font-bold uppercase
                                                       tracking-[.18em] text-white/75"
                                            >
                                                PathWise Learning
                                            </p>

                                        </div>

                                    </div>

                                @endif


                                <div class="absolute left-3 top-3">

                                    <span
                                        class="rounded-full bg-white/95 px-3 py-1.5
                                               text-[10px] font-bold text-violet-700
                                               shadow-sm"
                                    >
                                        {{ $lessonCount }}
                                        {{ \Illuminate\Support\Str::plural('Lesson', $lessonCount) }}
                                    </span>

                                </div>

                            </div>



                            {{-- CONTENT --}}
                            <div class="p-5">

                                <p class="text-xs font-semibold text-violet-600">
                                    {{ $course->category->name ?? 'Course' }}
                                </p>

                                <h2
                                    class="mt-2 line-clamp-2 text-[17px]
                                           font-bold leading-6 text-slate-900"
                                >
                                    {{ $course->title }}
                                </h2>


                                <p
                                    class="mt-2 line-clamp-2 min-h-[44px]
                                           text-sm leading-[22px] text-slate-500"
                                >
                                    {{
                                        $course->description
                                        ?: 'Organize and manage the learning content for this course.'
                                    }}
                                </p>



                                {{-- LESSON PROGRESS --}}
                                <div class="mt-5">

                                    <div
                                        class="mb-2 flex items-center
                                               justify-between text-xs"
                                    >

                                        <span class="font-medium text-slate-500">
                                            Content readiness
                                        </span>

                                        <span class="font-bold text-slate-700">
                                            {{ $completion }}%
                                        </span>

                                    </div>


                                    <div
                                        class="h-2 overflow-hidden
                                               rounded-full bg-slate-100"
                                    >
                                        <div
                                            class="h-full rounded-full bg-violet-600"
                                            style="width: {{ $completion }}%"
                                        ></div>
                                    </div>


                                    <div
                                        class="mt-3 flex items-center
                                               justify-between text-xs
                                               text-slate-400"
                                    >
                                        <span>
                                            {{ $publishedCount }} published
                                        </span>

                                        <span>
                                            {{ $lessonCount - $publishedCount }} draft
                                        </span>
                                    </div>

                                </div>



                                <a
                                    href="{{ route('teacher.lessons', $course) }}"
                                    class="mt-5 inline-flex h-11 w-full
                                           items-center justify-center gap-2
                                           rounded-xl bg-violet-600
                                           text-sm font-semibold text-white
                                           transition hover:bg-violet-700"
                                >

                                    Manage Lessons

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

                            </div>

                        </article>

                    @empty

                        <div
                            class="col-span-full rounded-3xl border-2
                                   border-dashed border-slate-200
                                   bg-white px-6 py-16 text-center"
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
                                    <path
                                        d="M4 19.5A2.5 2.5 0 016.5 17H20
                                           M6.5 2H20v20H6.5A2.5 2.5 0
                                           014 19.5v-15A2.5 2.5 0 016.5 2z"
                                    />
                                </svg>
                            </div>

                            <h3 class="mt-5 text-lg font-bold text-slate-900">
                                No courses yet
                            </h3>

                            <p class="mt-2 text-sm text-slate-500">
                                Create a course first before adding lessons.
                            </p>

                            <a
                                href="{{ route('teacher.courses.create') }}"
                                class="mt-6 inline-flex h-11 items-center
                                       rounded-xl bg-violet-600 px-5
                                       text-sm font-semibold text-white
                                       hover:bg-violet-700"
                            >
                                Create Course
                            </a>

                        </div>

                    @endforelse

                </div>


                <div
                    id="noCourseResults"
                    class="mt-5 hidden rounded-2xl border-2
                           border-dashed border-slate-200
                           bg-white py-12 text-center"
                >

                    <p class="text-sm font-semibold text-slate-700">
                        No matching courses found.
                    </p>

                    <p class="mt-1 text-xs text-slate-400">
                        Try a different search.
                    </p>

                </div>

            </section>

        </div>

    </main>

</div>


<script>
    function initializeLessonsIndex() {

        const search =
            document.getElementById('courseSearch');

        const grid =
            document.getElementById('courseGrid');

        const empty =
            document.getElementById('noCourseResults');

        if (!search || !grid) return;


        search.oninput = function () {

            const query =
                search.value.toLowerCase().trim();

            const cards =
                grid.querySelectorAll('[data-course-card]');

            let visible = 0;


            cards.forEach(function (card) {

                const haystack =
                    (card.dataset.title || '') +
                    ' ' +
                    (card.dataset.description || '');

                const show =
                    haystack.includes(query);

                card.style.display =
                    show ? '' : 'none';

                if (show) visible++;

            });


            if (empty) {
                empty.classList.toggle(
                    'hidden',
                    visible !== 0
                );
            }

        };

    }


    document.addEventListener(
        'DOMContentLoaded',
        initializeLessonsIndex
    );

    document.addEventListener(
        'livewire:navigated',
        initializeLessonsIndex
    );
</script>

</x-layouts::app>