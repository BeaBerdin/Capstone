<x-layouts::app :title="'Teacher Dashboard'">

@php
    $teacherId = auth()->id();

    $publishedCourses = \App\Models\Course::where('teacher_id', $teacherId)->where('status', 'published')->count();
    $pendingCourses   = \App\Models\Course::where('teacher_id', $teacherId)->where('status', 'pending')->count();

    $averageScore = round(\App\Models\QuizResult::whereHas('quiz.course', fn($q) => $q->where('teacher_id', $teacherId))->avg('percentage') ?? 0, 1);

    $averageProgress = round(\App\Models\Enrollment::whereHas('course', fn($q) => $q->where('teacher_id', $teacherId))->avg('progress_percentage') ?? 0);

    $totalAssignments = \App\Models\Assignment::whereHas('course', fn($q) => $q->where('teacher_id', $teacherId))->count();

    $pendingGrading = \App\Models\Submission::whereHas('assignment.course', fn($q) => $q->where('teacher_id', $teacherId))->where('status', 'submitted')->count();

    $upcomingAssignments = \App\Models\Assignment::with('course')
        ->whereHas('course', fn($q) => $q->where('teacher_id', $teacherId))
        ->whereNotNull('due_date')->where('due_date', '>=', now())
        ->orderBy('due_date')->take(4)->get();

    $recentQuizResults = \App\Models\QuizResult::with(['student','quiz.course'])
        ->whereHas('quiz.course', fn($q) => $q->where('teacher_id', $teacherId))
        ->latest()->take(6)->get();

    $recentLessons = \App\Models\Lesson::with('course')
        ->whereHas('course', fn($q) => $q->where('teacher_id', $teacherId))
        ->latest()->take(3)->get();

    $recentCourses = \App\Models\Course::where('teacher_id', $teacherId)->latest()->take(3)->get();

    $activityItems = collect();
    foreach ($recentQuizResults as $r) {
        $activityItems->push([
            'type' => 'quiz',
            'title' => ($r->student->name ?? 'Student') . ' completed a quiz',
            'description' => ($r->quiz->title ?? 'Quiz') . ' • ' . ($r->quiz->course->title ?? 'Course'),
            'time' => $r->created_at,
            'score' => round($r->percentage ?? 0),
        ]);
    }
    foreach ($recentLessons as $l) {
        $activityItems->push([
            'type' => 'lesson',
            'title' => 'Lesson added: "' . $l->title . '"',
            'description' => $l->course->title ?? 'Course',
            'time' => $l->created_at,
            'score' => null,
        ]);
    }
    foreach ($recentCourses as $c) {
        $activityItems->push([
            'type' => 'course',
            'title' => 'Course created: "' . $c->title . '"',
            'description' => ucfirst($c->status ?? 'draft'),
            'time' => $c->created_at,
            'score' => null,
        ]);
    }
    $activityItems = $activityItems->sortByDesc('time')->take(4)->values();

    $chartResults = \App\Models\QuizResult::whereHas('quiz.course', fn($q) => $q->where('teacher_id', $teacherId))
        ->latest()->take(7)->pluck('percentage')->reverse()->values();

    $chartArray = $chartResults->map(fn($s) => max(0, min(100, (float) $s)))->all();
    while (count($chartArray) < 7) array_unshift($chartArray, $averageScore);
    $chartArray = array_slice($chartArray, -7);

    $chartWidth = 600; $chartHeight = 150;
    $chartPoints = collect($chartArray)->map(function ($score, $i) use ($chartWidth, $chartHeight) {
        return round($i * ($chartWidth / 6), 1) . ',' . round($chartHeight - (($score / 100) * $chartHeight), 1);
    })->implode(' ');

    $chartAreaPoints = '0,' . $chartHeight . ' ' . $chartPoints . ' ' . $chartWidth . ',' . $chartHeight;

    $hour = now()->hour;
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
    $teacherName = auth()->user()->name ?? 'Teacher';
    $firstName = explode(' ', trim($teacherName))[0];
    $currentDate = now()->format('l, F d, Y');
@endphp

<style>
    .pw-card {
        background: #ffffff;
        border: 1px solid #e8eaf0;
        border-radius: 18px;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.035);
    }
    .pw-card-hover {
        transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
    }
    .pw-card-hover:hover {
        transform: translateY(-2px);
        border-color: #ddd6fe;
        box-shadow: 0 12px 30px rgba(91, 70, 229, 0.08);
    }
    .pw-ring {
        position: relative;
        width: 165px;
        height: 165px;
        border-radius: 9999px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .pw-ring::after {
        content: "";
        position: absolute;
        width: 124px;
        height: 124px;
        border-radius: 9999px;
        background: white;
    }
    .pw-ring-content {
        position: relative;
        z-index: 2;
        text-align: center;
    }
</style>

<div class="min-h-screen bg-[#f8f9fc] text-slate-900">

    <main class="px-5 py-7 sm:px-6 lg:px-8 lg:py-8">
        <div class="mx-auto max-w-[1500px]">

            {{-- Welcome --}}
            <div class="mb-7 flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-950 sm:text-[28px]">
                        {{ $greeting }}, {{ $firstName }}! 👋
                    </h1>
                    <p class="mt-1.5 text-sm text-slate-500">Here's what's happening in your classes today.</p>
                    <p class="mt-1 text-xs font-medium text-slate-400">{{ $currentDate }}</p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <a href="{{ route('teacher.lessons.index') }}" class="inline-flex h-11 items-center gap-2 rounded-xl border border-slate-200 bg-white px-5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-violet-200 hover:text-violet-700">
                        <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 20h9M16.5 3.5a2.121 2.121 0 013 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                        Plan a Lesson
                    </a>
                    <a href="{{ route('teacher.courses.create') }}" class="inline-flex h-11 items-center gap-2 rounded-xl bg-gradient-to-r from-violet-600 to-indigo-600 px-5 text-sm font-semibold text-white shadow-md shadow-violet-200 transition hover:-translate-y-0.5 hover:shadow-lg">
                        <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>
                        Create Course
                    </a>
                </div>
            </div>

            {{-- Stats --}}
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                @php
                    $statCards = [
                        ['label' => 'My Courses', 'value' => $totalCourses, 'sub' => 'Active courses', 'icon' => 'M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25', 'color' => 'violet', 'route' => 'teacher.my-courses', 'link' => 'View all'],
                        ['label' => 'Total Students', 'value' => $totalStudents, 'sub' => 'Across all courses', 'icon' => 'M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2M9 11a4 4 0 100-8 4 4 0 000 8zm13 10v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75', 'color' => 'blue', 'route' => 'teacher.student-progress.index', 'link' => 'View all'],
                        ['label' => 'Assignments', 'value' => $totalAssignments, 'sub' => $pendingGrading . ' need grading', 'icon' => 'M9 11l3 3L22 4M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11', 'color' => 'emerald', 'route' => 'teacher.my-courses', 'link' => 'View courses'],
                        ['label' => 'Avg. Class Progress', 'value' => $averageProgress . '%', 'sub' => 'Across all enrollments', 'icon' => 'M4 19V9m5 10V5m5 14v-7m5 7V3', 'color' => 'orange', 'route' => 'teacher.student-progress.index', 'link' => 'View report'],
                    ];
                @endphp
                @foreach($statCards as $s)
                    <div class="pw-card pw-card-hover p-5">
                        <div class="flex items-start gap-4">
                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-{{ $s['color'] }}-100 text-{{ $s['color'] }}-600">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $s['icon'] }}"/></svg>
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-slate-600">{{ $s['label'] }}</p>
                                <p class="mt-1 text-3xl font-bold text-slate-950">{{ $s['value'] }}</p>
                                <p class="mt-1 text-xs text-slate-400">{{ $s['sub'] }}</p>
                            </div>
                        </div>
                        <a href="{{ route($s['route']) }}" class="mt-5 inline-flex items-center gap-1.5 text-sm font-semibold text-violet-700 hover:text-violet-900">
                            {{ $s['link'] }} <span>→</span>
                        </a>
                    </div>
                @endforeach
            </div>

            {{-- Class Overview + Upcoming --}}
            <div class="mt-5 grid grid-cols-1 gap-5 xl:grid-cols-[1.5fr_1fr]">

                {{-- Class Overview --}}
                <section class="pw-card p-5 sm:p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-lg font-bold text-slate-950">Class Overview</h2>
                            <p class="mt-1 text-xs text-slate-400">Overall learner performance</p>
                        </div>
                        <span class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-600">Recent performance</span>
                    </div>

                    <div class="mt-7 grid grid-cols-1 items-center gap-8 lg:grid-cols-[210px_1fr]">
                        {{-- Donut --}}
                        <div class="flex flex-col items-center">
                            <p class="mb-4 text-sm font-medium text-slate-700">Overall Progress</p>
                            <div class="pw-ring" style="background: conic-gradient(#6d4aff 0 {{ $averageProgress }}%, #ede9fe {{ $averageProgress }}% 100%);">
                                <div class="pw-ring-content">
                                    <p class="text-3xl font-bold text-slate-950">{{ $averageProgress }}%</p>
                                    <p class="mt-1 text-xs text-slate-400">Average</p>
                                </div>
                            </div>
                            <p class="mt-4 text-xs font-medium text-emerald-600">{{ $totalStudents }} active learners</p>
                        </div>

                        {{-- Line chart --}}
                        <div class="border-t border-slate-100 pt-6 lg:border-l lg:border-t-0 lg:pl-8 lg:pt-0">
                            <p class="mb-4 text-sm font-medium text-slate-700">Quiz Performance Trend</p>
                            <div class="relative h-[205px] w-full">
                                <div class="absolute inset-y-0 left-0 flex flex-col justify-between pb-7 text-[10px] text-slate-400">
                                    <span>100%</span><span>75%</span><span>50%</span><span>25%</span><span>0%</span>
                                </div>
                                <div class="absolute inset-y-0 left-10 right-0 pb-7">
                                    <div class="absolute inset-0 flex flex-col justify-between">
                                        @for($i = 0; $i < 5; $i++)<div class="border-t border-slate-100"></div>@endfor
                                    </div>
                                    <svg class="absolute inset-0 h-full w-full overflow-visible" preserveAspectRatio="none" viewBox="0 0 600 150">
                                        <defs>
                                            <linearGradient id="pwChartGradient" x1="0" y1="0" x2="0" y2="1">
                                                <stop offset="0%" stop-color="#7c3aed" stop-opacity=".20"/>
                                                <stop offset="100%" stop-color="#7c3aed" stop-opacity="0"/>
                                            </linearGradient>
                                        </defs>
                                        <polygon points="{{ $chartAreaPoints }}" fill="url(#pwChartGradient)"/>
                                        <polyline points="{{ $chartPoints }}" fill="none" stroke="#6d4aff" stroke-width="3" vector-effect="non-scaling-stroke" stroke-linecap="round" stroke-linejoin="round"/>
                                        @foreach($chartArray as $score)
                                            @php $cx = $loop->index * ($chartWidth / 6); $cy = $chartHeight - (($score / 100) * $chartHeight); @endphp
                                            <circle cx="{{ $cx }}" cy="{{ $cy }}" r="5" fill="#6d4aff"/>
                                        @endforeach
                                    </svg>
                                    <div class="absolute -bottom-1 left-0 right-0 flex justify-between text-[10px] text-slate-400">
                                        <span>1</span><span>2</span><span>3</span><span>4</span><span>5</span><span>6</span><span>7</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                {{-- Upcoming --}}
                <section class="pw-card overflow-hidden">
                    <div class="flex items-center justify-between border-b border-slate-100 px-5 py-5">
                        <div>
                            <h2 class="text-lg font-bold text-slate-950">Upcoming</h2>
                            <p class="mt-1 text-xs text-slate-400">Scheduled deadlines</p>
                        </div>
                        <span class="text-xs font-semibold text-violet-700">{{ $upcomingAssignments->count() }} items</span>
                    </div>
                    <div>
                        @forelse($upcomingAssignments as $a)
                            @php $due = \Carbon\Carbon::parse($a->due_date); @endphp
                            <div class="flex items-center gap-4 border-b border-slate-100 px-5 py-4 last:border-b-0">
                                <div class="w-[50px] shrink-0 overflow-hidden rounded-lg border border-slate-200 bg-white text-center">
                                    <div class="bg-violet-600 py-1 text-[9px] font-bold uppercase text-white">{{ $due->format('M') }}</div>
                                    <div class="py-1.5 text-lg font-bold leading-none text-slate-800">{{ $due->format('d') }}</div>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-semibold text-slate-800">{{ $a->title }}</p>
                                    <p class="mt-1 truncate text-xs text-slate-400">{{ $a->course->title ?? 'Course' }}</p>
                                </div>
                                <div class="shrink-0 text-right">
                                    <p class="text-xs font-medium text-slate-500">{{ $due->format('g:i A') }}</p>
                                </div>
                            </div>
                        @empty
                            <div class="px-6 py-14 text-center">
                                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-violet-50 text-violet-500">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 2v4m8-4v4M3 10h18M5 4h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V6a2 2 0 012-2z"/></svg>
                                </div>
                                <p class="mt-4 text-sm font-semibold text-slate-700">Nothing scheduled</p>
                                <p class="mt-1 text-xs text-slate-400">Upcoming assignments will appear here.</p>
                            </div>
                        @endforelse
                    </div>
                </section>
            </div>

            {{-- Bottom Section --}}
            <div class="mt-5 grid grid-cols-1 gap-5 xl:grid-cols-[1.5fr_1fr]">

                {{-- Recent Activities --}}
                <section class="pw-card overflow-hidden">
                    <div class="flex items-center justify-between border-b border-slate-100 px-5 py-5">
                        <div>
                            <h2 class="text-lg font-bold text-slate-950">Recent Activities</h2>
                            <p class="mt-1 text-xs text-slate-400">Latest activity from your courses</p>
                        </div>
                        <a href="{{ route('teacher.quiz-results.index') }}" class="text-xs font-semibold text-violet-700 hover:text-violet-900">View all →</a>
                    </div>

                    @forelse($activityItems as $a)
                        <div class="flex items-center gap-4 border-b border-slate-100 px-5 py-4 last:border-b-0">
                            @if($a['type'] === 'quiz')
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-emerald-50 text-emerald-600">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/></svg>
                                </div>
                            @elseif($a['type'] === 'lesson')
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-blue-50 text-blue-600">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 19.5A2.5 2.5 0 016.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"/></svg>
                                </div>
                            @else
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-violet-50 text-violet-600">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292"/><path d="M12 6.042A8.966 8.966 0 0118 3.75c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292"/></svg>
                                </div>
                            @endif

                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold text-slate-800">{{ $a['title'] }}</p>
                                <p class="mt-1 truncate text-xs text-slate-400">{{ $a['description'] }}</p>
                            </div>

                            @if($a['score'] !== null)
                                <div class="hidden shrink-0 sm:block">
                                    <span class="rounded-full {{ $a['score'] >= 75 ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-600' }} px-2.5 py-1 text-xs font-semibold">
                                        {{ $a['score'] }}%
                                    </span>
                                </div>
                            @endif

                            <p class="hidden min-w-[75px] shrink-0 text-right text-xs text-slate-400 md:block">
                                {{ $a['time']->diffForHumans() }}
                            </p>
                        </div>
                    @empty
                        <div class="py-14 text-center text-sm text-slate-400">No recent activities yet.</div>
                    @endforelse
                </section>

            </div>

        </div>
    </main>

</div>

</x-layouts::app>