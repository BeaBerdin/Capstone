<x-layouts::app :title="__('Quiz Results')">
@php
    $totalResults = $results->count();
    $passedResults = $results->where('remarks', 'passed')->count();
    $failedResults = $results->where('remarks', 'failed')->count();
    $averagePercentage = $totalResults ? $results->avg('percentage') : 0;
@endphp
<div class="min-h-screen bg-slate-50/80 pb-12 text-slate-900">
    <div class="border-b border-slate-200 bg-white">
        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div><p class="text-xs font-bold uppercase tracking-[0.2em] text-violet-600">Assessment Analytics</p><h1 class="mt-2 text-3xl font-black tracking-tight text-slate-950">Quiz Results</h1><p class="mt-2 text-sm text-slate-500">Review only the results from quizzes in your own courses.</p></div>
                <a href="{{ route('teacher.quiz-results.create') }}" class="inline-flex items-center justify-center rounded-xl bg-violet-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-violet-700">Record Result</a>
            </div>
        </div>
    </div>
    <div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        @if(session('success'))<div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ session('success') }}</div>@endif
        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Attempts</p><p class="mt-2 text-3xl font-black text-slate-950">{{ $totalResults }}</p></div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Passed</p><p class="mt-2 text-3xl font-black text-emerald-600">{{ $passedResults }}</p></div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Failed</p><p class="mt-2 text-3xl font-black text-rose-600">{{ $failedResults }}</p></div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Average</p><p class="mt-2 text-3xl font-black text-violet-600">{{ number_format($averagePercentage,1) }}%</p></div>
        </div>

        <form method="GET" class="grid gap-3 rounded-2xl border border-slate-200 bg-white p-3 shadow-sm md:grid-cols-[1fr_220px_170px_auto]">
            <input name="search" value="{{ request('search') }}" placeholder="Search student or quiz" class="h-11 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none focus:border-violet-400 focus:ring-4 focus:ring-violet-100">
            <select name="course" class="h-11 rounded-xl border border-slate-200 bg-slate-50 px-3 text-sm outline-none focus:border-violet-400 focus:ring-4 focus:ring-violet-100"><option value="all">All courses</option>@foreach($courses as $course)<option value="{{ $course->id }}" @selected((string)request('course')===(string)$course->id)>{{ $course->title }}</option>@endforeach</select>
            <select name="remarks" class="h-11 rounded-xl border border-slate-200 bg-slate-50 px-3 text-sm outline-none focus:border-violet-400 focus:ring-4 focus:ring-violet-100"><option value="all">All outcomes</option><option value="passed" @selected(request('remarks')==='passed')>Passed</option><option value="failed" @selected(request('remarks')==='failed')>Failed</option></select>
            <button class="h-11 rounded-xl bg-slate-900 px-5 text-sm font-bold text-white">Filter</button>
        </form>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-4"><h2 class="text-lg font-extrabold text-slate-950">Assessment records</h2><p class="mt-1 text-sm text-slate-500">Latest student attempts and performance.</p></div>
            <div class="overflow-x-auto"><table class="w-full text-left text-sm"><thead class="bg-slate-50 text-xs font-bold uppercase tracking-wide text-slate-500"><tr><th class="px-5 py-4">Student</th><th class="px-5 py-4">Quiz / Course</th><th class="px-5 py-4">Score</th><th class="px-5 py-4">Performance</th><th class="px-5 py-4">Outcome</th><th class="px-5 py-4 text-right">Actions</th></tr></thead><tbody class="divide-y divide-slate-100">
                @forelse($results as $result)
                    @php $percentage=$result->percentage??0; $isPassed=strtolower($result->remarks)==='passed'; @endphp
                    <tr class="hover:bg-slate-50/70"><td class="px-5 py-4"><div class="flex items-center gap-3"><div class="flex h-9 w-9 items-center justify-center rounded-full bg-violet-50 text-sm font-black text-violet-700">{{ strtoupper(substr($result->student->name??'S',0,1)) }}</div><div><p class="font-bold text-slate-900">{{ $result->student->name??'Student' }}</p><p class="text-xs text-slate-400">Attempt {{ $result->attempt_number }}</p></div></div></td><td class="px-5 py-4"><p class="font-semibold text-slate-800">{{ $result->quiz->title??'Quiz' }}</p><p class="text-xs text-slate-400">{{ $result->quiz->course->title??'Course' }}</p></td><td class="px-5 py-4 font-extrabold text-slate-900">{{ $result->score }}/{{ $result->total_items }}</td><td class="px-5 py-4"><div class="flex min-w-40 items-center gap-3"><div class="h-2 flex-1 rounded-full bg-slate-100"><div class="h-2 rounded-full {{ $isPassed?'bg-emerald-500':'bg-rose-500' }}" style="width:{{ min($percentage,100) }}%"></div></div><span class="w-12 text-right font-bold text-slate-700">{{ number_format($percentage,0) }}%</span></div></td><td class="px-5 py-4"><span class="rounded-full px-3 py-1 text-xs font-bold {{ $isPassed?'bg-emerald-50 text-emerald-700':'bg-rose-50 text-rose-700' }}">{{ $isPassed?'Passed':'Failed' }}</span></td><td class="px-5 py-4 text-right"><div class="flex justify-end gap-2"><a href="{{ route('teacher.quiz-results.edit',$result) }}" class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-bold text-slate-700 hover:bg-slate-50">Edit</a><form action="{{ route('teacher.quiz-results.destroy',$result) }}" method="POST" onsubmit="return confirm('Delete this result?')">@csrf @method('DELETE')<button class="rounded-lg border border-rose-100 px-3 py-1.5 text-xs font-bold text-rose-600 hover:bg-rose-50">Delete</button></form></div></td></tr>
                @empty <tr><td colspan="6" class="px-6 py-14 text-center text-sm text-slate-400">No quiz results match the current filters.</td></tr>@endforelse
            </tbody></table></div>
        </div>
    </div>
</div>
</x-layouts::app>
