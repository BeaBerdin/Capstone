<x-layouts::app :title="__('My Courses')">
    <div class="space-y-6">

        <div>
            <h1 class="text-3xl font-bold text-black">My Courses</h1>
            <p class="mt-1 text-sm text-dark">
                Manage your published, pending, and draft learning materials.
            </p>
        </div>

        @php
            $totalCourses = $courses->count();
            $publishedCourses = $courses->where('status', 'published')->count();
            $pendingCourses = $courses->where('status', 'pending')->count();
            $totalStudents = $courses->sum('enrollments_count');
        @endphp

        <div class="grid gap-4 md:grid-cols-4">
            <div class="rounded-2xl border border-zinc-800 bg-zinc-900 p-5">
                <p class="text-sm text-zinc-400">Total Courses</p>
                <h2 class="mt-2 text-3xl font-bold text-white">{{ $totalCourses }}</h2>
            </div>

            <div class="rounded-2xl border border-emerald-800/40 bg-emerald-950 p-5">
                <p class="text-sm text-emerald-300">Published</p>
                <h2 class="mt-2 text-3xl font-bold text-emerald-400">{{ $publishedCourses }}</h2>
            </div>

            <div class="rounded-2xl border border-yellow-800/40 bg-yellow-950 p-5">
                <p class="text-sm text-yellow-300">Pending</p>
                <h2 class="mt-2 text-3xl font-bold text-yellow-400">{{ $pendingCourses }}</h2>
            </div>

            <div class="rounded-2xl border border-purple-800/40 bg-purple-950 p-5">
                <p class="text-sm text-purple-300">Total Students</p>
                <h2 class="mt-2 text-3xl font-bold text-purple-400">{{ $totalStudents }}</h2>
            </div>
        </div>

        @if(session('success'))
            <div class="rounded-xl border border-emerald-700/40 bg-emerald-950/40 px-4 py-3 text-emerald-300">
                {{ session('success') }}
            </div>
        @endif

        <div class="grid gap-5 xl:grid-cols-2">
            @forelse($courses as $course)
                @php
                    $status = strtolower($course->status);
                    $statusClasses = match ($status) {
                        'published' => 'bg-emerald-500/15 text-emerald-400 border-emerald-500/20',
                        'pending' => 'bg-yellow-500/15 text-yellow-400 border-yellow-500/20',
                        'rejected' => 'bg-red-500/15 text-red-400 border-red-500/20',
                        'draft' => 'bg-zinc-500/15 text-zinc-300 border-zinc-500/20',
                        default => 'bg-blue-500/15 text-blue-400 border-blue-500/20',
                    };
                @endphp

                <div class="group rounded-2xl border border-zinc-800 bg-zinc-900 p-6 shadow-lg shadow-purple-950/10 transition hover:border-purple-700/50 hover:bg-zinc-800">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex gap-4">
                            <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-purple-600/20 text-2xl">
                                📚
                            </div>

                            <div>
                                <h2 class="text-xl font-bold text-white">
                                    {{ $course->title }}
                                </h2>

                                <p class="mt-1 text-sm text-zinc-300">
                                    {{ $course->category->name ?? 'No Category' }}
                                </p>
                            </div>
                        </div>

                        <span class="rounded-full border px-3 py-1 text-xs font-semibold {{ $statusClasses }}">
                            {{ ucfirst($course->status) }}
                        </span>
                    </div>

                    <div class="mt-6 grid grid-cols-3 gap-4">
                        <div class="rounded-xl border border-zinc-800 bg-zinc-950 p-4">
                            <p class="text-xs text-zinc-400">Lessons</p>
                            <p class="mt-1 text-2xl font-bold text-white">
                                {{ $course->lessons_count ?? $course->lessons->count() }}
                            </p>
                        </div>

                        <div class="rounded-xl border border-zinc-800 bg-zinc-950 p-4">
                            <p class="text-xs text-zinc-400">Students</p>
                            <p class="mt-1 text-2xl font-bold text-white">
                                {{ $course->enrollments_count ?? $course->enrollments->count() }}
                            </p>
                        </div>

                        <div class="rounded-xl border border-zinc-800 bg-zinc-950 p-4">
                            <p class="text-xs text-zinc-400">Price</p>
                            <p class="mt-1 text-2xl font-bold text-white">
                                ₱{{ number_format($course->price, 2) }}
                            </p>
                        </div>
                    </div>

                    <div class="mt-6 flex flex-wrap items-center gap-2">
                        <a href="{{ route('teacher.course.students', $course) }}"
                           class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                            View Students
                        </a>

                        <a href="{{ route('teacher.lessons', $course) }}"
                           class="rounded-xl bg-purple-600 px-4 py-2 text-sm font-semibold text-white hover:bg-purple-700">
                            Manage Lessons
                        </a>

                        @if($course->status === 'draft' || $course->status === 'rejected')
                            <form action="{{ route('teacher.courses.submit', $course) }}"
                                  method="POST">
                                @csrf

                                <button type="submit"
                                        class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                                    Submit for Approval
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @empty
                <div class="rounded-2xl border border-zinc-800 bg-zinc-900 px-6 py-16 text-center xl:col-span-2">
                    <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-purple-600/20 text-2xl">
                        📚
                    </div>

                    <h3 class="text-lg font-semibold text-white">No courses yet</h3>

                    <p class="mt-1 text-sm text-zinc-300">
                        Your assigned or created courses will appear here.
                    </p>
                </div>
            @endforelse
        </div>

    </div>
</x-layouts::app>