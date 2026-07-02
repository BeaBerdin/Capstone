<x-layouts::app :title="$learningPath->name">

@php
    $studentId = auth()->id();

    $enrollments = \App\Models\Enrollment::where('student_id', $studentId)
        ->get()
        ->keyBy('course_id');

    $completedCourseIds = $enrollments
        ->where('status', 'completed')
        ->pluck('course_id')
        ->toArray();

    $activeCourseIds = $enrollments
        ->where('status', 'active')
        ->pluck('course_id')
        ->toArray();

    $totalCourses = $learningPath->courses->count();

    $completedCount = $learningPath->courses
        ->whereIn('id', $completedCourseIds)
        ->count();

    $pathProgress = $totalCourses > 0
        ? round(($completedCount / $totalCourses) * 100)
        : 0;
@endphp

<div class="space-y-6">

    {{-- Back button --}}
    <x-back-button :href="route('student.learning-paths')" label="Back to Learning Paths" />

    <div>
        <h1 class="text-3xl font-bold">
            {{ $learningPath->name }}
        </h1>

        <p class="text-gray-500">
            {{ $learningPath->description }}
        </p>

        @if($learningPath->is_generated)
            <span class="mt-3 inline-block rounded-full bg-purple-100 px-3 py-1 text-sm text-purple-700 dark:bg-purple-500/15 dark:text-purple-300">
                AI Generated Learning Path
            </span>
        @endif
    </div>

    <div class="rounded-xl border bg-white p-6 shadow-sm dark:bg-neutral-900 dark:border-neutral-700">

        <div class="flex justify-between items-center mb-3">
            <h2 class="text-xl font-bold">
                Path Progress
            </h2>

            <span class="text-sm font-semibold">
                {{ $pathProgress }}%
            </span>
        </div>

        <div class="h-3 w-full rounded bg-gray-300 dark:bg-neutral-800">
            <div class="h-3 rounded bg-linear-to-r from-purple-500 to-indigo-500"
                 style="width: {{ $pathProgress }}%">
            </div>
        </div>

        <p class="mt-2 text-sm text-gray-500">
            Completed {{ $completedCount }} out of {{ $totalCourses }} courses.
        </p>

    </div>

    <div class="rounded-xl border bg-white p-6 shadow-sm dark:bg-neutral-900 dark:border-neutral-700">

        <h2 class="text-xl font-bold mb-4">
            Recommended Learning Sequence
        </h2>

        @forelse($learningPath->courses as $index => $course)

            @php
                $isCompleted = in_array($course->id, $completedCourseIds);
                $isActive = in_array($course->id, $activeCourseIds);

                $statusLabel = 'Not Started';
                $statusClass = 'bg-gray-100 text-gray-700 dark:bg-neutral-700 dark:text-gray-300';

                if ($isCompleted) {
                    $statusLabel = 'Completed';
                    $statusClass = 'bg-green-100 text-green-700 dark:bg-green-500/15 dark:text-green-400';
                } elseif ($isActive) {
                    $statusLabel = 'In Progress';
                    $statusClass = 'bg-purple-100 text-purple-700 dark:bg-purple-500/15 dark:text-purple-300';
                }
            @endphp

            <div class="rounded-lg bg-neutral-100 p-5 mb-4 dark:bg-neutral-800">

                <div class="flex justify-between items-start gap-4">

                    <div>
                        <div class="text-sm text-purple-500 mb-2">
                            Step {{ $index + 1 }}
                        </div>

                        <h3 class="text-lg font-bold">
                            {{ $course->title }}
                        </h3>

                        <p class="text-sm text-gray-500 mt-1">
                            {{ $course->category->name ?? 'No Category' }}
                            •
                            {{ ucfirst($course->difficulty_level) }}
                        </p>
                    </div>

                    <span class="rounded-full px-3 py-1 text-sm {{ $statusClass }}">
                        {{ $statusLabel }}
                    </span>

                </div>

                <p class="mt-3 text-sm text-gray-600 dark:text-gray-400">
                    {{ $course->description }}
                </p>

                <div class="mt-4">

                    @if($isCompleted)
                        <a href="{{ route('student.course.show', $course) }}"
                           class="inline-block rounded-lg bg-green-600 px-4 py-2 text-white transition hover:bg-green-700">
                            Review Course
                        </a>
                    @elseif($isActive)
                        <a href="{{ route('student.learn.course', $course) }}"
                           class="inline-block rounded-lg bg-linear-to-r from-purple-500 to-indigo-600 px-4 py-2 text-white transition hover:opacity-90">
                            Continue Learning
                        </a>
                    @else
                        <a href="{{ route('student.course.show', $course) }}"
                           class="inline-block rounded-lg bg-linear-to-r from-purple-500 to-indigo-600 px-4 py-2 text-white transition hover:opacity-90">
                            View Course
                        </a>
                    @endif

                </div>

            </div>

        @empty

            <p class="text-gray-500">
                No courses assigned.
            </p>

        @endforelse

    </div>

</div>

</x-layouts::app>