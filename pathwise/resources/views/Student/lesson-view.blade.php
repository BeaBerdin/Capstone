<x-layouts::app :title="$lesson->title">

<div class="space-y-6">

    <div>
        <a href="{{ route('student.learn.course', $lesson->course) }}"
           class="text-sm text-blue-600 hover:underline">
            ← Back to Course Lessons
        </a>

        <h1 class="mt-3 text-3xl font-bold">
            {{ $lesson->lesson_order }}. {{ $lesson->title }}
        </h1>

        <p class="text-gray-500">
            {{ $lesson->course->title }}
        </p>
    </div>

    @if(session('success'))
        <div class="rounded-lg bg-green-100 p-4 text-green-700">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="rounded-lg bg-red-100 p-4 text-red-700">
            {{ session('error') }}
        </div>
    @endif

    <div class="rounded-xl border bg-white p-6 shadow-sm dark:bg-neutral-900 dark:border-neutral-700">

        <div class="mb-5 flex flex-wrap gap-3 text-sm text-gray-500">
            <span>Type: {{ ucfirst($lesson->lesson_type) }}</span>
            <span>Duration: {{ $lesson->duration_minutes ?? 0 }} minutes</span>

            @if($progress->status === 'completed')
                <span class="rounded-full bg-green-100 px-3 py-1 text-green-700">
                    Completed
                </span>
            @else
                <span class="rounded-full bg-yellow-100 px-3 py-1 text-yellow-700">
                    In Progress
                </span>
            @endif
        </div>

        @if($lesson->video_url)
            <div class="mb-6 rounded-lg border p-4 dark:border-neutral-700">
                <h2 class="mb-2 font-semibold">
                    Lesson Video
                </h2>

                <a href="{{ $lesson->video_url }}"
                   target="_blank"
                   class="text-blue-600 hover:underline">
                    Open Lesson Video →
                </a>
            </div>
        @endif

        <div class="whitespace-pre-line leading-7 text-gray-800 dark:text-gray-200">
            {{ $lesson->content ?? 'No lesson content available.' }}
        </div>

        <div class="mt-8 border-t pt-5 dark:border-neutral-700">
            @if($progress->status === 'completed')
                <div class="rounded-lg bg-green-100 p-4 text-green-700">
                    ✓ You have completed this lesson.
                </div>
            @else
                <form action="{{ route('student.lesson.complete', $lesson) }}"
                      method="POST">
                    @csrf

                    <button type="submit"
                            class="rounded bg-blue-600 px-5 py-2 text-white hover:bg-blue-700">
                        Mark as Complete
                    </button>
                </form>
            @endif
        </div>

    </div>

    <div class="flex justify-between gap-3">

        @if($previousLesson)
            <a href="{{ route('student.lesson.view', $previousLesson) }}"
               class="rounded bg-gray-600 px-4 py-2 text-white hover:bg-gray-700">
                ← Previous Lesson
            </a>
        @else
            <span></span>
        @endif

        @if($nextLesson)
            <a href="{{ route('student.lesson.view', $nextLesson) }}"
               class="rounded bg-blue-600 px-4 py-2 text-white hover:bg-blue-700">
                Next Lesson →
            </a>
        @else
            <a href="{{ route('student.learn.course', $lesson->course) }}"
               class="rounded bg-green-600 px-4 py-2 text-white hover:bg-green-700">
                Finish Course View
            </a>
        @endif

    </div>

</div>

</x-layouts::app>