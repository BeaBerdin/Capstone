<x-layouts::app :title="'Learning Paths'">

<div class="space-y-6">

    <div class="flex justify-between items-center">

        <div>
            <h1 class="text-3xl font-bold text-black">
                Learning Paths
            </h1>
            <p class="text-sm text-gray-400">
                Follow a structured learning journey.
            </p>
        </div>

        <form action="{{ route('student.learning-paths.generate') }}"
              method="POST">
            @csrf

            <button type="submit"
                    class="rounded-xl bg-linear-to-r from-purple-500 to-indigo-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:opacity-90">
                Generate AI Learning Path 
            </button>
        </form>

    </div>

    @if(session('success'))
        <div class="rounded-xl border border-green-700/40 bg-green-950/40 px-4 py-3 text-green-300">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="rounded-xl border border-red-700/40 bg-red-950/40 px-4 py-3 text-red-300">
            {{ session('error') }}
        </div>
    @endif

    <div class="grid gap-4">

        @forelse($learningPaths as $path)

            <div class="rounded-2xl border border-neutral-700 bg-neutral-900 p-6 shadow-lg shadow-purple-950/10 transition hover:border-purple-500/30">

                <div class="flex justify-between items-start">

                    <div>
                        <h2 class="text-xl font-bold text-white">
                            {{ $path->name }}
                        </h2>

                        <p class="mt-2 text-sm text-gray-400">
                            {{ $path->description }}
                        </p>
                    </div>

                    @if($path->is_generated)
                        <span class="rounded-full bg-purple-500/15 px-3 py-1 text-xs font-semibold text-purple-300">
                            AI Generated
                        </span>
                    @endif

                </div>

                <p class="mt-4 text-sm text-gray-400">
                    Courses Included:
                    <span class="font-semibold text-white">{{ $path->courses->count() }}</span>
                </p>

                @if($path->difficulty_level)
                    <p class="mt-2 text-sm text-gray-400">
                        Difficulty: <span class="text-gray-300">{{ ucfirst($path->difficulty_level) }}</span>
                    </p>
                @endif

                <a href="{{ route('student.learning-paths.show', $path) }}"
                   class="inline-block mt-4 rounded-xl bg-linear-to-r from-purple-500 to-indigo-600 px-5 py-2 text-sm font-semibold text-white transition hover:opacity-90">
                    View Learning Path →
                </a>

            </div>

        @empty

            <div class="rounded-2xl border border-neutral-700 bg-neutral-900 p-8 text-center">

                <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-purple-600/20 text-2xl mx-auto">
                    🎯
                </div>

                <h2 class="text-xl font-semibold text-white">
                    No Learning Paths Available
                </h2>

                <p class="mt-2 text-gray-400">
                    Generate your first AI-powered learning path to get started.
                </p>

                <form action="{{ route('student.learning-paths.generate') }}"
                      method="POST"
                      class="mt-4 inline-block">
                    @csrf
                    <button type="submit"
                            class="rounded-xl bg-linear-to-r from-purple-500 to-indigo-600 px-5 py-2 text-sm font-semibold text-white transition hover:opacity-90">
                        Generate AI Learning Path →
                    </button>
                </form>

            </div>

        @endforelse

    </div>

</div>

</x-layouts::app>