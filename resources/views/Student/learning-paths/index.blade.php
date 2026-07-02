<x-layouts::app :title="'Learning Paths'">

<div class="space-y-6">

    <div class="flex justify-between items-center">

        <div>
            <h1 class="text-2xl font-bold">
                Learning Paths
            </h1>

            <p class="text-gray-500">
                Follow a structured learning journey.
            </p>
        </div>

        <form action="{{ route('student.learning-paths.generate') }}"
              method="POST">
            @csrf

            <button type="submit"
                    class="rounded-lg bg-linear-to-r from-purple-500 to-indigo-600 px-4 py-2 text-white transition hover:opacity-90">
                Generate AI Learning Path
            </button>
        </form>

    </div>

    @if(session('success'))
        <div class="rounded-lg bg-green-100 p-4 text-green-700 dark:bg-green-500/10 dark:text-green-400">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="rounded-lg bg-red-100 p-4 text-red-700 dark:bg-red-500/10 dark:text-red-400">
            {{ session('error') }}
        </div>
    @endif

    <div class="grid gap-4">

        @forelse($learningPaths as $path)

            <div class="rounded-xl border bg-white p-6 shadow-sm dark:bg-neutral-900 dark:border-neutral-700">

                <div class="flex justify-between items-start">

                    <div>
                        <h2 class="text-xl font-bold">
                            {{ $path->name }}
                        </h2>

                        <p class="mt-2 text-gray-500">
                            {{ $path->description }}
                        </p>
                    </div>

                    @if($path->is_generated)
                        <span class="bg-purple-100 text-purple-700 text-xs px-3 py-1 rounded-full dark:bg-purple-500/15 dark:text-purple-300">
                            AI Generated
                        </span>
                    @endif

                </div>

                <p class="mt-4 text-sm">
                    Courses Included:
                    <strong>{{ $path->courses->count() }}</strong>
                </p>

                @if($path->difficulty_level)
                    <p class="mt-2 text-sm text-gray-500">
                        Difficulty: {{ ucfirst($path->difficulty_level) }}
                    </p>
                @endif

                <a href="{{ route('student.learning-paths.show', $path) }}"
                   class="inline-block mt-4 rounded-lg bg-linear-to-r from-purple-500 to-indigo-600 px-4 py-2 text-white transition hover:opacity-90">
                    View Learning Path
                </a>

            </div>

        @empty

            <div class="rounded-xl border p-6 dark:border-neutral-700">
                No learning paths available.
            </div>

        @endforelse

    </div>

</div>

</x-layouts::app>