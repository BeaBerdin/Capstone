<x-layouts::app :title="$learningPath->name">

<div class="space-y-6">

    <div>
        <h1 class="text-2xl font-bold">
            {{ $learningPath->name }}
        </h1>

        <p class="text-gray-500">
            {{ $learningPath->description ?? 'No description provided.' }}
        </p>
    </div>

    <div class="rounded-xl border bg-white p-6 shadow-sm dark:bg-neutral-900 dark:border-neutral-700">

        <h2 class="text-xl font-bold mb-4">
            Courses in this Learning Path
        </h2>

        @forelse($learningPath->courses as $course)
            <div class="rounded bg-neutral-100 p-4 mb-3 dark:bg-neutral-800">
                <h3 class="font-semibold">
                    {{ $loop->iteration }}. {{ $course->title }}
                </h3>

                <p class="text-sm text-gray-500 mt-1">
                    {{ $course->category->name ?? 'No Category' }}
                </p>
            </div>
        @empty
            <p class="text-gray-500">
                No courses added to this learning path.
            </p>
        @endforelse

    </div>

    <a href="{{ route('learning-paths.index') }}"
       class="inline-block text-blue-600">
        ← Back to Learning Paths
    </a>

</div>

</x-layouts::app>