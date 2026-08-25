<x-layouts::app :title="'Edit Category'">

<div class="space-y-6">

    {{-- Header --}}
    <div class="rounded-2xl border border-purple-500/30 bg-linear-to-r from-purple-900 via-neutral-900 to-neutral-900 p-6 shadow-lg">
        <h1 class="text-3xl font-bold text-white">
            Edit Course Category
        </h1>

        <p class="mt-2 text-sm text-purple-200">
            Update the category name and description used to organize courses.
        </p>
    </div>

    {{-- Form Card --}}
    <div class="rounded-2xl border border-neutral-700 bg-neutral-900 p-6 shadow-lg">

        <form method="POST"
              action="{{ route('course-categories.update', $courseCategory->id) }}"
              class="space-y-5">

            @csrf
            @method('PUT')

            {{-- Category Name --}}
            <div>
                <label class="block mb-2 text-sm font-medium text-gray-300">
                    Category Name
                </label>

                <input type="text"
                       name="name"
                       value="{{ old('name', $courseCategory->name) }}"
                       placeholder="Enter category name"
                       class="w-full rounded-xl border border-neutral-700 bg-neutral-800 p-3 text-white outline-none transition focus:border-purple-500">

                @error('name')
                    <p class="mt-2 text-sm text-red-400">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- Description --}}
            <div>
                <label class="block mb-2 text-sm font-medium text-gray-300">
                    Description
                </label>

                <textarea
                    name="description"
                    rows="5"
                    placeholder="Write a short description for this category..."
                    class="w-full rounded-xl border border-neutral-700 bg-neutral-800 p-3 text-white outline-none transition focus:border-purple-500">{{ old('description', $courseCategory->description) }}</textarea>

                @error('description')
                    <p class="mt-2 text-sm text-red-400">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- Buttons --}}
            <div class="flex gap-3 pt-2">

                <button type="submit"
                        class="rounded-xl bg-purple-600 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-purple-500">
                    Update Category
                </button>

                <a href="{{ route('course-categories.index') }}"
                   class="rounded-xl border border-neutral-700 bg-neutral-800 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-neutral-700">
                    Cancel
                </a>

            </div>

        </form>

    </div>

</div>

</x-layouts::app>