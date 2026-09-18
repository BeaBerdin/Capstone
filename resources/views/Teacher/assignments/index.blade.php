<x-layouts::app title="Assignments">
@php
    $totalAssignments = $assignments->count();
    $publishedAssignments = $assignments->where('is_published', true)->count();
    $draftAssignments = $totalAssignments - $publishedAssignments;
@endphp

<div class="min-h-screen bg-[#f8f9fc]">
    <main class="px-5 py-7 sm:px-6 lg:px-8 lg:py-9">
        <div class="mx-auto max-w-[1500px]">

            <div class="mb-5 flex flex-wrap items-center gap-2 text-xs text-slate-400">
                <a href="{{ route('teacher.dashboard') }}" class="font-medium hover:text-violet-600">
                    Dashboard
                </a>
                <span>›</span>
                <span class="font-semibold text-slate-600">Assignments</span>
            </div>

            <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[.12em] text-violet-600">
                        Output Evaluation
                    </p>
                    <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-950">
                        Assignments
                    </h1>
                    <p class="mt-2 text-sm text-slate-500">
                        Create graded activities, review student submissions, and provide feedback.
                    </p>
                </div>

                @if($courses->isNotEmpty())
                    <div class="relative" x-data="{ open: false }">
                        <button
                            type="button"
                            @click="open = !open"
                            class="inline-flex h-11 items-center justify-center gap-2 rounded-xl
                                   bg-gradient-to-r from-violet-600 to-indigo-600 px-5
                                   text-sm font-semibold text-white shadow-md shadow-violet-200
                                   transition hover:-translate-y-0.5 hover:shadow-lg"
                        >
                            <span class="text-lg leading-none">+</span>
                            New Assignment
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"
                                 stroke="currentColor" stroke-width="2">
                                <path d="m6 9 6 6 6-6"/>
                            </svg>
                        </button>

                        <div
                            x-cloak
                            x-show="open"
                            @click.outside="open = false"
                            class="absolute right-0 z-30 mt-2 w-80 overflow-hidden rounded-2xl
                                   border border-slate-200 bg-white p-2 shadow-xl"
                        >
                            <p class="px-3 py-2 text-[11px] font-bold uppercase tracking-wider text-slate-400">
                                Choose a Draft / Rejected Course
                            </p>
                            @foreach($courses as $course)
                                <a
                                    href="{{ route('teacher.assignments.create', $course) }}"
                                    class="block rounded-xl px-3 py-3 transition hover:bg-violet-50"
                                >
                                    <p class="text-sm font-semibold text-slate-800">{{ $course->title }}</p>
                                    <p class="mt-1 text-xs text-slate-400">
                                        {{ ucfirst($course->status) }}
                                    </p>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @else
                    <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 text-xs text-slate-500">
                        Create or return a course to Draft before adding assignments.
                    </div>
                @endif
            </div>

            @if(session('success'))
                <div class="mt-6 rounded-2xl border border-emerald-200 bg-emerald-50
                            px-5 py-4 text-sm font-medium text-emerald-700">
                    ✓ {{ session('success') }}
                </div>
            @endif

            <section class="mt-7 grid gap-3 sm:grid-cols-3">
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total</p>
                    <p class="mt-2 text-2xl font-bold text-slate-950">{{ $totalAssignments }}</p>
                </div>
                <div class="rounded-2xl border border-emerald-100 bg-emerald-50 p-5">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-emerald-600">Published</p>
                    <p class="mt-2 text-2xl font-bold text-emerald-700">{{ $publishedAssignments }}</p>
                </div>
                <div class="rounded-2xl border border-amber-100 bg-amber-50 p-5">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-amber-600">Unpublished</p>
                    <p class="mt-2 text-2xl font-bold text-amber-700">{{ $draftAssignments }}</p>
                </div>
            </section>

            <section class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-5 py-4 sm:px-6">
                    <h2 class="text-sm font-bold text-slate-800">Your Assignments</h2>
                </div>

                @forelse($assignments as $assignment)
                    @php
                        $courseLocked = !in_array($assignment->course?->status, ['draft', 'rejected'], true);
                    @endphp

                    <div class="border-b border-slate-100 px-5 py-5 last:border-b-0 sm:px-6">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="rounded-full bg-violet-50 px-2.5 py-1 text-[11px]
                                                 font-semibold text-violet-700">
                                        {{ $assignment->course?->title ?? 'Course unavailable' }}
                                    </span>

                                    @if($assignment->is_published)
                                        <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-[11px]
                                                     font-semibold text-emerald-700">
                                            Published
                                        </span>
                                    @else
                                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px]
                                                     font-semibold text-slate-600">
                                            Unpublished
                                        </span>
                                    @endif
                                </div>

                                <h3 class="mt-2 text-base font-bold text-slate-900">
                                    {{ $assignment->title }}
                                </h3>

                                <div class="mt-2 flex flex-wrap gap-x-5 gap-y-1 text-xs text-slate-500">
                                    <span>Max score: <strong class="text-slate-700">{{ $assignment->max_score }}</strong></span>
                                    <span>
                                        Due:
                                        <strong class="text-slate-700">
                                            {{ $assignment->due_date ? $assignment->due_date->format('M d, Y · h:i A') : 'No deadline' }}
                                        </strong>
                                    </span>
                                    @if($assignment->lesson)
                                        <span>Lesson: <strong class="text-slate-700">{{ $assignment->lesson->title }}</strong></span>
                                    @endif
                                </div>
                            </div>

                            <div class="flex flex-wrap gap-2">
                                <a
                                    href="{{ route('teacher.submissions.index', $assignment) }}"
                                    class="inline-flex h-9 items-center justify-center rounded-xl border
                                           border-violet-200 bg-violet-50 px-3.5 text-xs font-semibold
                                           text-violet-700 transition hover:bg-violet-100"
                                >
                                    View Submissions
                                </a>

                                @if(!$courseLocked)
                                    <a
                                        href="{{ route('teacher.assignments.edit', $assignment) }}"
                                        class="inline-flex h-9 items-center justify-center rounded-xl border
                                               border-slate-200 bg-white px-3.5 text-xs font-semibold
                                               text-slate-700 transition hover:bg-slate-50"
                                    >
                                        Edit
                                    </a>

                                    <form
                                        method="POST"
                                        action="{{ route('teacher.assignments.destroy', $assignment) }}"
                                        onsubmit="return confirm('Delete this assignment? This will also delete its submissions.');"
                                    >
                                        @csrf
                                        @method('DELETE')
                                        <button
                                            type="submit"
                                            class="inline-flex h-9 items-center justify-center rounded-xl border
                                                   border-rose-200 bg-rose-50 px-3.5 text-xs font-semibold
                                                   text-rose-700 transition hover:bg-rose-100"
                                        >
                                            Delete
                                        </button>
                                    </form>
                                @else
                                    <span
                                        class="inline-flex h-9 items-center justify-center rounded-xl
                                               bg-slate-100 px-3.5 text-xs font-semibold text-slate-500"
                                        title="Assignments are locked while the course is pending, approved, or published."
                                    >
                                        Content Locked
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="px-6 py-16 text-center">
                        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-violet-50 text-2xl">
                            📝
                        </div>
                        <h3 class="mt-4 text-base font-bold text-slate-800">No assignments yet</h3>
                        <p class="mt-2 text-sm text-slate-500">
                            Create an assignment from one of your Draft or Rejected courses.
                        </p>
                    </div>
                @endforelse
            </section>

        </div>
    </main>
</div>
</x-layouts::app>
