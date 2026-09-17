<x-layouts::app :title="$learningPath->name">

@php
    $studentId = auth()->id();

    $enrollments = \App\Models\Enrollment::where('student_id', $studentId)
        ->get()
        ->keyBy('course_id');

    $completedCourseIds = $enrollments
        ->where('status', 'completed')
        ->pluck('course_id')
        ->toArray();

    $activeCourseIds = $enrollments
        ->where('status', 'active')
        ->pluck('course_id')
        ->toArray();

    $totalCourses = $learningPath->courses->count();

    $completedCount = $learningPath->courses
        ->whereIn('id', $completedCourseIds)
        ->count();

    $pathProgress = $totalCourses > 0
        ? round(($completedCount / $totalCourses) * 100)
        : 0;
@endphp


<div class="min-h-screen bg-slate-50/70">

    <main class="px-4 py-6 sm:px-5 lg:px-6 lg:py-7">

        <div class="w-full max-w-none space-y-6">


            {{-- BACK --}}
            <div>
                <x-back-button
                    :href="route('student.learning-paths')"
                    label="Back to Learning Paths"
                />
            </div>



            {{-- =====================================================
                HERO
            ====================================================== --}}

            <section class="overflow-hidden rounded-3xl border border-violet-100 bg-white shadow-sm">

                <div class="grid grid-cols-1 lg:grid-cols-[minmax(0,1fr)_300px]">


                    {{-- LEFT --}}
                    <div class="relative px-6 py-8 sm:px-8">

                        <div class="pointer-events-none absolute -right-16 -top-16 h-52 w-52 rounded-full bg-violet-100/70 blur-3xl"></div>

                        <div class="relative">

                            <div class="flex flex-wrap items-center gap-2">

                                @if($learningPath->is_generated)
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-violet-50 px-3 py-1.5 text-[10px] font-bold uppercase tracking-wide text-violet-700">
                                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path d="m12 3 1.6 4.4L18 9l-4.4 1.6L12 15l-1.6-4.4L6 9l4.4-1.6L12 3Z"></path>
                                        </svg>
                                        AI Generated
                                    </span>
                                @endif

                                @if($learningPath->difficulty_level)
                                    <span class="rounded-full bg-slate-100 px-3 py-1.5 text-[10px] font-semibold text-slate-600">
                                        {{ ucfirst($learningPath->difficulty_level) }}
                                    </span>
                                @endif

                            </div>


                            <p class="mt-5 text-xs font-bold uppercase tracking-[0.14em] text-violet-600">
                                Personalized Learning Journey
                            </p>

                            <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">
                                {{ $learningPath->name }}
                            </h1>

                            <p class="mt-4 max-w-3xl text-sm leading-7 text-slate-500">
                                {{ $learningPath->description }}
                            </p>

                        </div>

                    </div>



                    {{-- RIGHT --}}
                    <div class="border-t border-violet-100 bg-violet-50/70 p-6 lg:border-l lg:border-t-0">

                        <p class="text-[10px] font-bold uppercase tracking-[0.12em] text-violet-500">
                            Path Progress
                        </p>

                        <div class="mt-3 flex items-end gap-2">
                            <p class="text-5xl font-bold tracking-tight text-violet-900">
                                {{ $pathProgress }}
                            </p>

                            <span class="pb-1 text-lg font-bold text-violet-400">
                                %
                            </span>
                        </div>


                        <div class="mt-5 h-2.5 overflow-hidden rounded-full bg-violet-100">
                            <div
                                class="h-full rounded-full bg-violet-600"
                                style="width: {{ $pathProgress }}%"
                            ></div>
                        </div>


                        <div class="mt-5 grid grid-cols-2 gap-3">

                            <div class="rounded-xl bg-white p-3 shadow-sm">
                                <p class="text-[9px] font-bold uppercase tracking-wide text-slate-400">
                                    Completed
                                </p>

                                <p class="mt-1 text-xl font-bold text-emerald-600">
                                    {{ $completedCount }}
                                </p>
                            </div>


                            <div class="rounded-xl bg-white p-3 shadow-sm">
                                <p class="text-[9px] font-bold uppercase tracking-wide text-slate-400">
                                    Total Courses
                                </p>

                                <p class="mt-1 text-xl font-bold text-violet-700">
                                    {{ $totalCourses }}
                                </p>
                            </div>

                        </div>

                    </div>

                </div>

            </section>



            {{-- =====================================================
                LEARNING SEQUENCE
            ====================================================== --}}

            <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-100 px-6 py-5 sm:px-7">

                    <p class="text-[10px] font-bold uppercase tracking-[0.12em] text-violet-500">
                        Your Journey
                    </p>

                    <h2 class="mt-1 text-xl font-bold text-slate-900">
                        Recommended Learning Sequence
                    </h2>

                    <p class="mt-1 text-xs text-slate-500">
                        Follow the course sequence below to progress through this learning path.
                    </p>

                </div>


                <div class="p-5 sm:p-7">

                    @forelse($learningPath->courses as $index => $course)

                        @php
                            $isCompleted = in_array($course->id, $completedCourseIds);
                            $isActive = in_array($course->id, $activeCourseIds);

                            $statusLabel = 'Not Started';
                            $statusClass = 'bg-slate-100 text-slate-600';
                            $dotClass = 'bg-slate-300';

                            if ($isCompleted) {
                                $statusLabel = 'Completed';
                                $statusClass = 'bg-emerald-50 text-emerald-700';
                                $dotClass = 'bg-emerald-500';
                            } elseif ($isActive) {
                                $statusLabel = 'In Progress';
                                $statusClass = 'bg-violet-50 text-violet-700';
                                $dotClass = 'bg-violet-600';
                            }

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
                        @endphp


                        <div class="relative">


                            {{-- CONNECTOR --}}
                            @if(!$loop->last)
                                <div class="absolute left-[21px] top-12 h-[calc(100%-16px)] w-px bg-slate-200"></div>
                            @endif


                            <div class="relative flex gap-4 pb-6">


                                {{-- STEP --}}
                                <div class="relative z-10 flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl border-4 border-white {{ $dotClass }} text-sm font-bold text-white shadow-sm">

                                    @if($isCompleted)
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                            <path d="m5 12 4 4L19 6"></path>
                                        </svg>
                                    @else
                                        {{ $index + 1 }}
                                    @endif

                                </div>



                                {{-- COURSE CARD --}}
                                <article class="flex-1 overflow-hidden rounded-2xl border border-slate-200 bg-white transition hover:border-violet-200 hover:shadow-sm">

                                    <div class="grid grid-cols-1 lg:grid-cols-[150px_minmax(0,1fr)_170px]">


                                        {{-- IMAGE --}}
                                        <div class="relative min-h-[140px] overflow-hidden bg-slate-100">

                                            @if($thumbnailUrl)

                                                <img
                                                    src="{{ $thumbnailUrl }}"
                                                    alt="{{ $course->title }}"
                                                    class="absolute inset-0 h-full w-full object-cover"
                                                >

                                            @else

                                                <div class="absolute inset-0 flex items-center justify-center bg-gradient-to-br from-violet-100 via-indigo-50 to-blue-100 text-violet-600">
                                                    <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                                        <path d="M4 19.5A2.5 2.5 0 016.5 17H20"></path>
                                                        <path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"></path>
                                                    </svg>
                                                </div>

                                            @endif

                                        </div>



                                        {{-- DETAILS --}}
                                        <div class="p-5">

                                            <div class="flex flex-wrap items-center gap-2">

                                                <span class="text-[10px] font-bold uppercase tracking-[0.1em] text-violet-500">
                                                    Step {{ $index + 1 }}
                                                </span>

                                                <span class="rounded-full px-2.5 py-1 text-[10px] font-bold {{ $statusClass }}">
                                                    {{ $statusLabel }}
                                                </span>

                                            </div>


                                            <h3 class="mt-2 text-lg font-bold text-slate-900">
                                                {{ $course->title }}
                                            </h3>


                                            <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-[11px] font-medium text-slate-400">

                                                <span>
                                                    {{ $course->category->name ?? 'No Category' }}
                                                </span>

                                                <span>
                                                    {{ ucfirst($course->difficulty_level) }}
                                                </span>

                                                @if($course->estimated_hours)
                                                    <span>
                                                        {{ $course->estimated_hours }} hrs
                                                    </span>
                                                @endif

                                            </div>


                                            <p class="mt-3 line-clamp-2 text-sm leading-6 text-slate-500">
                                                {{ $course->description }}
                                            </p>

                                        </div>



                                        {{-- ACTION --}}
                                        <div class="flex items-center border-t border-slate-100 bg-slate-50/70 p-5 lg:border-l lg:border-t-0">

                                            @if($isCompleted)

                                                <a
                                                    href="{{ route('student.course.show', $course) }}"
                                                    class="inline-flex h-10 w-full items-center justify-center rounded-xl bg-emerald-50 px-4 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-100"
                                                >
                                                    Review Course
                                                </a>

                                            @elseif($isActive)

                                                <a
                                                    href="{{ route('student.learn.course', $course) }}"
                                                    class="inline-flex h-10 w-full items-center justify-center gap-2 rounded-xl bg-violet-600 px-4 text-xs font-semibold text-white transition hover:bg-violet-700"
                                                >
                                                    Continue Learning

                                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                        <path d="m9 18 6-6-6-6"></path>
                                                    </svg>
                                                </a>

                                            @else

                                                <a
                                                    href="{{ route('student.course.show', $course) }}"
                                                    class="inline-flex h-10 w-full items-center justify-center gap-2 rounded-xl border border-violet-200 bg-white px-4 text-xs font-semibold text-violet-700 transition hover:bg-violet-50"
                                                >
                                                    View Course

                                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                        <path d="m9 18 6-6-6-6"></path>
                                                    </svg>
                                                </a>

                                            @endif

                                        </div>

                                    </div>

                                </article>

                            </div>

                        </div>


                    @empty


                        <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-6 py-14 text-center">

                            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-violet-50 text-violet-600">
                                <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path d="M4 19.5A2.5 2.5 0 016.5 17H20"></path>
                                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"></path>
                                </svg>
                            </div>

                            <h3 class="mt-4 text-base font-bold text-slate-900">
                                No courses assigned
                            </h3>

                            <p class="mt-2 text-sm text-slate-500">
                                This learning path does not have any available courses yet.
                            </p>

                        </div>

                    @endforelse

                </div>

            </section>

        </div>

    </main>

</div>

</x-layouts::app>
