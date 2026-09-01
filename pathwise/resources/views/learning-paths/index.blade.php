<x-layouts::app :title="'Learning Paths'">

<div class="space-y-6">

    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold">Learning Paths</h1>
            <p class="text-gray-500">
                Manage structured course paths for personalized learning.
            </p>
        </div>

        <a href="{{ route('learning-paths.create') }}"
           class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded">
            Add Learning Path
        </a>
    </div>

    @if(session('success'))
        <div class="rounded bg-green-100 p-4 text-green-700">
            {{ session('success') }}
        </div>
    @endif

    <div class="rounded-xl border bg-white p-6 shadow-sm dark:bg-neutral-900 dark:border-neutral-700">

        <table class="w-full">
            <thead>
                <tr class="border-b">
                    <th class="p-3 text-left">Path Name</th>
                    <th class="p-3 text-left">Description</th>
                    <th class="p-3 text-left">Courses</th>
                    <th class="p-3 text-left">Actions</th>
                </tr>
            </thead>

            <tbody>
                @forelse($learningPaths as $path)
                    <tr class="border-b">
                        <td class="p-3 font-semibold">
                            {{ $path->name }}
                        </td>

                        <td class="p-3">
                            {{ $path->description ?? '-' }}
                        </td>

                        <td class="p-3">
                            {{ $path->courses->count() }}
                        </td>

                        <td class="p-3 flex gap-2">
                            <a href="{{ route('learning-paths.show', $path) }}"
                               class="text-green-600">
                                View
                            </a>

                            <a href="{{ route('learning-paths.edit', $path) }}"
                               class="text-blue-600">
                                Edit
                            </a>

                            <form action="{{ route('learning-paths.destroy', $path) }}"
                                  method="POST"
                                  class="inline">
                                @csrf
                                @method('DELETE')

                                <button class="text-red-600"
                                        onclick="return confirm('Delete this learning path?')">
                                    Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="p-4 text-center">
                            No learning paths found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

    </div>

</div>

</x-layouts::app>