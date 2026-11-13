<x-layouts::app :title="__('Preview: :title', ['title' => $lesson->title])">
    @include('partials.soft3d')

    @php
        $typeLabel = match ($lesson->lesson_type) {
            'video' => 'Video Lesson',
            'document' => 'Document',
            default => 'Reading',
        };
        $fileUrl = $lesson->lesson_type === 'document' && $lesson->file_path
            ? route('student.lesson.preview.file', $lesson)
            : null;
        $isPdf = strtolower(pathinfo($lesson->file_path ?? '', PATHINFO_EXTENSION)) === 'pdf';

        $videoUrl = null;
        $videoEmbedUrl = null;
        if ($lesson->lesson_type === 'video' && $lesson->video_url) {
            $parts = parse_url($lesson->video_url);
            if (in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)) {
                $videoUrl = $lesson->video_url;
                $host = strtolower($parts['host'] ?? '');
                $path = trim($parts['path'] ?? '', '/');
                $youtubeId = null;

                if (in_array($host, ['youtu.be', 'www.youtu.be'], true)) {
                    $youtubeId = $path;
                } elseif (in_array($host, ['youtube.com', 'www.youtube.com', 'm.youtube.com'], true)) {
                    if ($path === 'watch') {
                        parse_str($parts['query'] ?? '', $query);
                        $youtubeId = $query['v'] ?? null;
                    } elseif (str_starts_with($path, 'embed/')) {
                        $youtubeId = substr($path, 6);
                    }
                }

                if (is_string($youtubeId) && preg_match('/^[A-Za-z0-9_-]{11}$/D', $youtubeId)) {
                    $videoEmbedUrl = 'https://www.youtube.com/embed/'.$youtubeId;
                }
            }
        }
    @endphp

    <main class="sad-page min-h-screen px-5 py-7 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-5xl space-y-6">
            <nav aria-label="Breadcrumb" class="text-sm text-slate-500">
                <a href="{{ route('student.course.show', $course) }}" class="hover:text-violet-700">
                    {{ $course->title }}
                </a>
                <span aria-hidden="true" class="mx-2">/</span>
                <span>Lesson Preview</span>
            </nav>

            <header class="sad-card rounded-2xl border border-violet-100 bg-white p-6">
                <span class="rounded-full bg-violet-50 px-3 py-1 text-xs font-semibold text-violet-700">Free Preview</span>
                <h1 class="mt-4 text-2xl font-bold text-slate-900">{{ $lesson->title }}</h1>
                <p class="mt-2 text-sm text-slate-500">
                    {{ $typeLabel }}
                    @if($lesson->duration_minutes)
                        · {{ $lesson->duration_minutes }} min
                    @endif
                </p>
                <p class="mt-4 text-sm text-slate-600">Explore this lesson before enrolling. Previewing does not count toward course completion.</p>
            </header>

            @if($lesson->lesson_type === 'video')
                <section aria-label="Preview video" class="sad-card overflow-hidden rounded-2xl border border-slate-200 bg-white">
                    @if($videoEmbedUrl)
                        <iframe
                            src="{{ $videoEmbedUrl }}"
                            title="{{ $lesson->title }}"
                            class="aspect-video w-full"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                            allowfullscreen
                        ></iframe>
                    @endif
                    <div class="p-6">
                        @if($videoUrl)
                            <a href="{{ $videoUrl }}" target="_blank" rel="noopener noreferrer" class="font-semibold text-violet-700 hover:underline">Watch video in a new tab →</a>
                        @else
                            <p class="text-sm text-slate-500">No video is available for this lesson yet.</p>
                        @endif
                    </div>
                </section>
            @endif

            @if($lesson->lesson_type === 'document')
                <section aria-label="Preview document" class="sad-card overflow-hidden rounded-2xl border border-slate-200 bg-white">
                    <div class="p-6">
                        @if($fileUrl)
                            <a href="{{ $fileUrl }}" target="_blank" rel="noopener noreferrer" class="font-semibold text-violet-700 hover:underline">Open lesson document →</a>
                        @else
                            <p class="text-sm text-slate-500">No document is available for this lesson yet.</p>
                        @endif
                    </div>
                    @if($fileUrl && $isPdf)
                        <iframe src="{{ $fileUrl }}" title="{{ $lesson->title }}" class="h-[700px] w-full border-0"></iframe>
                    @endif
                </section>
            @endif

            @if($lesson->content)
                <section aria-label="Lesson content" class="sad-card rounded-2xl border border-slate-200 bg-white p-6 sm:p-8">
                    <h2 class="mb-4 text-lg font-semibold text-slate-900">Lesson content</h2>
                    <div class="whitespace-pre-wrap break-words text-sm leading-7 text-slate-700">{{ $lesson->content }}</div>
                </section>
            @elseif($lesson->lesson_type === 'text')
                <p class="rounded-2xl border border-slate-200 bg-white p-6 text-sm text-slate-500">No reading content is available for this lesson yet.</p>
            @endif

            <a href="{{ route('student.course.show', $course) }}" class="sad-btn-primary inline-flex rounded-xl bg-violet-600 px-5 py-3 text-sm font-semibold text-white hover:bg-violet-700">← Back to Course</a>
        </div>
    </main>
</x-layouts::app>
