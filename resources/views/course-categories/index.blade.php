<x-layouts::app :title="__('Course Categories')">
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-black">Course Categories</h1>
                <p class="text-sm text-gray-400">Manage PathWise course categories.</p>
            </div>

            <a href="{{ route('course-categories.create') }}"
               class="rounded-xl bg-purple-600 px-4 py-2 text-sm font-semibold text-white hover:bg-purple-700">
                Add Category
            </a>
        </div>

        @if (session('success'))
            <div class="rounded-xl border border-green-700/40 bg-green-950/40 px-4 py-3 text-green-300">
                {{ session('success') }}
            </div>
        @endif

        <div class="rounded-2xl border border-neutral-700 bg-neutral-900 shadow-lg shadow-purple-950/10">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-neutral-800 text-xs uppercase tracking-wider text-white">
                        <tr>
                            <th class="px-6 py-4">Name</th>
                            <th class="px-6 py-4">Description</th>
                            <th class="px-6 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-800">
                        @forelse ($categories as $category)
                            <tr class="hover:bg-white/5">
                                <td class="px-6 py-4 font-semibold text-white">
                                    {{ $category->name }}
                                </td>
                                <td class="px-6 py-4 text-gray-400">
                                    {{ $category->description }}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <a href="{{ route('course-categories.edit', $category) }}"
                                       class="rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-blue-700">
                                        Edit
                                    </a>

                                    <form action="{{ route('course-categories.destroy', $category) }}" method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button class="ml-2 rounded-lg bg-red-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-red-700"
                                                onclick="return confirm('Delete this category?')">
                                            Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-6 py-16 text-center">
                                    <h3 class="text-lg font-semibold text-white">No categories yet</h3>
                                    <p class="mt-1 text-sm text-gray-400">
                                        Categories will appear here once created.
                                    </p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-layouts::app>