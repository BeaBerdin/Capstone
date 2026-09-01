<x-layouts::app :title="$course->title . ' Lessons'">
@php
    $publishedLessons = $lessons->where('is_published', true)->count();
    $totalMinutes = (int) $lessons->sum(fn($lesson) => (int) ($lesson->duration_minutes ?? 0));
@endphp

<div class="min-h-screen bg-slate-50/80 pb-12 text-slate-900">
    <div class="border-b border-slate-200 bg-white">
        <div class="mx-auto max-w-7xl px-4 py-7 sm:px-6 lg:px-8">
            <nav class="flex flex-wrap items-center gap-2 text-xs font-semibold text-slate-400">
                <a href="{{ route('teacher.my-courses') }}" class="hover:text-violet-700">My Courses</a>
                <span>›</span>
                <span class="max-w-52 truncate text-slate-500">{{ $course->title }}</span>
                <span>›</span>
                <span class="text-violet-600">Lessons</span>
            </nav>

            <div class="mt-5 flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex min-w-0 items-center gap-4">
                    <div class="h-20 w-28 shrink-0 overflow-hidden rounded-2xl bg-gradient-to-br from-violet-600 to-indigo-600 shadow-sm">
                        @if($course->thumbnail)
                            <img src="{{ asset('storage/' . $course->thumbnail) }}" alt="{{ $course->title }}" class="h-full w-full object-cover">
                        @else
                            <div class="flex h-full w-full items-center justify-center text-3xl font-black text-white">{{ strtoupper(substr($course->title,0,1)) }}</div>
                        @endif
                    </div>
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-full bg-violet-50 px-2.5 py-1 text-[10px] font-extrabold uppercase tracking-wide text-violet-700">{{ $course->category->name ?? 'Course' }}</span>
                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-extrabold uppercase tracking-wide text-slate-600">{{ ucfirst($course->status) }}</span>
                        </div>
                        <h1 class="mt-2 truncate text-2xl font-black tracking-tight text-slate-950 sm:text-3xl">{{ $course->title }}</h1>
                        <p class="mt-1 text-sm text-slate-500">Organize lesson content in the order students should learn it.</p>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('teacher.courses.edit', $course) }}" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 hover:border-violet-200 hover:text-violet-700">Edit Course</a>
                    <a href="{{ route('teacher.lessons.create', $course) }}" class="inline-flex items-center gap-2 rounded-xl bg-violet-600 px-4 py-2.5 text-sm font-bold text-white shadow-sm shadow-violet-200 hover:bg-violet-700">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                        Add Lesson
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        @if(session('success'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ session('success') }}</div>
        @endif

        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Total lessons</p><p class="mt-2 text-2xl font-black text-slate-950">{{ $lessons->count() }}</p></div>
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Published</p><p class="mt-2 text-2xl font-black text-emerald-600">{{ $publishedLessons }}</p></div>
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Estimated time</p><p class="mt-2 text-2xl font-black text-slate-950">{{ $totalMinutes > 0 ? $totalMinutes . ' min' : '—' }}</p></div>
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Students</p><p class="mt-2 text-2xl font-black text-blue-600">{{ $enrollments->count() }}</p></div>
        </div>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-extrabold text-slate-950">Course content</h2>
                    <p class="mt-1 text-sm text-slate-500">Each lesson shows its real content type instead of a generic course card.</p>
                </div>
                <a href="{{ route('teacher.lessons.create', $course) }}" class="text-sm font-bold text-violet-700 hover:text-violet-800">+ Add lesson</a>
            </div>

            <div class="divide-y divide-slate-100">
                @forelse($lessons as $lesson)
                    @php
                        $type = strtolower($lesson->lesson_type);
                        $typeStyle = match($type) {
                            'video' => ['bg-violet-50 text-violet-700 ring-violet-100', 'Video'],
                            'document' => ['bg-sky-50 text-sky-700 ring-sky-100', 'Document'],
                            'quiz' => ['bg-amber-50 text-amber-700 ring-amber-100', 'Quiz'],
                            default => ['bg-emerald-50 text-emerald-700 ring-emerald-100', 'Reading'],
                        };
                        $youtubeId = null;
                        if ($type === 'video' && $lesson->video_url && preg_match('~(?:youtu\\.be/|youtube\\.com/(?:watch\\?v=|embed/|shorts/))([A-Za-z0-9_-]{6,})~', $lesson->video_url, $match)) {
                            $youtubeId = $match[1];
                        }
                    @endphp
                    <article class="grid gap-4 p-5 transition hover:bg-slate-50/70 md:grid-cols-[52px_1fr_180px_auto] md:items-center">
                        <div class="flex items-center gap-3 md:block">
                            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-slate-100 text-sm font-black text-slate-500">{{ str_pad($lesson->lesson_order, 2, '0', STR_PAD_LEFT) }}</div>
                            <span class="md:hidden rounded-full px-2.5 py-1 text-[10px] font-bold ring-1 {{ $typeStyle[0] }}">{{ $typeStyle[1] }}</span>
                        </div>

                        <div class="min-w-0">
                            <div class="hidden md:flex items-center gap-2">
                                <span class="rounded-full px-2.5 py-1 text-[10px] font-bold ring-1 {{ $typeStyle[0] }}">{{ $typeStyle[1] }}</span>
                                @if($lesson->is_preview)<span class="rounded-full bg-blue-50 px-2.5 py-1 text-[10px] font-bold text-blue-700">Preview</span>@endif
                                <span class="rounded-full px-2.5 py-1 text-[10px] font-bold {{ $lesson->is_published ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">{{ $lesson->is_published ? 'Published' : 'Draft' }}</span>
                            </div>
                            <h3 class="mt-2 text-base font-extrabold text-slate-950">{{ $lesson->title }}</h3>
                            <p class="mt-1 line-clamp-2 max-w-2xl text-sm leading-5 text-slate-500">{{ $lesson->content ?: 'No lesson description/content has been added yet.' }}</p>
                            <div class="mt-2 flex flex-wrap gap-3 text-xs font-medium text-slate-400">
                                @if($lesson->duration_minutes)<span>◷ {{ $lesson->duration_minutes }} min</span>@endif
                                @if($lesson->file_path)<a href="{{ asset('storage/' . $lesson->file_path) }}" target="_blank" class="font-semibold text-sky-600 hover:underline">Open attached file</a>@endif
                                @if($lesson->video_url)<a href="{{ $lesson->video_url }}" target="_blank" rel="noopener" class="font-semibold text-violet-600 hover:underline">Open video</a>@endif
                            </div>
                        </div>

                        <div class="overflow-hidden rounded-xl border border-slate-100 bg-slate-50">
                            @if($youtubeId)
                                <img src="https://img.youtube.com/vi/{{ $youtubeId }}/mqdefault.jpg" alt="{{ $lesson->title }} video preview" class="aspect-video w-full object-cover">
                            @elseif($course->thumbnail)
                                <div class="relative"><img src="{{ asset('storage/' . $course->thumbnail) }}" alt="" class="aspect-video w-full object-cover opacity-75"><div class="absolute inset-0 flex items-center justify-center bg-slate-950/20"><span class="rounded-full bg-white/95 px-3 py-1 text-xs font-black text-slate-700">{{ $typeStyle[1] }}</span></div></div>
                            @else
                                <div class="flex aspect-video items-center justify-center text-center text-xs font-bold text-slate-400">{{ $typeStyle[1] }} content</div>
                            @endif
                        </div>

                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ route('teacher.lessons.edit', $lesson) }}" class="inline-flex h-10 items-center justify-center rounded-xl border border-slate-200 px-3 text-sm font-bold text-slate-700 hover:border-violet-200 hover:bg-violet-50 hover:text-violet-700">Edit</a>
                            <form action="{{ route('teacher.lessons.delete', $lesson) }}" method="POST" onsubmit="return confirm('Delete this lesson?')">
                                @csrf
                                @method('DELETE')
                                <button class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-rose-100 text-rose-500 hover:bg-rose-50" aria-label="Delete lesson">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166M19.228 5.79L18.16 19.673A2.25 2.25 0 0115.917 21H8.084a2.25 2.25 0 01-2.244-1.327L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0V4.477c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                </button>
                            </form>
                        </div>
                    </article>
                @empty
                    <div class="px-6 py-16 text-center">
                        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-violet-50 text-violet-600"><svg class="h-8 w-8" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg></div>
                        <h3 class="mt-4 text-xl font-extrabold text-slate-950">Start building this course</h3>
                        <p class="mx-auto mt-2 max-w-md text-sm text-slate-500">Add a video, reading, document, or quiz. Students will see these lessons in the order you choose.</p>
                        <a href="{{ route('teacher.lessons.create', $course) }}" class="mt-5 inline-flex rounded-xl bg-violet-600 px-5 py-3 text-sm font-bold text-white hover:bg-violet-700">Add First Lesson</a>
                    </div>
                @endforelse
            </div>
        </section>
    </div>
</div>
</x-layouts::app>
