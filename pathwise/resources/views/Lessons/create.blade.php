<x-layouts::app :title="__('Create Lesson')">

<div class="space-y-6">

    {{-- Header --}}
    <div class="rounded-2xl border border-purple-500/30 bg-linear-to-r from-purple-900 via-neutral-900 to-neutral-900 p-6">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">

            <div>
                <p class="text-sm font-medium text-purple-300">Lesson Management</p>
                <h1 class="mt-2 text-3xl font-bold text-white">Create Lesson</h1>
                <p class="mt-2 text-sm text-gray-300">
                    Add a lesson under a course. This will be shown to students before taking the course quiz.
                </p>
            </div>

            <div class="flex gap-3">

                <a href="{{ route('teacher.my-courses') }}"
                   class="rounded-xl bg-purple-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-purple-700 transition">
                    View My Courses
                </a>

                <a href="{{ route('teacher.my-courses') }}"
                   class="rounded-xl border border-neutral-600 bg-neutral-800 px-5 py-2.5 text-sm font-semibold text-white hover:bg-neutral-700 transition">
                    Back
                </a>

            </div>

        </div>
    </div>

    {{-- Form --}}
    <div class="rounded-2xl border border-neutral-700 bg-neutral-900 p-6 shadow-lg">

        <form action="{{ route('lessons.store') }}" method="POST" class="space-y-5">
            @csrf

            {{-- Course --}}
            <div>
                <label class="block mb-1 text-sm font-medium text-gray-300">Course</label>

                <select name="course_id"
                        class="w-full rounded-xl border border-neutral-700 bg-neutral-800 text-white p-3 outline-none focus:border-purple-500 transition"
                        required>

                    <option value="" class="text-gray-500">Select course</option>

                    @foreach($courses as $course)
                        <option value="{{ $course->id }}" @selected(old('course_id') == $course->id)>
                            {{ $course->title }}
                        </option>
                    @endforeach

                </select>

                <p class="mt-1 text-xs text-gray-500">
                    Select the course where this lesson belongs.
                </p>

                @error('course_id')
                    <p class="text-sm text-red-400 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Title --}}
            <div>
                <label class="block mb-1 text-sm font-medium text-gray-300">Lesson Title</label>

                <input type="text"
                       name="title"
                       value="{{ old('title') }}"
                       placeholder="Example: Introduction to Accounting"
                       class="w-full rounded-xl border border-neutral-700 bg-neutral-800 text-white p-3 outline-none placeholder:text-gray-500 focus:border-purple-500 transition"
                       required>

                <p class="mt-1 text-xs text-gray-500">
                    Use a clear and specific title for the lesson.
                </p>

                @error('title')
                    <p class="text-sm text-red-400 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Content --}}
            <div>
                <label class="block mb-1 text-sm font-medium text-gray-300">Lesson Content</label>

                <textarea name="content"
                          rows="8"
                          placeholder="Write the main lesson content here..."
                          class="w-full rounded-xl border border-neutral-700 bg-neutral-800 text-white p-3 outline-none placeholder:text-gray-500 focus:border-purple-500 transition">{{ old('content') }}</textarea>

                <p class="mt-1 text-xs text-gray-500">
                    This is the reading material that students will review before marking the lesson as complete.
                </p>

                @error('content')
                    <p class="text-sm text-red-400 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Type --}}
            <div>
                <label class="block mb-1 text-sm font-medium text-gray-300">Lesson Type</label>

                <select name="lesson_type"
                        class="w-full rounded-xl border border-neutral-700 bg-neutral-800 text-white p-3 outline-none focus:border-purple-500 transition">

                    <option value="text" @selected(old('lesson_type') == 'text')>Text</option>
                    <option value="video" @selected(old('lesson_type') == 'video')>Video</option>
                    <option value="document" @selected(old('lesson_type') == 'document')>Document</option>
                    <option value="quiz" @selected(old('lesson_type') == 'quiz')>Quiz</option>

                </select>

                <p class="mt-1 text-xs text-gray-500">
                    Choose Text for reading lessons, Video if the lesson uses a YouTube or external video link.
                </p>

                @error('lesson_type')
                    <p class="text-sm text-red-400 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Video --}}
            <div>
                <label class="block mb-1 text-sm font-medium text-gray-300">Video URL</label>

                <input type="url"
                       name="video_url"
                       value="{{ old('video_url') }}"
                       placeholder="Example: https://www.youtube.com/watch?v=..."
                       class="w-full rounded-xl border border-neutral-700 bg-neutral-800 text-white p-3 outline-none placeholder:text-gray-500 focus:border-purple-500 transition">

                <p class="mt-1 text-xs text-gray-500">
                    Optional. Add this only if the lesson has a video resource.
                </p>

                @error('video_url')
                    <p class="text-sm text-red-400 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Grid --}}
            <div class="grid gap-4 md:grid-cols-2">

                <div>
                    <label class="block mb-1 text-sm font-medium text-gray-300">Lesson Order</label>

                    <input type="number"
                           name="lesson_order"
                           value="{{ old('lesson_order', 1) }}"
                           min="1"
                           placeholder="Example: 1"
                           class="w-full rounded-xl border border-neutral-700 bg-neutral-800 text-white p-3 outline-none placeholder:text-gray-500 focus:border-purple-500 transition"
                           required>

                    <p class="mt-1 text-xs text-gray-500">
                        This controls the order of the lesson in the course.
                    </p>

                    @error('lesson_order')
                        <p class="text-sm text-red-400 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block mb-1 text-sm font-medium text-gray-300">Duration (minutes)</label>

                    <input type="number"
                           name="duration_minutes"
                           value="{{ old('duration_minutes') }}"
                           min="1"
                           placeholder="Example: 30"
                           class="w-full rounded-xl border border-neutral-700 bg-neutral-800 text-white p-3 outline-none placeholder:text-gray-500 focus:border-purple-500 transition">

                    <p class="mt-1 text-xs text-gray-500">
                        Estimated time needed to complete this lesson.
                    </p>

                    @error('duration_minutes')
                        <p class="text-sm text-red-400 mt-1">{{ $message }}</p>
                    @enderror
                </div>

            </div>

            {{-- Checkboxes --}}
            <div class="rounded-xl border border-neutral-700 bg-neutral-800/50 p-4 space-y-3">

                <label class="flex items-start gap-2 cursor-pointer">
                    <input type="checkbox"
                           name="is_preview"
                           class="mt-1 rounded border-neutral-600 bg-neutral-800 text-purple-500 focus:ring-purple-500 focus:ring-offset-neutral-900"
                           @checked(old('is_preview'))>

                    <span>
                        <span class="block font-medium text-gray-200">Free Preview</span>
                        <span class="text-sm text-gray-500">
                            Allow students to preview this lesson before enrolling.
                        </span>
                    </span>
                </label>

                <label class="flex items-start gap-2 cursor-pointer">
                    <input type="checkbox"
                           name="is_published"
                           class="mt-1 rounded border-neutral-600 bg-neutral-800 text-purple-500 focus:ring-purple-500 focus:ring-offset-neutral-900"
                           checked>

                    <span>
                        <span class="block font-medium text-gray-200">Published</span>
                        <span class="text-sm text-gray-500">
                            Make this lesson visible to students.
                        </span>
                    </span>
                </label>

            </div>

            {{-- Buttons --}}
            <div class="flex gap-3 pt-2">

                <button type="submit"
                        class="rounded-xl bg-purple-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-purple-500 transition">
                    Save Lesson
                </button>

                <a href="{{ route('teacher.my-courses') }}"
                   class="rounded-xl border border-neutral-600 bg-neutral-800 px-6 py-2.5 text-sm font-semibold text-white hover:bg-neutral-700 transition">
                    Cancel
                </a>

            </div>

        </form>

    </div>

</div>

</x-layouts::app>