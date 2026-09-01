<x-layouts::app :title="__('Create Category')">

<div class="space-y-6">

    {{-- Header --}}
    <div class="rounded-2xl border border-purple-500/30 bg-linear-to-r from-purple-900 via-neutral-900 to-neutral-900 p-6">
        <h1 class="text-3xl font-bold text-white">
            Create Course Category
        </h1>

        <p class="mt-2 text-sm text-purple-200">
            Organize courses by category to improve structure and navigation.
        </p>
    </div>

    {{-- Form --}}
    <div class="rounded-2xl border border-neutral-700 bg-neutral-900 p-6 shadow-lg">

        <form method="POST" action="{{ route('course-categories.store') }}" class="space-y-5">
            @csrf

            {{-- Name --}}
            <div>
                <label class="block text-sm font-medium text-gray-300">
                    Category Name
                </label>

                <input type="text"
                       name="name"
                       value="{{ old('name') }}"
                       placeholder="e.g. Programming, Business, Design"
                       class="mt-1 w-full rounded-xl border border-neutral-700 bg-neutral-800 p-3 text-white focus:border-purple-500 outline-none">

                @error('name')
                    <p class="text-sm text-red-400 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Description --}}
            <div>
                <label class="block text-sm font-medium text-gray-300">
                    Description
                </label>

                <textarea name="description"
                          rows="4"
                          placeholder="Short description about this category..."
                          class="mt-1 w-full rounded-xl border border-neutral-700 bg-neutral-800 p-3 text-white focus:border-purple-500 outline-none">{{ old('description') }}</textarea>
            </div>

            {{-- Buttons --}}
            <div class="flex gap-3 pt-2">

                <button type="submit"
                        class="rounded-xl bg-purple-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-purple-500 transition">
                    Save Category
                </button>

                <a href="{{ route('course-categories.index') }}"
                   class="rounded-xl border border-neutral-700 bg-neutral-800 px-6 py-2.5 text-sm font-semibold text-white hover:bg-neutral-700 transition">
                    Cancel
                </a>

            </div>

        </form>

    </div>

</div>

</x-layouts::app>