<x-layouts::app :title="__('Edit Lesson')">

<div class="min-h-screen bg-black p-6">

    <div class="mx-auto max-w-4xl space-y-6">

        {{-- Header --}}
        <div class="rounded-2xl border border-purple-500/20 bg-linear-to-r from-purple-900/40 via-purple-900/20 to-black p-6 shadow-xl">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <span class="inline-flex rounded-full bg-purple-500/20 px-3 py-1 text-xs font-semibold text-purple-300">
                        Lesson Management
                    </span>
                    <h1 class="mt-3 text-3xl font-bold text-white">
                        Edit Lesson
                    </h1>
                    <p class="mt-2 text-sm text-gray-400">
                        Update lesson details, content, order, and visibility.
                    </p>
                </div>
            </div>
        </div>

        {{-- Form Card --}}
        <div class="rounded-2xl border border-purple-500/20 bg-zinc-950 p-6 shadow-xl">

            <form action="{{ route('lessons.update', $lesson) }}"
                  method="POST"
                  class="space-y-6">

                @csrf
                @method('PUT')

                {{-- Course Select --}}
                <div>
                    <label class="mb-2 block text-sm font-semibold text-white">
                        Course
                    </label>
                    <select name="course_id"
                            class="w-full rounded-xl border border-purple-500/20 bg-[#111111] p-3 text-white focus:border-purple-500 focus:outline-none focus:ring-1 focus:ring-purple-500"
                            required>
                        @foreach($courses as $course)
                            <option value="{{ $course->id }}"
                                @selected(old('course_id', $lesson->course_id) == $course->id)>
                                {{ $course->title }}
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-gray-500">
                        Select the course where this lesson belongs.
                    </p>
                    @error('course_id')
                        <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Lesson Title --}}
                <div>
                    <label class="mb-2 block text-sm font-semibold text-white">
                        Lesson Title
                    </label>
                    <input type="text"
                           name="title"
                           value="{{ old('title', $lesson->title) }}"
                           placeholder="Example: Income Statement"
                           class="w-full rounded-xl border border-purple-500/20 bg-[#111111] p-3 text-white placeholder-gray-600 focus:border-purple-500 focus:outline-none focus:ring-1 focus:ring-purple-500"
                           required>
                    <p class="mt-1 text-xs text-gray-500">
                        Use a clear and specific title for the lesson.
                    </p>
                    @error('title')
                        <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Lesson Content --}}
                <div>
                    <label class="mb-2 block text-sm font-semibold text-white">
                        Lesson Content
                    </label>
                    <textarea name="content"
                              rows="8"
                              placeholder="Write the main lesson content here..."
                              class="w-full rounded-xl border border-purple-500/20 bg-[#111111] p-3 text-white placeholder-gray-600 focus:border-purple-500 focus:outline-none focus:ring-1 focus:ring-purple-500">{{ old('content', $lesson->content) }}</textarea>
                    <p class="mt-1 text-xs text-gray-500">
                        This is the reading material shown to students in the lesson page.
                    </p>
                    @error('content')
                        <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Lesson Type --}}
                <div>
                    <label class="mb-2 block text-sm font-semibold text-white">
                        Lesson Type
                    </label>
                    <select name="lesson_type"
                            class="w-full rounded-xl border border-purple-500/20 bg-[#111111] p-3 text-white focus:border-purple-500 focus:outline-none focus:ring-1 focus:ring-purple-500">
                        <option value="text" @selected(old('lesson_type', $lesson->lesson_type) == 'text')>Text</option>
                        <option value="video" @selected(old('lesson_type', $lesson->lesson_type) == 'video')>Video</option>
                        <option value="document" @selected(old('lesson_type', $lesson->lesson_type) == 'document')>Document</option>
                        <option value="quiz" @selected(old('lesson_type', $lesson->lesson_type) == 'quiz')>Quiz</option>
                    </select>
                    <p class="mt-1 text-xs text-gray-500">
                        Choose the type that best describes the lesson.
                    </p>
                    @error('lesson_type')
                        <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Video URL --}}
                <div>
                    <label class="mb-2 block text-sm font-semibold text-white">
                        Video URL
                    </label>
                    <input type="url"
                           name="video_url"
                           value="{{ old('video_url', $lesson->video_url) }}"
                           placeholder="Example: https://www.youtube.com/watch?v=..."
                           class="w-full rounded-xl border border-purple-500/20 bg-[#111111] p-3 text-white placeholder-gray-600 focus:border-purple-500 focus:outline-none focus:ring-1 focus:ring-purple-500">
                    <p class="mt-1 text-xs text-gray-500">
                        Optional. Add this only if the lesson has a video resource.
                    </p>
                    @error('video_url')
                        <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Lesson Order & Duration --}}
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-white">
                            Lesson Order
                        </label>
                        <input type="number"
                               name="lesson_order"
                               value="{{ old('lesson_order', $lesson->lesson_order) }}"
                               min="1"
                               class="w-full rounded-xl border border-purple-500/20 bg-[#111111] p-3 text-white focus:border-purple-500 focus:outline-none focus:ring-1 focus:ring-purple-500"
                               required>
                        <p class="mt-1 text-xs text-gray-500">
                            This controls the sequence of the lesson in the course.
                        </p>
                        @error('lesson_order')
                            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-white">
                            Duration in Minutes
                        </label>
                        <input type="number"
                               name="duration_minutes"
                               value="{{ old('duration_minutes', $lesson->duration_minutes) }}"
                               min="1"
                               placeholder="Example: 30"
                               class="w-full rounded-xl border border-purple-500/20 bg-[#111111] p-3 text-white placeholder-gray-600 focus:border-purple-500 focus:outline-none focus:ring-1 focus:ring-purple-500">
                        <p class="mt-1 text-xs text-gray-500">
                            Estimated time needed to complete this lesson.
                        </p>
                        @error('duration_minutes')
                            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Checkboxes --}}
                <div class="rounded-xl border border-purple-500/20 bg-[#111111] p-4 space-y-3">
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="checkbox"
                               name="is_preview"
                               class="mt-1 h-4 w-4 rounded border-purple-500/30 bg-zinc-950 text-purple-600 focus:ring-purple-500 focus:ring-offset-0"
                               @checked(old('is_preview', $lesson->is_preview))>
                        <div>
                            <span class="block font-semibold text-white">Free Preview</span>
                            <span class="text-sm text-gray-500">
                                Allow students to preview this lesson before enrolling.
                            </span>
                        </div>
                    </label>

                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="checkbox"
                               name="is_published"
                               class="mt-1 h-4 w-4 rounded border-purple-500/30 bg-zinc-950 text-purple-600 focus:ring-purple-500 focus:ring-offset-0"
                               @checked(old('is_published', $lesson->is_published))>
                        <div>
                            <span class="block font-semibold text-white">Published</span>
                            <span class="text-sm text-gray-500">
                                Make this lesson visible to students.
                            </span>
                        </div>
                    </label>
                </div>

                {{-- Buttons --}}
                <div class="flex gap-3 pt-2">
                    <button type="submit"
                            class="inline-flex items-center gap-2 rounded-xl bg-purple-600 px-6 py-3 text-sm font-semibold text-white shadow-lg transition hover:bg-purple-700">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        Update Lesson
                    </button>

                    <a href="{{ route('lessons.index') }}"
                       class="inline-flex items-center gap-2 rounded-xl border border-purple-500/20 bg-[#111111] px-6 py-3 text-sm font-semibold text-gray-300 transition hover:bg-purple-500/10">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        Cancel
                    </a>
                </div>

            </form>

        </div>

    </div>
</div>

</x-layouts::app>