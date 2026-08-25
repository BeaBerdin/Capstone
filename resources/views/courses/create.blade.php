<x-layouts::app :title="'Create Course'">

<div class="space-y-6">

    {{-- Header --}}
    <div class="rounded-2xl border border-purple-500/30 bg-linear-to-r from-purple-900 via-neutral-900 to-neutral-900 p-6 shadow-lg">
        <h1 class="text-3xl font-bold text-white">
            Create Course
        </h1>

        <p class="mt-2 text-sm text-purple-200">
            Create a new course by providing its title, description, category, pricing, difficulty level, and certificate availability.
        </p>
    </div>

    {{-- Form Card --}}
    <div class="rounded-2xl border border-neutral-700 bg-neutral-900 p-6 shadow-lg">

        <form action="{{ route('courses.store') }}"
              method="POST"
              class="space-y-5">

            @csrf

            {{-- Course Title --}}
            <div>
                <label class="block mb-2 text-sm font-medium text-gray-300">
                    Course Title
                </label>

                <input type="text"
                       name="title"
                       value="{{ old('title') }}"
                       placeholder="Example: Entrepreneurship Essentials"
                       class="w-full rounded-xl border border-neutral-700 bg-neutral-800 p-3 text-white outline-none transition focus:border-purple-500"
                       required>

                @error('title')
                    <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Description --}}
            <div>
                <label class="block mb-2 text-sm font-medium text-gray-300">
                    Description
                </label>

                <textarea name="description"
                          rows="5"
                          placeholder="Describe what students will learn..."
                          class="w-full rounded-xl border border-neutral-700 bg-neutral-800 p-3 text-white outline-none transition focus:border-purple-500">{{ old('description') }}</textarea>

                @error('description')
                    <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Category --}}
            <div>
                <label class="block mb-2 text-sm font-medium text-gray-300">
                    Category
                </label>

                <select name="category_id"
                        class="w-full rounded-xl border border-neutral-700 bg-neutral-800 p-3 text-white outline-none transition focus:border-purple-500"
                        required>

                    <option value="">Select Category</option>

                    @foreach($categories as $category)
                        <option value="{{ $category->id }}"
                            @selected(old('category_id') == $category->id)>
                            {{ $category->name }}
                        </option>
                    @endforeach

                </select>

                @error('category_id')
                    <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Price & Hours --}}
            <div class="grid gap-4 md:grid-cols-2">

                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-300">
                        Price
                    </label>

                    <input type="number"
                           step="0.01"
                           name="price"
                           value="{{ old('price',0) }}"
                           class="w-full rounded-xl border border-neutral-700 bg-neutral-800 p-3 text-white outline-none transition focus:border-purple-500">

                    @error('price')
                        <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-300">
                        Estimated Hours
                    </label>

                    <input type="number"
                           name="estimated_hours"
                           value="{{ old('estimated_hours') }}"
                           class="w-full rounded-xl border border-neutral-700 bg-neutral-800 p-3 text-white outline-none transition focus:border-purple-500">

                    @error('estimated_hours')
                        <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                    @enderror
                </div>

            </div>

            {{-- Difficulty & Status --}}
            <div class="grid gap-4 md:grid-cols-2">

                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-300">
                        Difficulty Level
                    </label>

                    <select name="difficulty_level"
                            class="w-full rounded-xl border border-neutral-700 bg-neutral-800 p-3 text-white outline-none transition focus:border-purple-500">

                        <option value="beginner">Beginner</option>
                        <option value="intermediate" selected>Intermediate</option>
                        <option value="advanced">Advanced</option>

                    </select>
                </div>

                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-300">
                        Course Status
                    </label>

                    <select name="status"
                            class="w-full rounded-xl border border-neutral-700 bg-neutral-800 p-3 text-white outline-none transition focus:border-purple-500">

                        <option value="draft">Draft</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="published" selected>Published</option>

                    </select>
                </div>

            </div>

            {{-- Certificate --}}
            <div class="rounded-xl border border-neutral-700 bg-neutral-800/50 p-4">

                <label class="flex items-start gap-3">

                    <input type="checkbox"
                           name="certificate_available"
                           value="1"
                           class="mt-1 accent-purple-600"
                           @checked(old('certificate_available', true))>

                    <span>

                        <span class="block font-medium text-gray-200">
                            Certificate Available
                        </span>

                        <span class="text-sm text-gray-400">
                            Allow students to receive a certificate after successfully completing the course.
                        </span>

                    </span>

                </label>

            </div>

            {{-- Buttons --}}
            <div class="flex gap-3 pt-2">

                <button type="submit"
                        class="rounded-xl bg-purple-600 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-purple-500">
                    Save Course
                </button>

                <a href="{{ route('courses.index') }}"
                   class="rounded-xl border border-neutral-700 bg-neutral-800 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-neutral-700">
                    Cancel
                </a>

            </div>

        </form>

    </div>

</div>

</x-layouts::app>