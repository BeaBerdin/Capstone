<x-layouts::app :title="'Create Learning Path'">

<div class="space-y-6">

    <div>
        <h1 class="text-2xl font-bold">Create Learning Path</h1>
        <p class="text-gray-500">
            Build a structured learning sequence using available courses.
        </p>
    </div>

    <div class="rounded-xl border bg-white p-6 shadow-sm dark:bg-neutral-900 dark:border-neutral-700">

        <form action="{{ route('learning-paths.store') }}"
              method="POST"
              class="space-y-5">
            @csrf

            <div>
                <label class="block mb-1 font-medium">Path Name</label>
                <input type="text"
                       name="name"
                       value="{{ old('name') }}"
                       class="w-full rounded border p-2 bg-transparent"
                       required>

                @error('name')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block mb-1 font-medium">Description</label>
                <textarea name="description"
                          rows="4"
                          class="w-full rounded border p-2 bg-transparent">{{ old('description') }}</textarea>
            </div>

            <div>
                <label class="block mb-2 font-medium">Select Courses</label>

                <div class="space-y-2">
                    @foreach($courses as $course)
                        <label class="flex items-center gap-2 rounded bg-neutral-100 p-3 dark:bg-neutral-800">
                            <input type="checkbox"
                                   name="course_ids[]"
                                   value="{{ $course->id }}">
                            <span>{{ $course->title }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="flex gap-2">
                <button type="submit"
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded">
                    Save Learning Path
                </button>

                <a href="{{ route('learning-paths.index') }}"
                   class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded">
                    Cancel
                </a>
            </div>
        </form>

    </div>

</div>

</x-layouts::app>