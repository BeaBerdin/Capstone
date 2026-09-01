<x-layouts::app :title="'Teacher Dashboard'">
<div class="min-h-screen bg-slate-50/80 pb-12 text-slate-900">
    <div class="border-b border-slate-200 bg-white">
        <div class="mx-auto max-w-7xl px-4 py-7 sm:px-6 lg:px-8">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-violet-600">PathWise Teaching Portal</p>
                    <h1 class="mt-2 text-3xl font-black tracking-tight text-slate-950 sm:text-4xl">{{ $greeting }}, {{ auth()->user()->name }} 👋</h1>
                    <p class="mt-1 text-sm font-medium text-slate-400">{{ $currentDate }}</p>
                    <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-500">See what needs attention, continue building course content, and monitor how students are progressing.</p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('teacher.lessons.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 shadow-sm hover:border-violet-200 hover:text-violet-700">Manage Lessons</a>
                    <a href="{{ route('teacher.courses.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-violet-600 px-4 py-2.5 text-sm font-bold text-white shadow-sm shadow-violet-200 hover:bg-violet-700">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                        Create Course
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            <a href="{{ route('teacher.my-courses') }}" class="group rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                <div class="flex items-start justify-between"><div><p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Courses</p><p class="mt-2 text-3xl font-black text-slate-950">{{ $totalCourses }}</p></div><div class="flex h-10 w-10 items-center justify-center rounded-xl bg-violet-50 text-violet-600"><svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25A8.966 8.966 0 0118 3.75c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/></svg></div></div>
                <p class="mt-3 text-xs text-slate-400">{{ $publishedCourses }} published · {{ $pendingCourses }} pending</p>
            </a>
            <a href="{{ route('teacher.lessons.index') }}" class="group rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                <div class="flex items-start justify-between"><div><p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Lessons</p><p class="mt-2 text-3xl font-black text-slate-950">{{ $totalLessons }}</p></div><div class="flex h-10 w-10 items-center justify-center rounded-xl bg-sky-50 text-sky-600"><svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg></div></div>
                <p class="mt-3 text-xs text-slate-400">Videos, readings, documents, and quizzes</p>
            </a>
            <a href="{{ route('teacher.student-progress.index') }}" class="group rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                <div class="flex items-start justify-between"><div><p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Students</p><p class="mt-2 text-3xl font-black text-slate-950">{{ $totalStudents }}</p></div><div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600"><svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766a6.375 6.375 0 0111.964-3.179M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0z"/></svg></div></div>
                <p class="mt-3 text-xs text-slate-400">{{ $totalEnrollments }} total course enrollments</p>
            </a>
            <a href="{{ route('teacher.quiz-results.index') }}" class="group rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                <div class="flex items-start justify-between"><div><p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Avg. Quiz Score</p><p class="mt-2 text-3xl font-black {{ $averageScore >= 75 ? 'text-emerald-600' : 'text-slate-950' }}">{{ number_format($averageScore, 1) }}%</p></div><div class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-50 text-amber-600"><svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div></div>
                <p class="mt-3 text-xs text-slate-400">Across student quiz attempts</p>
            </a>
        </div>

        <div class="grid gap-6 lg:grid-cols-[1.35fr_.65fr]">
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                    <div><h2 class="text-lg font-extrabold text-slate-950">Recent courses</h2><p class="mt-1 text-sm text-slate-500">Continue building and publishing your latest course content.</p></div>
                    <a href="{{ route('teacher.my-courses') }}" class="text-sm font-bold text-violet-700 hover:text-violet-800">View all</a>
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse($recentCourses as $course)
                        <a href="{{ route('teacher.lessons', $course) }}" class="grid gap-4 p-4 transition hover:bg-slate-50 sm:grid-cols-[92px_1fr_auto] sm:items-center">
                            <div class="h-16 w-24 overflow-hidden rounded-xl bg-gradient-to-br from-violet-600 to-indigo-600">
                                @if($course->thumbnail)<img src="{{ asset('storage/' . $course->thumbnail) }}" class="h-full w-full object-cover" alt="">@else<div class="flex h-full items-center justify-center text-2xl font-black text-white">{{ strtoupper(substr($course->title,0,1)) }}</div>@endif
                            </div>
                            <div class="min-w-0"><div class="flex flex-wrap items-center gap-2"><h3 class="truncate text-sm font-extrabold text-slate-900">{{ $course->title }}</h3><span class="rounded-full px-2 py-0.5 text-[10px] font-bold {{ $course->status === 'published' ? 'bg-emerald-50 text-emerald-700' : ($course->status === 'pending' ? 'bg-amber-50 text-amber-700' : 'bg-slate-100 text-slate-600') }}">{{ ucfirst($course->status) }}</span></div><p class="mt-1 text-xs text-slate-400">{{ $course->category->name ?? 'General' }} · {{ $course->lessons_count }} lessons · {{ $course->enrollments_count }} students</p></div>
                            <span class="hidden text-sm font-bold text-violet-700 sm:block">Manage →</span>
                        </a>
                    @empty
                        <div class="p-10 text-center"><p class="font-bold text-slate-700">No courses yet.</p><a href="{{ route('teacher.courses.create') }}" class="mt-2 inline-block text-sm font-bold text-violet-700">Create your first course</a></div>
                    @endforelse
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div><h2 class="text-lg font-extrabold text-slate-950">Teaching snapshot</h2><p class="mt-1 text-sm text-slate-500">A quick health check for your courses.</p></div>
                <div class="mt-6 flex items-center gap-5">
                    <div class="relative flex h-28 w-28 shrink-0 items-center justify-center rounded-full" style="background: conic-gradient(rgb(124 58 237) {{ min(100, $averageProgress) }}%, rgb(241 245 249) 0);">
                        <div class="flex h-20 w-20 flex-col items-center justify-center rounded-full bg-white"><span class="text-2xl font-black text-slate-950">{{ number_format($averageProgress,0) }}%</span><span class="text-[10px] font-bold uppercase text-slate-400">Progress</span></div>
                    </div>
                    <div class="min-w-0 space-y-3 text-sm">
                        <div class="flex items-center justify-between gap-6"><span class="text-slate-500">Published</span><span class="font-extrabold text-slate-900">{{ $publishedCourses }}</span></div>
                        <div class="flex items-center justify-between gap-6"><span class="text-slate-500">Pending</span><span class="font-extrabold text-amber-600">{{ $pendingCourses }}</span></div>
                        <div class="flex items-center justify-between gap-6"><span class="text-slate-500">Draft</span><span class="font-extrabold text-slate-900">{{ $draftCourses }}</span></div>
                        <div class="flex items-center justify-between gap-6"><span class="text-slate-500">Completed enrollments</span><span class="font-extrabold text-emerald-600">{{ $completedEnrollments }}</span></div>
                    </div>
                </div>

                <div class="mt-6 rounded-2xl bg-gradient-to-br from-violet-600 to-indigo-700 p-4 text-white">
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-violet-100">PathWise tip</p>
                    <p class="mt-2 text-sm font-semibold leading-5">Course covers and short lesson summaries make the content library easier to scan for both teachers and students.</p>
                </div>
            </section>
        </div>

        <div class="grid gap-6 lg:grid-cols-[1fr_340px]">
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4"><div><h2 class="text-lg font-extrabold text-slate-950">Recent quiz activity</h2><p class="mt-1 text-sm text-slate-500">Latest results from quizzes in your courses.</p></div><a href="{{ route('teacher.quiz-results.index') }}" class="text-sm font-bold text-violet-700">View results</a></div>
                <div class="divide-y divide-slate-100">
                    @forelse($recentQuizResults as $result)
                        <div class="flex items-center gap-4 px-5 py-4"><div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-violet-50 text-sm font-black text-violet-700">{{ strtoupper(substr($result->student->name ?? 'S',0,1)) }}</div><div class="min-w-0 flex-1"><p class="truncate text-sm font-extrabold text-slate-900">{{ $result->student->name ?? 'Student' }}</p><p class="mt-0.5 truncate text-xs text-slate-400">{{ $result->quiz->title ?? 'Quiz' }} · {{ $result->quiz->course->title ?? 'Course' }}</p></div><div class="text-right"><p class="text-sm font-black {{ ($result->percentage ?? 0) >= 75 ? 'text-emerald-600' : 'text-rose-600' }}">{{ number_format($result->percentage ?? 0,0) }}%</p><p class="mt-0.5 text-[10px] font-bold uppercase text-slate-400">{{ ucfirst($result->remarks ?? 'result') }}</p></div></div>
                    @empty
                        <div class="px-5 py-10 text-center text-sm text-slate-400">No quiz attempts have been recorded yet.</div>
                    @endforelse
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-extrabold text-slate-950">Quick actions</h2>
                <div class="mt-4 grid grid-cols-2 gap-3">
                    <a href="{{ route('teacher.courses.create') }}" class="rounded-xl border border-slate-200 p-4 text-center transition hover:border-violet-200 hover:bg-violet-50"><div class="mx-auto flex h-10 w-10 items-center justify-center rounded-xl bg-violet-50 text-violet-600">＋</div><p class="mt-2 text-xs font-bold text-slate-700">Create Course</p></a>
                    <a href="{{ route('teacher.lessons.index') }}" class="rounded-xl border border-slate-200 p-4 text-center transition hover:border-sky-200 hover:bg-sky-50"><div class="mx-auto flex h-10 w-10 items-center justify-center rounded-xl bg-sky-50 text-sky-600">▤</div><p class="mt-2 text-xs font-bold text-slate-700">Add Lesson</p></a>
                    <a href="{{ route('teacher.quiz-results.index') }}" class="rounded-xl border border-slate-200 p-4 text-center transition hover:border-amber-200 hover:bg-amber-50"><div class="mx-auto flex h-10 w-10 items-center justify-center rounded-xl bg-amber-50 text-amber-600">✓</div><p class="mt-2 text-xs font-bold text-slate-700">Quiz Results</p></a>
                    <a href="{{ route('teacher.student-progress.index') }}" class="rounded-xl border border-slate-200 p-4 text-center transition hover:border-emerald-200 hover:bg-emerald-50"><div class="mx-auto flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">↗</div><p class="mt-2 text-xs font-bold text-slate-700">Progress</p></a>
                </div>
            </section>
        </div>
    </div>
</div>
</x-layouts::app>
