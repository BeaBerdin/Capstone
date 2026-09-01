<x-layouts::app :title="__('My Courses')">
@php
    $totalCourses = $courses->count();
    $publishedCourses = $courses->where('status', 'published')->count();
    $pendingCourses = $courses->where('status', 'pending')->count();
    $totalStudents = $courses->sum('enrollments_count');
@endphp

<div class="min-h-screen bg-slate-50/80 pb-12 text-slate-900">
    <div class="border-b border-slate-200 bg-white">
        <div class="mx-auto flex max-w-7xl flex-col gap-5 px-4 py-8 sm:px-6 lg:flex-row lg:items-end lg:justify-between lg:px-8">
            <div>
                <div class="mb-2 flex items-center gap-2 text-xs font-bold uppercase tracking-[0.2em] text-violet-600">
                    <span class="h-2 w-2 rounded-full bg-violet-600"></span>
                    PathWise Teaching Portal
                </div>
                <h1 class="text-3xl font-black tracking-tight text-slate-950 sm:text-4xl">My Courses</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">
                    Build, organize, and publish learning experiences. Course covers now appear throughout PathWise so students can recognize courses faster.
                </p>
            </div>

            <a href="{{ route('teacher.courses.create') }}"
               class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-violet-600 px-5 py-3 text-sm font-bold text-white shadow-sm shadow-violet-200 transition hover:-translate-y-0.5 hover:bg-violet-700">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                Create Course
            </a>
        </div>
    </div>

    <div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        @if(session('success'))
            <div class="flex items-start gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
                <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                {{ session('success') }}
            </div>
        @endif

        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            @foreach([
                ['label' => 'Total Courses', 'value' => $totalCourses, 'hint' => 'All course drafts and published courses', 'icon' => 'book', 'tone' => 'violet'],
                ['label' => 'Published', 'value' => $publishedCourses, 'hint' => 'Visible to students', 'icon' => 'check', 'tone' => 'emerald'],
                ['label' => 'Pending', 'value' => $pendingCourses, 'hint' => 'Waiting for approval', 'icon' => 'clock', 'tone' => 'amber'],
                ['label' => 'Students', 'value' => $totalStudents, 'hint' => 'Enrollments across your courses', 'icon' => 'users', 'tone' => 'blue'],
            ] as $stat)
                @php
                    $tone = match($stat['tone']) {
                        'emerald' => 'bg-emerald-50 text-emerald-600 ring-emerald-100',
                        'amber' => 'bg-amber-50 text-amber-600 ring-amber-100',
                        'blue' => 'bg-blue-50 text-blue-600 ring-blue-100',
                        default => 'bg-violet-50 text-violet-600 ring-violet-100',
                    };
                @endphp
                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $stat['label'] }}</p>
                            <p class="mt-2 text-3xl font-black tracking-tight text-slate-950">{{ $stat['value'] }}</p>
                        </div>
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl ring-1 {{ $tone }}">
                            @if($stat['icon'] === 'check')
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            @elseif($stat['icon'] === 'clock')
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2m5-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            @elseif($stat['icon'] === 'users')
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766a6.375 6.375 0 0111.964-3.179M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0z"/></svg>
                            @else
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25A8.966 8.966 0 0118 3.75c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/></svg>
                            @endif
                        </div>
                    </div>
                    <p class="mt-3 hidden text-xs text-slate-400 sm:block">{{ $stat['hint'] }}</p>
                </div>
            @endforeach
        </div>

        <form method="GET" action="{{ route('teacher.my-courses') }}" class="rounded-2xl border border-slate-200 bg-white p-3 shadow-sm">
            <div class="grid gap-3 md:grid-cols-[1fr_180px_220px_auto]">
                <label class="relative block">
                    <span class="sr-only">Search courses</span>
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35m1.35-5.65a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input name="search" value="{{ request('search') }}" placeholder="Search by course title or description"
                           class="h-11 w-full rounded-xl border border-slate-200 bg-slate-50 pl-10 pr-4 text-sm text-slate-800 outline-none transition focus:border-violet-400 focus:bg-white focus:ring-4 focus:ring-violet-100">
                </label>

                <select name="status" class="h-11 rounded-xl border border-slate-200 bg-slate-50 px-3 text-sm text-slate-700 outline-none focus:border-violet-400 focus:ring-4 focus:ring-violet-100">
                    <option value="all">All status</option>
                    @foreach(['draft','pending','published','approved','rejected'] as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>

                <select name="category" class="h-11 rounded-xl border border-slate-200 bg-slate-50 px-3 text-sm text-slate-700 outline-none focus:border-violet-400 focus:ring-4 focus:ring-violet-100">
                    <option value="all">All categories</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) request('category') === (string) $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>

                <div class="flex gap-2">
                    <button class="h-11 flex-1 rounded-xl bg-slate-900 px-4 text-sm font-bold text-white transition hover:bg-slate-800">Filter</button>
                    @if(request()->hasAny(['search','status','category']))
                        <a href="{{ route('teacher.my-courses') }}" class="inline-flex h-11 items-center justify-center rounded-xl border border-slate-200 px-4 text-sm font-semibold text-slate-600 hover:bg-slate-50">Clear</a>
                    @endif
                </div>
            </div>
        </form>

        <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
            @forelse($courses as $course)
                @php
                    $statusClasses = match(strtolower($course->status)) {
                        'published' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
                        'pending' => 'bg-amber-50 text-amber-700 ring-amber-200',
                        'rejected' => 'bg-rose-50 text-rose-700 ring-rose-200',
                        'approved' => 'bg-blue-50 text-blue-700 ring-blue-200',
                        default => 'bg-slate-100 text-slate-600 ring-slate-200',
                    };
                @endphp

                <article class="group overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition duration-200 hover:-translate-y-1 hover:shadow-xl hover:shadow-slate-200/70">
                    <div class="relative h-44 overflow-hidden bg-gradient-to-br from-violet-600 via-indigo-600 to-blue-500">
                        @if($course->thumbnail)
                            <img src="{{ asset('storage/' . $course->thumbnail) }}" alt="{{ $course->title }} cover" class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                            <div class="absolute inset-0 bg-gradient-to-t from-slate-950/60 via-transparent to-transparent"></div>
                        @else
                            <div class="absolute -right-6 -top-8 h-36 w-36 rounded-full bg-white/10"></div>
                            <div class="absolute -bottom-12 -left-8 h-40 w-40 rounded-full bg-fuchsia-400/20"></div>
                            <div class="absolute inset-0 flex items-center justify-center">
                                <div class="flex h-20 w-20 items-center justify-center rounded-3xl bg-white/15 text-4xl font-black text-white ring-1 ring-white/20 backdrop-blur-sm">
                                    {{ strtoupper(substr($course->title, 0, 1)) }}
                                </div>
                            </div>
                        @endif

                        <span class="absolute left-4 top-4 rounded-full px-3 py-1 text-[11px] font-bold ring-1 ring-inset {{ $statusClasses }}">
                            {{ ucfirst($course->status) }}
                        </span>
                        <a href="{{ route('teacher.courses.edit', $course) }}" class="absolute right-4 top-4 inline-flex h-9 w-9 items-center justify-center rounded-full bg-white/90 text-slate-700 shadow-sm backdrop-blur transition hover:bg-white" aria-label="Edit course">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13l-2.685.895.895-2.685a4.5 4.5 0 011.13-1.897L16.862 4.487zM19.5 7.125L16.862 4.487M18 14.25V19.5A1.5 1.5 0 0116.5 21h-12A1.5 1.5 0 013 19.5v-12A1.5 1.5 0 014.5 6H9.75"/></svg>
                        </a>
                    </div>

                    <div class="p-5">
                        <div class="flex items-center gap-2 text-xs font-semibold text-violet-600">
                            <span>{{ $course->category->name ?? 'General' }}</span>
                            <span class="text-slate-300">•</span>
                            <span class="capitalize text-slate-500">{{ $course->difficulty_level ?? 'beginner' }}</span>
                        </div>

                        <h2 class="mt-2 line-clamp-2 text-lg font-extrabold leading-snug text-slate-950">{{ $course->title }}</h2>
                        <p class="mt-2 line-clamp-2 min-h-10 text-sm leading-5 text-slate-500">{{ $course->description }}</p>

                        <div class="mt-5 grid grid-cols-3 gap-2 border-y border-slate-100 py-4 text-center">
                            <div>
                                <p class="text-lg font-black text-slate-950">{{ $course->lessons_count }}</p>
                                <p class="text-[11px] font-medium uppercase tracking-wide text-slate-400">Lessons</p>
                            </div>
                            <div class="border-x border-slate-100">
                                <p class="text-lg font-black text-slate-950">{{ $course->enrollments_count }}</p>
                                <p class="text-[11px] font-medium uppercase tracking-wide text-slate-400">Students</p>
                            </div>
                            <div>
                                <p class="text-lg font-black text-slate-950">{{ (float) $course->price > 0 ? '₱'.number_format($course->price, 0) : 'Free' }}</p>
                                <p class="text-[11px] font-medium uppercase tracking-wide text-slate-400">Price</p>
                            </div>
                        </div>

                        <div class="mt-4 flex gap-2">
                            <a href="{{ route('teacher.lessons', $course) }}" class="inline-flex flex-1 items-center justify-center gap-2 rounded-xl bg-violet-600 px-3 py-2.5 text-sm font-bold text-white transition hover:bg-violet-700">
                                Manage Lessons
                            </a>
                            <a href="{{ route('teacher.course.students', $course) }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200 px-3 py-2.5 text-sm font-bold text-slate-700 transition hover:border-violet-200 hover:bg-violet-50 hover:text-violet-700" title="View students">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766a6.375 6.375 0 0111.964-3.179M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0z"/></svg>
                            </a>
                        </div>

                        @if(in_array($course->status, ['draft', 'rejected']))
                            <form action="{{ route('teacher.courses.submit', $course) }}" method="POST" class="mt-2">
                                @csrf
                                <button class="w-full rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2.5 text-sm font-bold text-emerald-700 transition hover:bg-emerald-100">
                                    Submit for Approval
                                </button>
                            </form>
                        @endif
                    </div>
                </article>
            @empty
                <div class="col-span-full rounded-3xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center">
                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-violet-50 text-violet-600">
                        <svg class="h-8 w-8" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25A8.966 8.966 0 0118 3.75c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/></svg>
                    </div>
                    <h3 class="mt-4 text-xl font-extrabold text-slate-950">No courses match your filters</h3>
                    <p class="mx-auto mt-2 max-w-md text-sm text-slate-500">Clear the filters or create a new course with a cover image, description, and learning level.</p>
                    <a href="{{ route('teacher.courses.create') }}" class="mt-5 inline-flex items-center rounded-xl bg-violet-600 px-5 py-3 text-sm font-bold text-white hover:bg-violet-700">Create Course</a>
                </div>
            @endforelse
        </div>
    </div>
</div>
</x-layouts::app>
