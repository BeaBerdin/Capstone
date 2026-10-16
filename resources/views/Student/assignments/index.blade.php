<x-layouts::app title="My Assignments">

@php
    $totalAssignments = $assignments->count();

    $upcomingAssignments = $assignments
        ->filter(function ($assignment) {
            return $assignment->due_date
                && $assignment->due_date->isFuture();
        })
        ->count();

    $noDeadlineAssignments = $assignments
        ->filter(function ($assignment) {
            return is_null($assignment->due_date);
        })
        ->count();
@endphp


<div class="min-h-screen bg-[#f8f9fc]">

    <main class="px-5 py-7 sm:px-6 lg:px-8 lg:py-9">

        <div class="mx-auto max-w-[1500px]">


            {{-- BREADCRUMB --}}
            <div
                class="mb-5 flex flex-wrap items-center
                       gap-2 text-xs text-slate-400"
            >

                <a
                    href="{{ route('student.dashboard') }}"
                    class="font-medium hover:text-violet-600"
                >
                    Dashboard
                </a>

                <span>›</span>

                <span class="font-semibold text-slate-600">
                    Assignments
                </span>

            </div>


            {{-- HEADER --}}
            <div
                class="flex flex-col gap-4
                       lg:flex-row lg:items-end
                       lg:justify-between"
            >

                <div>

                    <p
                        class="text-xs font-bold uppercase
                               tracking-[.12em] text-violet-600"
                    >
                        Output Activities
                    </p>

                    <h1
                        class="mt-2 text-3xl font-bold
                               tracking-tight text-slate-950"
                    >
                        My Assignments
                    </h1>

                    <p class="mt-2 text-sm text-slate-500">
                        View and submit assignments from your enrolled courses.
                    </p>

                </div>

                <a
                    href="{{ route('student.my-courses') }}"
                    class="inline-flex h-10 items-center
                           justify-center rounded-xl
                           border border-slate-200
                           bg-white px-4 text-sm
                           font-semibold text-slate-700
                           shadow-sm hover:bg-slate-50"
                >
                    My Courses
                </a>

            </div>


            {{-- SUCCESS --}}
            @if(session('success'))

                <div
                    class="mt-6 rounded-2xl border
                           border-emerald-200
                           bg-emerald-50 px-5 py-4
                           text-sm font-medium
                           text-emerald-700"
                >
                    ✓ {{ session('success') }}
                </div>

            @endif


            {{-- STATS --}}
            <section
                class="mt-7 grid gap-3
                       sm:grid-cols-3"
            >

                <div
                    class="rounded-2xl border
                           border-slate-200
                           bg-white p-5 shadow-sm"
                >

                    <p
                        class="text-[11px] font-bold
                               uppercase tracking-wider
                               text-slate-400"
                    >
                        Total
                    </p>

                    <p
                        class="mt-2 text-2xl font-bold
                               text-slate-950"
                    >
                        {{ $totalAssignments }}
                    </p>

                </div>


                <div
                    class="rounded-2xl border
                           border-violet-100
                           bg-violet-50 p-5"
                >

                    <p
                        class="text-[11px] font-bold
                               uppercase tracking-wider
                               text-violet-600"
                    >
                        Upcoming
                    </p>

                    <p
                        class="mt-2 text-2xl font-bold
                               text-violet-700"
                    >
                        {{ $upcomingAssignments }}
                    </p>

                </div>


                <div
                    class="rounded-2xl border
                           border-blue-100
                           bg-blue-50 p-5"
                >

                    <p
                        class="text-[11px] font-bold
                               uppercase tracking-wider
                               text-blue-600"
                    >
                        No Deadline
                    </p>

                    <p
                        class="mt-2 text-2xl font-bold
                               text-blue-700"
                    >
                        {{ $noDeadlineAssignments }}
                    </p>

                </div>

            </section>


            {{-- ASSIGNMENTS --}}
            <section
                class="mt-6 overflow-hidden
                       rounded-2xl border
                       border-slate-200
                       bg-white shadow-sm"
            >

                <div
                    class="border-b border-slate-100
                           px-5 py-4 sm:px-6"
                >

                    <h2
                        class="text-sm font-bold
                               text-slate-800"
                    >
                        Available Assignments
                    </h2>

                </div>


                @forelse($assignments as $assignment)

                    @php
                        $isLate =
                            $assignment->due_date
                            && $assignment->due_date->isPast();

                        $dueClass =
                            $isLate
                            ? 'text-rose-600'
                            : 'text-slate-500';
                    @endphp


                    <div
                        class="border-b border-slate-100
                               px-5 py-5
                               last:border-b-0
                               sm:px-6"
                    >

                        <div
                            class="flex flex-col gap-4
                                   lg:flex-row
                                   lg:items-center
                                   lg:justify-between"
                        >

                            <div class="min-w-0">


                                {{-- Course --}}
                                <div
                                    class="flex flex-wrap
                                           items-center gap-2"
                                >

                                    <span
                                        class="rounded-full
                                               bg-violet-50
                                               px-2.5 py-1
                                               text-[11px]
                                               font-semibold
                                               text-violet-700"
                                    >
                                        {{
                                            $assignment
                                                ->course
                                                ?->title
                                            ?? 'Course'
                                        }}
                                    </span>


                                    @if($isLate)

                                        <span
                                            class="rounded-full
                                                   bg-rose-50
                                                   px-2.5 py-1
                                                   text-[11px]
                                                   font-semibold
                                                   text-rose-700"
                                        >
                                            Deadline Passed
                                        </span>

                                    @elseif($assignment->due_date)

                                        <span
                                            class="rounded-full
                                                   bg-blue-50
                                                   px-2.5 py-1
                                                   text-[11px]
                                                   font-semibold
                                                   text-blue-700"
                                        >
                                            Upcoming
                                        </span>

                                    @else

                                        <span
                                            class="rounded-full
                                                   bg-slate-100
                                                   px-2.5 py-1
                                                   text-[11px]
                                                   font-semibold
                                                   text-slate-600"
                                        >
                                            No Deadline
                                        </span>

                                    @endif

                                </div>


                                {{-- Title --}}
                                <h3
                                    class="mt-2 text-base
                                           font-bold
                                           text-slate-900"
                                >
                                    {{ $assignment->title }}
                                </h3>


                                {{-- Description --}}
                                @if($assignment->description)

                                    <p
                                        class="mt-2 line-clamp-2
                                               max-w-3xl
                                               text-sm leading-6
                                               text-slate-500"
                                    >
                                        {{ $assignment->description }}
                                    </p>

                                @endif


                                {{-- Metadata --}}
                                <div
                                    class="mt-3 flex flex-wrap
                                           gap-x-5 gap-y-1
                                           text-xs"
                                >

                                    <span class="text-slate-500">
                                        Max score:

                                        <strong class="text-slate-700">
                                            {{ $assignment->max_score }}
                                        </strong>
                                    </span>


                                    <span class="{{ $dueClass }}">

                                        Due:

                                        <strong>
                                            {{
                                                $assignment->due_date
                                                    ? $assignment
                                                        ->due_date
                                                        ->format(
                                                            'M d, Y · h:i A'
                                                        )
                                                    : 'No deadline'
                                            }}
                                        </strong>

                                    </span>


                                    @if($assignment->lesson)

                                        <span class="text-slate-500">
                                            Lesson:

                                            <strong class="text-slate-700">
                                                {{ $assignment->lesson->title }}
                                            </strong>
                                        </span>

                                    @endif

                                </div>

                            </div>


                            {{-- ACTION --}}
                            <a
                                href="{{
                                    route(
                                        'student.assignments.show',
                                        $assignment
                                    )
                                }}"
                                class="inline-flex h-10
                                       shrink-0 items-center
                                       justify-center
                                       rounded-xl
                                       bg-gradient-to-r
                                       from-violet-600
                                       to-indigo-600
                                       px-5 text-sm
                                       font-semibold
                                       text-white
                                       shadow-md
                                       shadow-violet-200
                                       transition
                                       hover:-translate-y-0.5"
                            >
                                View Assignment
                            </a>

                        </div>

                    </div>


                @empty

                    <div class="px-6 py-16 text-center">

                        <div
                            class="mx-auto flex h-14 w-14
                                   items-center justify-center
                                   rounded-2xl
                                   bg-violet-50"
                        >

                            <svg
                                class="h-6 w-6 text-violet-600"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                            >
                                <path d="M9 12h6M9 16h4"/>
                                <path d="M7 3h7l5 5v13H7z"/>
                            </svg>

                        </div>


                        <h3
                            class="mt-4 text-base
                                   font-bold
                                   text-slate-800"
                        >
                            No assignments available
                        </h3>


                        <p
                            class="mt-2 text-sm
                                   text-slate-500"
                        >
                            Published assignments from your enrolled courses
                            will appear here.
                        </p>

                    </div>

                @endforelse

            </section>

        </div>

    </main>

</div>

</x-layouts::app>