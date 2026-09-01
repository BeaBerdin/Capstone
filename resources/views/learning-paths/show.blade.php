<x-layouts::app :title="$learningPath->name">

<div class="min-h-screen bg-[#f8f9fc]">

    <main class="px-5 py-7 sm:px-6 lg:px-8 lg:py-9">

        <div class="mx-auto max-w-[1100px]">

            {{-- =====================================================
                BACK BUTTON
            ====================================================== --}}

            <a
                href="{{ route('learning-paths.index') }}"
                class="inline-flex items-center gap-2 text-xs font-semibold
                       text-slate-500 transition hover:text-violet-600"
            >

                <svg
                    class="h-4 w-4"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                >
                    <path d="m15 18-6-6 6-6"></path>
                </svg>

                Back to Learning Paths

            </a>


            {{-- =====================================================
                LEARNING PATH HEADER
            ====================================================== --}}

            <section class="relative mt-5 overflow-hidden rounded-2xl
                            bg-gradient-to-br from-violet-600 via-indigo-600
                            to-blue-600 p-7 shadow-lg shadow-violet-950/10">

                {{-- Decorative circles --}}

                <div class="absolute -right-16 -top-16 h-44 w-44 rounded-full bg-white/10"></div>
                <div class="absolute -bottom-20 -left-10 h-40 w-40 rounded-full bg-white/10"></div>

                <div class="relative">

                    <div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">

                        <div class="max-w-3xl">

                            <span class="inline-flex rounded-full bg-white/15 px-3 py-1.5
                                         text-[10px] font-bold uppercase tracking-[.1em]
                                         text-white backdrop-blur-sm">
                                Learning Path
                            </span>

                            <h1 class="mt-4 text-3xl font-bold leading-tight text-white sm:text-4xl">
                                {{ $learningPath->name }}
                            </h1>

                            <p class="mt-3 text-sm leading-6 text-violet-100">
                                {{ $learningPath->description ?? 'No description provided.' }}
                            </p>

                        </div>


                        {{-- Course Count --}}

                        <div class="shrink-0 rounded-2xl border border-white/15
                                    bg-white/10 px-5 py-4 text-center backdrop-blur-sm">

                            <p class="text-[10px] font-bold uppercase tracking-[.08em] text-violet-100">
                                Courses
                            </p>

                            <p class="mt-1 text-3xl font-bold text-white">
                                {{ $learningPath->courses->count() }}
                            </p>

                        </div>

                    </div>

                </div>

            </section>


            {{-- =====================================================
                COURSE LIST
            ====================================================== --}}

            <section class="mt-6 overflow-hidden rounded-2xl border
                            border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-100 px-6 py-5">

                    <h2 class="text-lg font-bold text-slate-900">
                        Courses in this Learning Path
                    </h2>

                    <p class="mt-1 text-xs text-slate-500">
                        Complete these courses in sequence to build your skills.
                    </p>

                </div>


                @if($learningPath->courses->isNotEmpty())

                    <div class="divide-y divide-slate-100">

                        @foreach($learningPath->courses as $course)

                            <article class="group px-6 py-5 transition hover:bg-slate-50/70">

                                <div class="flex flex-col gap-4 sm:flex-row sm:items-center">

                                    {{-- NUMBER --}}

                                    <div class="flex h-11 w-11 shrink-0 items-center
                                                justify-center rounded-xl bg-violet-50
                                                text-sm font-bold text-violet-600">

                                        {{ $loop->iteration }}

                                    </div>


                                    {{-- COURSE INFO --}}

                                    <div class="min-w-0 flex-1">

                                        <div class="flex flex-col gap-2 sm:flex-row
                                                    sm:items-center sm:justify-between">

                                            <div class="min-w-0">

                                                <p class="text-[10px] font-bold uppercase
                                                          tracking-[.08em] text-violet-500">
                                                    Course {{ $loop->iteration }}
                                                </p>

                                                <h3 class="mt-1 text-base font-bold text-slate-900">
                                                    {{ $course->title }}
                                                </h3>

                                            </div>


                                            @if($course->category)

                                                <span class="self-start rounded-full
                                                             bg-slate-100 px-3 py-1
                                                             text-[10px] font-semibold
                                                             text-slate-600">
                                                    {{ $course->category->name }}
                                                </span>

                                            @endif

                                        </div>

                                        <p class="mt-2 text-xs text-slate-400">
                                            Part of your structured learning journey
                                        </p>

                                    </div>


                                    {{-- COURSE ACTION --}}

                                    <a
                                        href="{{ route('student.course.show', $course) }}"
                                        class="inline-flex h-9 shrink-0 items-center
                                               justify-center gap-2 rounded-xl
                                               border border-violet-200 bg-violet-50
                                               px-4 text-xs font-semibold
                                               text-violet-700 transition
                                               hover:bg-violet-600 hover:text-white"
                                    >

                                        View Course

                                        <svg
                                            class="h-3.5 w-3.5"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                        >
                                            <path d="M5 12h14"></path>
                                            <path d="m13 6 6 6-6 6"></path>
                                        </svg>

                                    </a>

                                </div>

                            </article>

                        @endforeach

                    </div>


                @else

                    {{-- =================================================
                        EMPTY STATE
                    ================================================== --}}

                    <div class="px-6 py-16 text-center">

                        <div class="mx-auto flex h-14 w-14 items-center
                                    justify-center rounded-2xl bg-violet-50
                                    text-violet-600">

                            <svg
                                class="h-7 w-7"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.7"
                            >
                                <circle cx="6" cy="6" r="2"></circle>
                                <circle cx="18" cy="18" r="2"></circle>
                                <path d="M8 6h4a4 4 0 014 4v6"></path>
                            </svg>

                        </div>

                        <h3 class="mt-4 text-sm font-bold text-slate-800">
                            No Courses Yet
                        </h3>

                        <p class="mt-1 text-xs text-slate-400">
                            No courses have been added to this learning path.
                        </p>

                    </div>

                @endif

            </section>


            {{-- =====================================================
                LEARNING PATH FOOTER
            ====================================================== --}}

            @if($learningPath->courses->isNotEmpty())

                <div class="mt-5 flex flex-col gap-3 rounded-2xl
                            border border-violet-100 bg-violet-50/60
                            px-5 py-4 sm:flex-row sm:items-center
                            sm:justify-between">

                    <div>

                        <p class="text-xs font-bold text-violet-900">
                            Ready to start learning?
                        </p>

                        <p class="mt-1 text-[11px] text-violet-600">
                            Explore the courses above and continue your learning journey.
                        </p>

                    </div>

                    <a
                        href="{{ route('learning-paths.index') }}"
                        class="inline-flex h-9 items-center justify-center
                               rounded-xl bg-white px-4 text-xs font-semibold
                               text-violet-700 shadow-sm ring-1 ring-violet-100
                               transition hover:bg-violet-600 hover:text-white"
                    >
                        View All Paths
                    </a>

                </div>

            @endif

        </div>

    </main>

</div>

</x-layouts::app>