<x-layouts::app :title="__('Student Progress')">

    @php
        $totalEnrollments = $enrollments->count();
        $completed = $enrollments->where('status', 'completed')->count();
        $active = $enrollments->where('status', 'active')->count();
        $averageProgress = $totalEnrollments > 0 ? $enrollments->avg('progress_percentage') : 0;
    @endphp

    <style>
        .pw-card {
            background: #ffffff;
            border: 1px solid #e7e9ef;
            border-radius: 20px;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.035);
        }

        .pw-hover {
            transition:
                transform 160ms ease,
                border-color 160ms ease,
                box-shadow 160ms ease;
        }

        .pw-hover:hover {
            transform: translateY(-2px);
            border-color: #ddd6fe;
            box-shadow: 0 12px 30px rgba(76, 29, 149, 0.06);
        }
    </style>

    <div class="min-h-screen bg-[#f8f9fc]">

        <main class="px-5 py-7 sm:px-6 lg:px-8 lg:py-9">

            <div class="mx-auto max-w-[1500px]">


                {{-- =====================================================
                    HEADER
                ====================================================== --}}

                <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">

                    <div>

                        <p class="text-xs font-bold uppercase tracking-[.12em] text-violet-600">
                            Learning Analytics
                        </p>

                        <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-950">
                            Student Progress Tracker
                        </h1>

                        <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">
                            Monitor student enrollments and course completion status.
                        </p>

                    </div>

                </div>



                {{-- =====================================================
                    STATS CARDS
                ====================================================== --}}

                <section class="mt-7 grid grid-cols-2 gap-4 xl:grid-cols-4">


                    {{-- TOTAL ENROLLMENTS --}}
                    <div class="pw-card pw-hover p-5">

                        <div class="flex items-start justify-between gap-4">

                            <div>

                                <p class="text-xs font-semibold text-slate-500">
                                    Total Enrollments
                                </p>

                                <p class="mt-2 text-3xl font-bold tracking-tight text-slate-950">
                                    {{ $totalEnrollments }}
                                </p>

                            </div>

                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-violet-50 text-violet-600">

                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2"></path>
                                    <circle cx="9" cy="7" r="4"></circle>
                                    <path d="M22 21v-2a4 4 0 00-3-3.87"></path>
                                </svg>

                            </div>

                        </div>

                        <p class="mt-2 text-xs text-slate-400">
                            All students
                        </p>

                    </div>



                    {{-- ACTIVE --}}
                    <div class="pw-card pw-hover p-5">

                        <div class="flex items-start justify-between gap-4">

                            <div>

                                <p class="text-xs font-semibold text-slate-500">
                                    Active
                                </p>

                                <p class="mt-2 text-3xl font-bold tracking-tight text-blue-600">
                                    {{ $active }}
                                </p>

                            </div>

                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-600">

                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="9"></circle>
                                    <path d="M12 7v5l3 2"></path>
                                </svg>

                            </div>

                        </div>

                        <p class="mt-2 text-xs text-slate-400">
                            Currently learning
                        </p>

                    </div>



                    {{-- COMPLETED --}}
                    <div class="pw-card pw-hover p-5">

                        <div class="flex items-start justify-between gap-4">

                            <div>

                                <p class="text-xs font-semibold text-slate-500">
                                    Completed
                                </p>

                                <p class="mt-2 text-3xl font-bold tracking-tight text-emerald-600">
                                    {{ $completed }}
                                </p>

                            </div>

                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                                ✓
                            </div>

                        </div>

                        <p class="mt-2 text-xs text-slate-400">
                            Finished courses
                        </p>

                    </div>



                    {{-- AVERAGE PROGRESS --}}
                    <div class="pw-card pw-hover p-5">

                        <div class="flex items-start justify-between gap-4">

                            <div>

                                <p class="text-xs font-semibold text-slate-500">
                                    Average Progress
                                </p>

                                <p class="mt-2 text-3xl font-bold tracking-tight text-orange-500">
                                    {{ number_format($averageProgress, 2) }}%
                                </p>

                            </div>

                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-orange-50 text-orange-500">

                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M3 3v18h18"></path>
                                    <path d="m7 16 4-5 4 3 5-7"></path>
                                </svg>

                            </div>

                        </div>

                        <p class="mt-2 text-xs text-slate-400">
                            Across all enrollments
                        </p>

                    </div>

                </section>



                {{-- =====================================================
                    ENROLLMENT TABLE
                ====================================================== --}}

                <section class="pw-card mt-6 overflow-hidden">

                    <div class="border-b border-slate-100 px-6 py-5">

                        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">

                            <div>

                                <h2 class="text-lg font-bold text-slate-900">
                                    Enrollment Progress
                                </h2>

                                <p class="mt-1 text-xs text-slate-500">
                                    Overview of each student's progress per course.
                                </p>

                            </div>

                        </div>

                    </div>

                    <div class="overflow-x-auto">

                        <table class="w-full min-w-[800px] text-left text-sm">

                            <thead class="border-b border-slate-100 bg-slate-50/80">

                                <tr class="text-[10px] font-bold uppercase tracking-[.08em] text-slate-400">

                                    <th class="px-6 py-4">Student</th>
                                    <th class="px-6 py-4">Course</th>
                                    <th class="px-6 py-4">Progress</th>
                                    <th class="px-6 py-4">Status</th>

                                </tr>

                            </thead>


                            <tbody class="divide-y divide-slate-100">

                                @forelse($enrollments as $enrollment)

                                    @php
                                        $progress = $enrollment->progress_percentage ?? 0;
                                        $status = strtolower($enrollment->status ?? 'active');
                                        $isCompleted = $status === 'completed';
                                    @endphp

                                    <tr class="transition hover:bg-slate-50/70">

                                        <td class="px-6 py-5">

                                            <div class="flex items-center gap-3">

                                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-violet-100 text-xs font-bold text-violet-700">
                                                    {{ strtoupper(substr($enrollment->student->name ?? 'S', 0, 1)) }}
                                                </div>

                                                <div class="min-w-0">

                                                    <p class="truncate text-sm font-semibold text-slate-800">
                                                        {{ $enrollment->student->name ?? 'Student' }}
                                                    </p>

                                                    <p class="mt-0.5 text-[11px] text-slate-400">
                                                        Learner
                                                    </p>

                                                </div>

                                            </div>

                                        </td>

                                        <td class="px-6 py-5">

                                            <p class="text-sm font-semibold text-slate-800">
                                                {{ $enrollment->course->title ?? 'Course unavailable' }}
                                            </p>

                                        </td>

                                        <td class="px-6 py-5">

                                            <div class="flex items-center gap-3">

                                                <div class="h-2 w-40 overflow-hidden rounded-full bg-slate-100">

                                                    <div
                                                        class="h-full rounded-full {{ $isCompleted ? 'bg-emerald-500' : 'bg-violet-500' }}"
                                                        style="width: {{ min($progress, 100) }}%"
                                                    ></div>

                                                </div>

                                                <span class="text-xs font-bold {{ $isCompleted ? 'text-emerald-600' : 'text-violet-600' }}">
                                                    {{ number_format($progress, 2) }}%
                                                </span>

                                            </div>

                                        </td>

                                        <td class="px-6 py-5">

                                            @if($isCompleted)

                                                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1.5 text-[11px] font-bold text-emerald-700">
                                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                                    Completed
                                                </span>

                                            @else

                                                <span class="inline-flex items-center gap-1.5 rounded-full bg-blue-50 px-3 py-1.5 text-[11px] font-bold text-blue-700">
                                                    <span class="h-1.5 w-1.5 rounded-full bg-blue-500"></span>
                                                    Active
                                                </span>

                                            @endif

                                        </td>

                                    </tr>

                                @empty

                                    <tr>

                                        <td colspan="4" class="px-6 py-16 text-center">

                                            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-violet-50 text-violet-600">

                                                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2"></path>
                                                    <circle cx="9" cy="7" r="4"></circle>
                                                    <path d="M22 21v-2a4 4 0 00-3-3.87"></path>
                                                </svg>

                                            </div>

                                            <h3 class="mt-4 text-sm font-bold text-slate-800">
                                                No student progress yet
                                            </h3>

                                            <p class="mt-1 text-xs text-slate-400">
                                                Student progress will appear once students enroll in courses.
                                            </p>

                                        </td>

                                    </tr>

                                @endforelse

                            </tbody>

                        </table>

                    </div>

                </section>

            </div>

        </main>

    </div>

</x-layouts::app>