<x-layouts::app :title="'Recommended Courses'">

<div class="min-h-screen bg-slate-50/70">

    <main class="px-4 py-6 sm:px-5 lg:px-6 lg:py-7">

        <div class="w-full max-w-none space-y-6">


            {{-- =====================================================
                HEADER
            ====================================================== --}}

            <section class="overflow-hidden rounded-3xl border border-violet-100 bg-white shadow-sm">

                <div class="relative px-6 py-7 sm:px-8">

                    <div class="pointer-events-none absolute -right-20 -top-24 h-64 w-64 rounded-full bg-violet-100/70 blur-3xl"></div>
                    <div class="pointer-events-none absolute right-36 top-12 h-24 w-24 rounded-full bg-indigo-100/70 blur-2xl"></div>

                    <div class="relative flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">

                        <div class="max-w-2xl">

                            <div class="inline-flex items-center gap-2 rounded-full bg-violet-50 px-3 py-1.5 text-[11px] font-bold uppercase tracking-[0.14em] text-violet-700">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path d="m12 3 1.6 4.4L18 9l-4.4 1.6L12 15l-1.6-4.4L6 9l4.4-1.6L12 3Z"></path>
                                </svg>
                                Personalized Learning
                            </div>

                            <h1 class="mt-4 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">
                                AI Recommendations
                            </h1>

                            <p class="mt-3 max-w-xl text-sm leading-6 text-slate-500">
                                Course suggestions based on your quiz performance and current learning progress.
                            </p>

                        </div>


                        <div class="flex items-center gap-4 rounded-2xl border border-violet-100 bg-violet-50/70 px-5 py-4">

                            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-white text-violet-600 shadow-sm">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path d="M12 3l2.4 4.86L20 8.74l-4 3.9.94 5.46L12 15.77 7.06 18.1 8 12.64 4 8.74l5.6-.88L12 3z"></path>
                                </svg>
                            </div>

                            <div>
                                <p class="text-[10px] font-bold uppercase tracking-[0.12em] text-violet-500">
                                    Recommendations
                                </p>

                                <p class="mt-1 text-2xl font-bold text-violet-900">
                                    {{ $recommendations->count() }}
                                </p>
                            </div>

                        </div>

                    </div>

                </div>

            </section>



            {{-- =====================================================
                RECOMMENDATIONS
            ====================================================== --}}

            @forelse($recommendations as $recommendation)

                @php
                    $course = $recommendation->course;

                    $thumbnail = $course?->thumbnail ?? null;
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

                    $score = min(
                        max(
                            (float) ($recommendation->recommendation_score ?? 0),
                            0
                        ),
                        100
                    );
                @endphp


                <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm transition duration-200 hover:border-violet-200 hover:shadow-md">

                    <div class="grid grid-cols-1 lg:grid-cols-[260px_minmax(0,1fr)_210px]">


                        {{-- COURSE VISUAL --}}
                        <div class="relative min-h-[220px] overflow-hidden bg-slate-100 lg:min-h-full">

                            @if($thumbnailUrl)

                                <img
                                    src="{{ $thumbnailUrl }}"
                                    alt="{{ $course?->title ?? 'Recommended Course' }}"
                                    class="absolute inset-0 h-full w-full object-cover"
                                >

                                <div class="absolute inset-0 bg-gradient-to-t from-slate-950/50 via-transparent to-transparent"></div>

                            @else

                                <div class="absolute inset-0 bg-gradient-to-br from-violet-500 via-indigo-500 to-blue-500"></div>

                                <div class="absolute -right-8 -top-8 h-32 w-32 rounded-full border border-white/20"></div>
                                <div class="absolute -bottom-12 -left-12 h-40 w-40 rounded-full border border-white/15"></div>

                                <div class="relative flex h-full min-h-[220px] items-center justify-center">
                                    <div class="flex h-20 w-20 items-center justify-center rounded-3xl bg-white/15 text-white backdrop-blur">
                                        <svg class="h-9 w-9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                            <path d="M4 19.5A2.5 2.5 0 016.5 17H20"></path>
                                            <path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"></path>
                                        </svg>
                                    </div>
                                </div>

                            @endif


                            <div class="absolute left-4 top-4">
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-white/95 px-3 py-1.5 text-[10px] font-bold uppercase tracking-wide text-violet-700 shadow-sm backdrop-blur">
                                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path d="m12 3 1.6 4.4L18 9l-4.4 1.6L12 15l-1.6-4.4L6 9l4.4-1.6L12 3Z"></path>
                                    </svg>
                                    AI Pick
                                </span>
                            </div>


                            <div class="absolute bottom-4 left-4 right-4">
                                <p class="text-[10px] font-bold uppercase tracking-[0.12em] text-white/75">
                                    {{ $course?->category?->name ?? 'Recommended Course' }}
                                </p>
                            </div>

                        </div>



                        {{-- COURSE CONTENT --}}
                        <div class="p-6 sm:p-7">

                            <div class="flex flex-wrap items-center gap-2">

                                @if($course?->difficulty_level)
                                    <span class="rounded-full bg-slate-100 px-3 py-1 text-[10px] font-semibold text-slate-600">
                                        {{ ucfirst($course->difficulty_level) }}
                                    </span>
                                @endif

                                @if($course?->estimated_hours)
                                    <span class="rounded-full bg-violet-50 px-3 py-1 text-[10px] font-semibold text-violet-700">
                                        {{ $course->estimated_hours }} hrs
                                    </span>
                                @endif

                                @if($course?->certificate_available)
                                    <span class="rounded-full bg-emerald-50 px-3 py-1 text-[10px] font-semibold text-emerald-700">
                                        Certificate Available
                                    </span>
                                @endif

                            </div>


                            <p class="mt-5 text-[10px] font-bold uppercase tracking-[0.12em] text-violet-500">
                                Recommended for you
                            </p>

                            <h2 class="mt-2 text-2xl font-bold tracking-tight text-slate-950">
                                {{ $course?->title ?? 'Course unavailable' }}
                            </h2>


                            @if($course?->description)
                                <p class="mt-3 line-clamp-2 max-w-3xl text-sm leading-6 text-slate-500">
                                    {{ $course->description }}
                                </p>
                            @endif


                            <div class="mt-6 rounded-2xl border border-violet-100 bg-violet-50/60 p-4">

                                <div class="flex items-start gap-3">

                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-white text-violet-600 shadow-sm">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path d="M12 3v18"></path>
                                            <path d="M5 10h14"></path>
                                        </svg>
                                    </div>

                                    <div>
                                        <p class="text-[10px] font-bold uppercase tracking-[0.1em] text-violet-600">
                                            Why this fits you
                                        </p>

                                        <p class="mt-1 text-sm leading-6 text-slate-600">
                                            {{ $recommendation->reason }}
                                        </p>
                                    </div>

                                </div>

                            </div>

                        </div>



                        {{-- MATCH / ACTION --}}
                        <div class="flex flex-col justify-between border-t border-slate-100 bg-slate-50/70 p-6 lg:border-l lg:border-t-0">

                            <div>

                                <p class="text-[10px] font-bold uppercase tracking-[0.12em] text-slate-400">
                                    AI Match
                                </p>


                                <div class="mt-4 flex items-end gap-2">

                                    <p class="text-5xl font-bold tracking-tight text-violet-700">
                                        {{ number_format($score, 0) }}
                                    </p>

                                    <span class="pb-1 text-lg font-bold text-violet-400">
                                        %
                                    </span>

                                </div>


                                <div class="mt-4 h-2 overflow-hidden rounded-full bg-violet-100">
                                    <div
                                        class="h-full rounded-full bg-violet-600"
                                        style="width: {{ $score }}%;"
                                    ></div>
                                </div>


                                <p class="mt-3 text-xs leading-5 text-slate-500">
                                    Match based on your latest learning performance.
                                </p>

                            </div>


                            <div class="mt-8 space-y-2">

                                <a
                                    href="{{ route('student.course.show', $course) }}"
                                    class="inline-flex h-11 w-full items-center justify-center gap-2 rounded-xl bg-violet-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-violet-700"
                                >
                                    View Course

                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path d="m9 18 6-6-6-6"></path>
                                    </svg>
                                </a>


                                <a
                                    href="{{ route('student.recommendations') }}"
                                    class="inline-flex h-10 w-full items-center justify-center rounded-xl text-xs font-semibold text-slate-500 transition hover:bg-white hover:text-violet-700"
                                >
                                    Refresh View
                                </a>

                            </div>

                        </div>

                    </div>

                </section>


            @empty


                {{-- =================================================
                    EMPTY STATE
                ================================================== --}}

                <section class="overflow-hidden rounded-3xl border border-dashed border-violet-200 bg-white shadow-sm">

                    <div class="grid min-h-[360px] grid-cols-1 lg:grid-cols-[1fr_360px]">


                        <div class="flex items-center px-8 py-12 sm:px-12">

                            <div class="max-w-xl">

                                <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-violet-50 text-violet-600">
                                    <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path d="m12 3 1.6 4.4L18 9l-4.4 1.6L12 15l-1.6-4.4L6 9l4.4-1.6L12 3Z"></path>
                                    </svg>
                                </div>

                                <p class="mt-6 text-xs font-bold uppercase tracking-[0.14em] text-violet-600">
                                    Personalized Suggestions
                                </p>

                                <h2 class="mt-2 text-2xl font-bold tracking-tight text-slate-950">
                                    No recommendations yet
                                </h2>

                                <p class="mt-3 text-sm leading-6 text-slate-500">
                                    Complete quizzes from your enrolled courses. PathWise will use your performance
                                    to suggest courses that match your current learning level.
                                </p>

                                <a
                                    href="{{ route('student.my-courses') }}"
                                    class="mt-6 inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-violet-600 px-5 text-sm font-semibold text-white transition hover:bg-violet-700"
                                >
                                    Go to My Courses

                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path d="m9 18 6-6-6-6"></path>
                                    </svg>
                                </a>

                            </div>

                        </div>


                        <div class="relative hidden overflow-hidden bg-gradient-to-br from-violet-600 via-indigo-600 to-blue-600 lg:block">

                            <div class="absolute -right-12 -top-12 h-44 w-44 rounded-full border border-white/15"></div>
                            <div class="absolute -bottom-16 -left-16 h-56 w-56 rounded-full border border-white/10"></div>

                            <div class="relative flex h-full items-center justify-center">

                                <div class="text-center text-white">

                                    <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-3xl bg-white/15 backdrop-blur">
                                        <svg class="h-9 w-9" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                            <path d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16 2.5 6.5L22 12l-6.5 2.5L13 21l-2.5-6.5L4 12l6.5-2.5L13 3Z"></path>
                                        </svg>
                                    </div>

                                    <p class="mt-5 text-sm font-semibold">
                                        PathWise AI
                                    </p>

                                    <p class="mt-1 text-xs text-white/70">
                                        Learn. Assess. Recommend.
                                    </p>

                                </div>

                            </div>

                        </div>

                    </div>

                </section>

            @endforelse

        </div>

    </main>

</div>

</x-layouts::app>
