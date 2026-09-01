<x-layouts::app :title="'Quiz Results'">

@php
    $totalAttempts   = $results->count();
    $passedAttempts  = $results->where('remarks', 'passed')->count();
    $failedAttempts  = $results->where('remarks', 'failed')->count();
    $averageScore    = $totalAttempts > 0 ? $results->avg(fn ($r) => (float) $r->percentage) : 0;
    $passRate        = $totalAttempts > 0 ? ($passedAttempts / $totalAttempts) * 100 : 0;
    $uniqueStudents  = $results->pluck('student_id')->unique()->count();

    $teacherCourses = $results->map(fn ($r) => $r->quiz?->course)->filter()->unique('id')->sortBy('title')->values();
    $teacherQuizzes = $results->map(fn ($r) => $r->quiz)->filter()->unique('id')->sortBy('title')->values();
@endphp

<style>
    .pw-card {
        background: #fff;
        border: 1px solid #e7e9ef;
        border-radius: 20px;
        box-shadow: 0 1px 3px rgba(15,23,42,.035);
    }
    .pw-control {
        width: 100%;
        border: 1px solid #e1e4ea;
        border-radius: 12px;
        background: #fff;
        color: #334155;
        font-size: 13px;
        transition: all 160ms ease;
    }
    .pw-control:focus {
        outline: none !important;
        border-color: #8b5cf6 !important;
        box-shadow: 0 0 0 4px rgba(139,92,246,.08) !important;
    }
</style>

<div class="min-h-screen bg-[#f8f9fc]">
    <main class="px-5 py-7 sm:px-6 lg:px-8 lg:py-9">
        <div class="mx-auto max-w-[1500px]">

            {{-- Header --}}
            <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[.12em] text-violet-600">Student Performance</p>
                    <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-950">Quiz Results</h1>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">
                        Review student quiz attempts, scores, pass rates, and assessment performance across your courses.
                    </p>
                </div>
                <a href="{{ route('teacher.lessons.index') }}" class="inline-flex h-11 self-start items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-600 transition hover:border-violet-200 hover:text-violet-700">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 19.5A2.5 2.5 0 016.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"/></svg>
                    Course Content
                </a>
            </div>

            @if(session('success'))
                <div class="mt-6 flex items-center gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-medium text-emerald-700">
                    <div class="flex h-7 w-7 items-center justify-center rounded-full bg-emerald-100">✓</div>
                    {{ session('success') }}
                </div>
            @endif

            {{-- Stats --}}
            <section class="mt-7 grid grid-cols-2 gap-4 xl:grid-cols-4">
                @php
                    $stats = [
                        ['label' => 'Total Attempts', 'value' => $totalAttempts, 'sub' => 'Submitted quiz attempts', 'color' => 'violet', 'icon' => 'M9 11l3 3L22 4M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11'],
                        ['label' => 'Average Score', 'value' => number_format($averageScore, 1).'%', 'sub' => 'Across all quiz attempts', 'color' => 'blue', 'icon' => 'M3 3v18h18m-7 7-4-5 4 3 5-7'],
                        ['label' => 'Pass Rate', 'value' => number_format($passRate, 1).'%', 'sub' => $passedAttempts.' passed • '.$failedAttempts.' failed', 'color' => 'emerald', 'icon' => null],
                        ['label' => 'Students Assessed', 'value' => $uniqueStudents, 'sub' => 'Unique participating students', 'color' => 'orange', 'icon' => 'M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2M9 11a4 4 0 100-8 4 4 0 000 8zm13 10v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75'],
                    ];
                @endphp
                @foreach($stats as $s)
                    <div class="pw-card p-5">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-xs font-semibold text-slate-500">{{ $s['label'] }}</p>
                                <p class="mt-2 text-3xl font-bold tracking-tight {{ $s['color'] === 'emerald' ? 'text-emerald-600' : ($s['color'] === 'blue' ? 'text-blue-600' : ($s['color'] === 'orange' ? 'text-orange-500' : 'text-slate-950')) }}">{{ $s['value'] }}</p>
                            </div>
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-{{ $s['color'] }}-50 text-{{ $s['color'] === 'emerald' ? 'emerald' : ($s['color'] === 'blue' ? 'blue' : ($s['color'] === 'orange' ? 'orange' : 'violet')) }}-600">
                                @if($s['icon'])
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $s['icon'] }}"/></svg>
                                @else
                                    ✓
                                @endif
                            </div>
                        </div>
                        <p class="mt-2 text-xs text-slate-400">{{ $s['sub'] }}</p>
                    </div>
                @endforeach
            </section>

            {{-- Results Table --}}
            <section class="pw-card mt-6 overflow-hidden">
                <div class="border-b border-slate-100 p-5 sm:p-6">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <h2 class="text-lg font-bold text-slate-900">Assessment Records</h2>
                            <p class="mt-1 text-xs text-slate-500">Student attempts from your course quizzes.</p>
                        </div>
                        <p id="resultCounter" class="text-xs font-semibold text-slate-400">
                            {{ $totalAttempts }} {{ \Illuminate\Support\Str::plural('result', $totalAttempts) }}
                        </p>
                    </div>

                    {{-- Filters --}}
                    <div class="mt-5 grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-[minmax(240px,1fr)_200px_200px_160px_auto]">
                        <div class="relative">
                            <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                            <input type="search" id="resultSearch" placeholder="Search student or quiz..." class="pw-control h-11 pl-10 pr-4">
                        </div>
                        <select id="courseFilter" class="pw-control h-11 px-3">
                            <option value="">All Courses</option>
                            @foreach($teacherCourses as $course)
                                <option value="{{ $course->id }}">{{ $course->title }}</option>
                            @endforeach
                        </select>
                        <select id="quizFilter" class="pw-control h-11 px-3">
                            <option value="">All Quizzes</option>
                            @foreach($teacherQuizzes as $q)
                                <option value="{{ $q->id }}">{{ $q->title }}</option>
                            @endforeach
                        </select>
                        <select id="statusFilter" class="pw-control h-11 px-3">
                            <option value="">All Results</option>
                            <option value="passed">Passed</option>
                            <option value="failed">Failed</option>
                        </select>
                        <button type="button" id="clearFilters" class="inline-flex h-11 items-center justify-center rounded-xl border border-slate-200 bg-white px-4 text-xs font-semibold text-slate-600 transition hover:bg-slate-50">Reset</button>
                    </div>
                </div>

                @if($results->isNotEmpty())
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[1050px] text-left">
                            <thead class="border-b border-slate-100 bg-slate-50/80">
                                <tr class="text-[10px] font-bold uppercase tracking-[.08em] text-slate-400">
                                    <th class="px-6 py-4">Student</th>
                                    <th class="px-6 py-4">Assessment</th>
                                    <th class="px-6 py-4">Attempt</th>
                                    <th class="px-6 py-4">Score</th>
                                    <th class="px-6 py-4">Performance</th>
                                    <th class="px-6 py-4">Result</th>
                                    <th class="px-6 py-4">Submitted</th>
                                </tr>
                            </thead>
                            <tbody id="resultsTableBody" class="divide-y divide-slate-100">
                                @foreach($results as $result)
                                    @php
                                        $studentName  = $result->student?->name ?? 'Unknown Student';
                                        $studentEmail = $result->student?->email ?? '';
                                        $course       = $result->quiz?->course;
                                        $quiz         = $result->quiz;
                                        $percentage   = (float) ($result->percentage ?? 0);
                                        $isPassed     = strtolower($result->remarks ?? '') === 'passed';
                                        $parts        = collect(preg_split('/\s+/', trim($studentName)))->filter()->take(2);
                                        $initials     = $parts->map(fn ($p) => strtoupper(substr($p, 0, 1)))->implode('') ?: 'S';
                                        $searchText   = strtolower($studentName.' '.$studentEmail.' '.($course?->title ?? '').' '.($quiz?->title ?? ''));
                                        $completedAt  = $result->completed_at ? \Carbon\Carbon::parse($result->completed_at) : null;
                                    @endphp

                                    <tr class="result-row transition hover:bg-slate-50/70"
                                        data-search="{{ $searchText }}"
                                        data-course="{{ $course?->id }}"
                                        data-quiz="{{ $quiz?->id }}"
                                        data-status="{{ strtolower($result->remarks ?? '') }}">

                                        {{-- Student --}}
                                        <td class="px-6 py-5">
                                            <div class="flex items-center gap-3">
                                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-violet-100 text-xs font-bold text-violet-700">{{ $initials }}</div>
                                                <div class="min-w-0">
                                                    <p class="max-w-[190px] truncate text-sm font-semibold text-slate-800">{{ $studentName }}</p>
                                                    <p class="mt-0.5 max-w-[190px] truncate text-[11px] text-slate-400">{{ $studentEmail ?: 'Student' }}</p>
                                                </div>
                                            </div>
                                        </td>

                                        {{-- Assessment --}}
                                        <td class="px-6 py-5">
                                            <p class="max-w-[220px] truncate text-sm font-semibold text-slate-800">{{ $quiz?->title ?? 'Quiz' }}</p>
                                            <p class="mt-1 max-w-[220px] truncate text-[11px] text-slate-400">{{ $course?->title ?? 'Course unavailable' }}</p>
                                        </td>

                                        {{-- Attempt --}}
                                        <td class="px-6 py-5">
                                            <span class="inline-flex items-center justify-center rounded-lg bg-slate-100 px-2.5 py-1.5 text-xs font-semibold text-slate-600">Attempt {{ $result->attempt_number ?? 1 }}</span>
                                        </td>

                                        {{-- Score --}}
                                        <td class="px-6 py-5">
                                            <p class="text-sm font-bold text-slate-800">{{ $result->score ?? 0 }}<span class="font-medium text-slate-400"> / {{ $result->total_items ?? 0 }}</span></p>
                                        </td>

                                        {{-- Performance --}}
                                        <td class="px-6 py-5">
                                            <div class="flex items-center gap-3">
                                                <div class="h-2 w-24 overflow-hidden rounded-full bg-slate-100">
                                                    <div class="h-full rounded-full {{ $isPassed ? 'bg-emerald-500' : 'bg-red-400' }}" style="width: {{ min(max($percentage, 0), 100) }}%"></div>
                                                </div>
                                                <span class="min-w-[48px] text-xs font-bold {{ $isPassed ? 'text-emerald-600' : 'text-red-500' }}">{{ number_format($percentage, 1) }}%</span>
                                            </div>
                                        </td>

                                        {{-- Status --}}
                                        <td class="px-6 py-5">
                                            @if($isPassed)
                                                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1.5 text-[11px] font-bold text-emerald-700"><span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>Passed</span>
                                            @else
                                                <span class="inline-flex items-center gap-1.5 rounded-full bg-red-50 px-3 py-1.5 text-[11px] font-bold text-red-600"><span class="h-1.5 w-1.5 rounded-full bg-red-500"></span>Failed</span>
                                            @endif
                                        </td>

                                        {{-- Date --}}
                                        <td class="px-6 py-5">
                                            @if($completedAt)
                                                <p class="text-xs font-semibold text-slate-600">{{ $completedAt->format('M d, Y') }}</p>
                                                <p class="mt-1 text-[11px] text-slate-400">{{ $completedAt->format('g:i A') }}</p>
                                            @else
                                                <span class="text-xs text-slate-400">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div id="noFilteredResults" class="hidden border-t border-slate-100 px-6 py-14 text-center">
                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                        </div>
                        <h3 class="mt-3 text-sm font-bold text-slate-800">No matching results</h3>
                        <p class="mt-1 text-xs text-slate-400">Try changing your search or filters.</p>
                    </div>

                @else
                    {{-- Empty --}}
                    <div class="px-6 py-20 text-center">
                        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-violet-50 text-violet-600">
                            <svg class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/></svg>
                        </div>
                        <h3 class="mt-4 text-base font-bold text-slate-900">No quiz results yet</h3>
                        <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-slate-500">Student quiz attempts will automatically appear here after learners complete assessments from your courses.</p>
                        <a href="{{ route('teacher.lessons.index') }}" class="mt-5 inline-flex h-11 items-center justify-center rounded-xl bg-violet-600 px-5 text-sm font-semibold text-white transition hover:bg-violet-700">Manage Course Content</a>
                    </div>
                @endif
            </section>
        </div>
    </main>
</div>

<script>
    function initializeQuizResultsPage() {
        const searchInput  = document.getElementById('resultSearch');
        const courseFilter = document.getElementById('courseFilter');
        const quizFilter   = document.getElementById('quizFilter');
        const statusFilter = document.getElementById('statusFilter');
        const clearButton  = document.getElementById('clearFilters');
        const counter      = document.getElementById('resultCounter');
        const empty        = document.getElementById('noFilteredResults');
        const rows         = Array.from(document.querySelectorAll('.result-row'));

        if (!searchInput || rows.length === 0) return;

        function filterResults() {
            const search = searchInput.value.trim().toLowerCase();
            const course = courseFilter.value;
            const quiz   = quizFilter.value;
            const status = statusFilter.value;
            let visible  = 0;

            rows.forEach(function (row) {
                const matchesSearch = !search || row.dataset.search.includes(search);
                const matchesCourse = !course || row.dataset.course === course;
                const matchesQuiz   = !quiz   || row.dataset.quiz === quiz;
                const matchesStatus = !status || row.dataset.status === status;
                const show = matchesSearch && matchesCourse && matchesQuiz && matchesStatus;

                row.classList.toggle('hidden', !show);
                if (show) visible++;
            });

            if (counter) counter.textContent = visible + (visible === 1 ? ' result' : ' results');
            if (empty)   empty.classList.toggle('hidden', visible !== 0);
        }

        searchInput.addEventListener('input', filterResults);
        courseFilter.addEventListener('change', filterResults);
        quizFilter.addEventListener('change', filterResults);
        statusFilter.addEventListener('change', filterResults);

        if (clearButton) {
            clearButton.addEventListener('click', function () {
                searchInput.value = '';
                courseFilter.value = '';
                quizFilter.value = '';
                statusFilter.value = '';
                filterResults();
            });
        }

        filterResults();
    }

    document.addEventListener('DOMContentLoaded', initializeQuizResultsPage);
    document.addEventListener('livewire:navigated', initializeQuizResultsPage);
</script>

</x-layouts::app>