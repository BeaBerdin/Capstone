<x-layouts::app :title="__('Lessons')">
<div class="min-h-screen bg-slate-50/80 pb-12 text-slate-900">
    <div class="border-b border-slate-200 bg-white">
        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <div class="flex flex-col gap-5 md:flex-row md:items-end md:justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-violet-600">Content Library</p>
                    <h1 class="mt-2 text-3xl font-black tracking-tight text-slate-950 sm:text-4xl">Lessons</h1>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">Choose a course to organize its videos, readings, documents, quizzes, and lesson content.</p>
                </div>
                <a href="{{ route('teacher.courses.create') }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 shadow-sm hover:border-violet-200 hover:text-violet-700">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    New Course
                </a>
            </div>
        </div>
    </div>

    <div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        <form method="GET" action="{{ route('teacher.lessons.index') }}" class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-3 shadow-sm sm:flex-row">
            <label class="relative flex-1">
                <span class="sr-only">Search courses</span>
                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35m1.35-5.65a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input name="search" value="{{ request('search') }}" placeholder="Search your courses" class="h-11 w-full rounded-xl border border-slate-200 bg-slate-50 pl-10 pr-4 text-sm outline-none focus:border-violet-400 focus:bg-white focus:ring-4 focus:ring-violet-100">
            </label>
            <button class="h-11 rounded-xl bg-slate-900 px-5 text-sm font-bold text-white hover:bg-slate-800">Search</button>
            @if(request('search'))
                <a href="{{ route('teacher.lessons.index') }}" class="inline-flex h-11 items-center justify-center rounded-xl border border-slate-200 px-5 text-sm font-semibold text-slate-600 hover:bg-slate-50">Clear</a>
            @endif
        </form>

        <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
            @forelse($courses as $course)
                @php
                    $published = $course->lessons->where('is_published', true)->count();
                    $videos = $course->lessons->where('lesson_type', 'video')->count();
                    $readings = $course->lessons->whereIn('lesson_type', ['text','document'])->count();
                    $quizzes = $course->lessons->where('lesson_type', 'quiz')->count();
                @endphp
                <article class="group overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition duration-200 hover:-translate-y-1 hover:shadow-xl hover:shadow-slate-200/60">
                    <div class="relative h-40 overflow-hidden bg-gradient-to-br from-violet-600 via-indigo-600 to-sky-500">
                        @if($course->thumbnail)
                            <img src="{{ asset('storage/' . $course->thumbnail) }}" alt="{{ $course->title }}" class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                            <div class="absolute inset-0 bg-gradient-to-t from-slate-950/60 via-transparent to-transparent"></div>
                        @else
                            <div class="absolute -right-10 -top-10 h-36 w-36 rounded-full bg-white/10"></div>
                            <div class="absolute inset-0 flex items-center justify-center">
                                <svg class="h-14 w-14 text-white/90" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25A8.966 8.966 0 0118 3.75c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/></svg>
                            </div>
                        @endif
                        <div class="absolute bottom-3 left-3 flex items-center gap-2">
                            <span class="rounded-full bg-white/95 px-2.5 py-1 text-[10px] font-extrabold uppercase tracking-wide text-violet-700">{{ $course->category->name ?? 'Course' }}</span>
                            <span class="rounded-full bg-slate-950/60 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-white backdrop-blur">{{ $course->lessons_count }} lessons</span>
                        </div>
                    </div>

                    <div class="p-5">
                        <h2 class="line-clamp-2 text-lg font-extrabold leading-snug text-slate-950">{{ $course->title }}</h2>
                        <p class="mt-2 line-clamp-2 min-h-10 text-sm leading-5 text-slate-500">{{ $course->description }}</p>

                        <div class="mt-4 flex flex-wrap gap-2 text-xs font-semibold">
                            <span class="rounded-full bg-violet-50 px-2.5 py-1 text-violet-700">▶ {{ $videos }} video</span>
                            <span class="rounded-full bg-sky-50 px-2.5 py-1 text-sky-700">▤ {{ $readings }} reading</span>
                            <span class="rounded-full bg-amber-50 px-2.5 py-1 text-amber-700">? {{ $quizzes }} quiz</span>
                        </div>

                        <div class="mt-5 flex items-center justify-between border-t border-slate-100 pt-4">
                            <div>
                                <p class="text-xs font-medium text-slate-400">Published</p>
                                <p class="text-sm font-extrabold text-slate-800">{{ $published }} of {{ $course->lessons_count }}</p>
                            </div>
                            <a href="{{ route('teacher.lessons', $course) }}" class="inline-flex items-center gap-2 rounded-xl bg-violet-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-violet-700">
                                Manage Lessons
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                            </a>
                        </div>
                    </div>
                </article>
            @empty
                <div class="col-span-full rounded-3xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center">
                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-violet-50 text-violet-600">
                        <svg class="h-8 w-8" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25A8.966 8.966 0 0118 3.75c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/></svg>
                    </div>
                    <h3 class="mt-4 text-xl font-extrabold text-slate-950">No courses found</h3>
                    <p class="mt-2 text-sm text-slate-500">Create a course first, then add visual and content-rich lessons.</p>
                    <a href="{{ route('teacher.courses.create') }}" class="mt-5 inline-flex rounded-xl bg-violet-600 px-5 py-3 text-sm font-bold text-white hover:bg-violet-700">Create Course</a>
                </div>
            @endforelse
        </div>
    </div>
</div>
</x-layouts::app>
