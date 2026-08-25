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
        <h1 class="text-3xl font-bold text-black">
            {{ $learningPath->name }}
        </h1>

        <p class="text-sm text-gray-400">
            {{ $learningPath->description }}
        </p>

        @if($learningPath->is_generated)
            <span class="mt-3 inline-block rounded-full bg-purple-500/15 px-3 py-1 text-sm font-semibold text-purple-500">
                AI Generated Learning Path
            </span>
        @endif
    </div>

    <div class="rounded-2xl border border-neutral-700 bg-neutral-900 p-6 shadow-lg shadow-purple-950/10">

        <div class="flex justify-between items-center mb-3">
            <h2 class="text-xl font-bold text-white">
                Path Progress
            </h2>

            <span class="text-sm font-semibold text-white">
                {{ $pathProgress }}%
            </span>
        </div>

        <div class="h-3 w-full rounded-full bg-neutral-700">
            <div class="h-3 rounded-full bg-linear-to-r from-purple-500 to-indigo-500"
                 style="width: {{ $pathProgress }}%">
            </div>
        </div>

        <p class="mt-2 text-sm text-gray-400">
            Completed {{ $completedCount }} out of {{ $totalCourses }} courses.
        </p>

    </div>

    <div class="rounded-2xl border border-neutral-700 bg-neutral-900 p-6 shadow-lg shadow-purple-950/10">

        <h2 class="text-xl font-bold text-white mb-4">
            Recommended Learning Sequence
        </h2>

        @forelse($learningPath->courses as $index => $course)

            @php
                $isCompleted = in_array($course->id, $completedCourseIds);
                $isActive = in_array($course->id, $activeCourseIds);

                $statusLabel = 'Not Started';
                $statusClass = 'bg-gray-500/15 text-gray-400';

                if ($isCompleted) {
                    $statusLabel = 'Completed';
                    $statusClass = 'bg-green-500/15 text-green-400';
                } elseif ($isActive) {
                    $statusLabel = 'In Progress';
                    $statusClass = 'bg-purple-500/15 text-purple-300';
                }
            @endphp

            <div class="rounded-xl border border-neutral-700 bg-neutral-800 p-5 mb-4">

                <div class="flex justify-between items-start gap-4">

                    <div>
                        <div class="text-sm text-purple-400 mb-2">
                            Step {{ $index + 1 }}
                        </div>

                        <h3 class="text-lg font-bold text-white">
                            {{ $course->title }}
                        </h3>

                        <p class="text-sm text-gray-500 mt-1">
                            {{ $course->category->name ?? 'No Category' }}
                            •
                            {{ ucfirst($course->difficulty_level) }}
                        </p>
                    </div>

                    <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $statusClass }}">
                        {{ $statusLabel }}
                    </span>

                </div>

                <p class="mt-3 text-sm text-gray-400">
                    {{ $course->description }}
                </p>

                <div class="mt-4">

                    @if($isCompleted)
                        <a href="{{ route('student.course.show', $course) }}"
                           class="inline-block rounded-xl bg-green-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-green-700">
                            Review Course
                        </a>
                    @elseif($isActive)
                        <a href="{{ route('student.learn.course', $course) }}"
                           class="inline-block rounded-xl bg-linear-to-r from-purple-500 to-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:opacity-90">
                            Continue Learning →
                        </a>
                    @else
                        <a href="{{ route('student.course.show', $course) }}"
                           class="inline-block rounded-xl bg-linear-to-r from-purple-500 to-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:opacity-90">
                            View Course →
                        </a>
                    @endif

                </div>

            </div>

        @empty

            <div class="rounded-xl border border-neutral-700 bg-neutral-800 p-8 text-center">
                <p class="text-gray-400">
                    No courses assigned.
                </p>
            </div>

        @endforelse

    </div>

</div>

</x-layouts::app>