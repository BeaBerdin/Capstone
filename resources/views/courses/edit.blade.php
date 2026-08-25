<x-layouts::app :title="'Edit Course'">

<div class="space-y-6">

    {{-- Header --}}
    <div class="rounded-2xl border border-purple-500/30 bg-linear-to-r from-purple-900 via-neutral-900 to-neutral-900 p-6">
        <h1 class="text-3xl font-bold text-white">
            Edit Course
        </h1>

        <p class="mt-2 text-sm text-purple-200">
            Update course details, category, difficulty level, pricing, and certificate availability.
        </p>
    </div>

    {{-- Form Card --}}
    <div class="rounded-2xl border border-neutral-700 bg-neutral-900 p-6 shadow-lg">

        <form action="{{ route('courses.update', $course) }}"
              method="POST"
              class="space-y-5">

            @csrf
            @method('PUT')

            {{-- Title --}}
            <div>
                <label class="block mb-1 text-sm font-medium text-gray-300">
                    Course Title
                </label>

                <input type="text"
                       name="title"
                       value="{{ old('title', $course->title) }}"
                       class="w-full rounded-xl border border-neutral-700 bg-neutral-800 text-white p-3 focus:border-purple-500 outline-none"
                       required>
            </div>

            {{-- Description --}}
            <div>
                <label class="block mb-1 text-sm font-medium text-gray-300">
                    Description
                </label>

                <textarea name="description"
                          rows="5"
                          class="w-full rounded-xl border border-neutral-700 bg-neutral-800 text-white p-3 focus:border-purple-500 outline-none">{{ old('description', $course->description) }}</textarea>
            </div>

            {{-- Category --}}
            <div>
                <label class="block mb-1 text-sm font-medium text-gray-300">
                    Category
                </label>

                <select name="category_id"
                        class="w-full rounded-xl border border-neutral-700 bg-neutral-800 text-white p-3 focus:border-purple-500 outline-none"
                        required>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}"
                            @selected(old('category_id', $course->category_id) == $category->id)>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Grid --}}
            <div class="grid gap-4 md:grid-cols-2">

                <div>
                    <label class="block mb-1 text-sm text-gray-300">Price</label>
                    <input type="number"
                           step="0.01"
                           name="price"
                           value="{{ old('price', $course->price) }}"
                           class="w-full rounded-xl border border-neutral-700 bg-neutral-800 text-white p-3 focus:border-purple-500 outline-none">
                </div>

                <div>
                    <label class="block mb-1 text-sm text-gray-300">Estimated Hours</label>
                    <input type="number"
                           name="estimated_hours"
                           value="{{ old('estimated_hours', $course->estimated_hours) }}"
                           class="w-full rounded-xl border border-neutral-700 bg-neutral-800 text-white p-3 focus:border-purple-500 outline-none">
                </div>

            </div>

            {{-- Difficulty + Status --}}
            <div class="grid gap-4 md:grid-cols-2">

                <div>
                    <label class="block mb-1 text-sm text-gray-300">Difficulty Level</label>

                    <select name="difficulty_level"
                            class="w-full rounded-xl border border-neutral-700 bg-neutral-800 text-white p-3 focus:border-purple-500 outline-none">

                        <option value="beginner" @selected(old('difficulty_level', $course->difficulty_level) == 'beginner')>
                            Beginner
                        </option>

                        <option value="intermediate" @selected(old('difficulty_level', $course->difficulty_level) == 'intermediate')>
                            Intermediate
                        </option>

                        <option value="advanced" @selected(old('difficulty_level', $course->difficulty_level) == 'advanced')>
                            Advanced
                        </option>

                    </select>
                </div>

                <div>
                    <label class="block mb-1 text-sm text-gray-300">Status</label>

                    <select name="status"
                            class="w-full rounded-xl border border-neutral-700 bg-neutral-800 text-white p-3 focus:border-purple-500 outline-none">

                        <option value="draft" @selected(old('status', $course->status) == 'draft')>Draft</option>
                        <option value="pending" @selected(old('status', $course->status) == 'pending')>Pending</option>
                        <option value="approved" @selected(old('status', $course->status) == 'approved')>Approved</option>
                        <option value="rejected" @selected(old('status', $course->status) == 'rejected')>Rejected</option>
                        <option value="published" @selected(old('status', $course->status) == 'published')>Published</option>

                    </select>
                </div>

            </div>

            {{-- Certificate --}}
            <div class="rounded-xl border border-neutral-700 bg-neutral-800/50 p-4">

                <label class="flex items-start gap-2 text-gray-300">
                    <input type="checkbox"
                           name="certificate_available"
                           value="1"
                           class="mt-1 accent-purple-500"
                           @checked(old('certificate_available', $course->certificate_available))>

                    <span>
                        <span class="font-medium">Certificate Available</span>
                        <span class="block text-sm text-gray-400">
                            Allow students to earn certificate after completion
                        </span>
                    </span>
                </label>

            </div>

            {{-- Buttons --}}
            <div class="flex gap-3 pt-2">

                <button type="submit"
                        class="rounded-xl bg-purple-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-purple-500 transition">
                    Update Course
                </button>

                <a href="{{ route('courses.index') }}"
                   class="rounded-xl border border-neutral-700 bg-neutral-800 px-6 py-2.5 text-sm font-semibold text-white hover:bg-neutral-700 transition">
                    Cancel
                </a>

            </div>

        </form>

    </div>

</div>

</x-layouts::app>