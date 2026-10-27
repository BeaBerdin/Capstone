{{--
    Administrator lesson fields, shared by Create and Edit. $lesson is null
    when creating. Field names and options are unchanged.
--}}
@php
    $lesson = $lesson ?? null;
@endphp

<x-pw.form-section title="Lesson Details" first>
    <x-slot:icon>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><path d="M14 2v6h6M8 13h8M8 17h6"></path></svg>
    </x-slot:icon>

    <x-pw.field label="Course" for="course_id" required hint="Select the course where this lesson belongs.">
        <select id="course_id" name="course_id" required class="sad-field">
            <option value="">Select course</option>
            @foreach($courses as $course)
                <option value="{{ $course->id }}" @selected(old('course_id', $lesson?->course_id) == $course->id)>
                    {{ $course->title }}
                </option>
            @endforeach
        </select>
    </x-pw.field>

    <x-pw.field label="Lesson Title" for="title" required hint="Use a clear and specific title for the lesson.">
        <input id="title" type="text" name="title" required maxlength="255"
               value="{{ old('title', $lesson?->title) }}"
               placeholder="Example: Introduction to Accounting"
               class="sad-field">
    </x-pw.field>

    <x-pw.field label="Lesson Content" for="content" hint="The reading material students review before marking the lesson as complete.">
        <textarea id="content" name="content" rows="8"
                  placeholder="Write the main lesson content here..."
                  class="sad-field">{{ old('content', $lesson?->content) }}</textarea>
    </x-pw.field>
</x-pw.form-section>

<x-pw.form-section title="Format & Order">
    <x-slot:icon>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1.5"></rect><rect x="14" y="3" width="7" height="7" rx="1.5"></rect><rect x="3" y="14" width="7" height="7" rx="1.5"></rect><rect x="14" y="14" width="7" height="7" rx="1.5"></rect></svg>
    </x-slot:icon>

    <div class="grid gap-5 sm:grid-cols-2">
        <x-pw.field label="Lesson Type" for="lesson_type" required hint="Text for reading lessons; Video for a YouTube or external link.">
            <select id="lesson_type" name="lesson_type" class="sad-field">
                @foreach(['text' => 'Text', 'video' => 'Video', 'document' => 'Document', 'quiz' => 'Quiz'] as $value => $label)
                    <option value="{{ $value }}" @selected(old('lesson_type', $lesson?->lesson_type) == $value)>{{ $label }}</option>
                @endforeach
            </select>
        </x-pw.field>

        <x-pw.field label="Video URL" for="video_url" hint="Optional. Only if the lesson has a video.">
            <input id="video_url" type="url" name="video_url"
                   value="{{ old('video_url', $lesson?->video_url) }}"
                   placeholder="https://www.youtube.com/watch?v=..."
                   class="sad-field">
        </x-pw.field>
    </div>

    <div class="grid gap-5 sm:grid-cols-2">
        <x-pw.field label="Lesson Order" for="lesson_order" required hint="Controls the lesson's position in the course.">
            <input id="lesson_order" type="number" name="lesson_order" min="1" required
                   value="{{ old('lesson_order', $lesson?->lesson_order ?? 1) }}"
                   placeholder="Example: 1"
                   class="sad-field">
        </x-pw.field>

        <x-pw.field label="Duration (minutes)" for="duration_minutes" hint="Estimated time to complete the lesson.">
            <input id="duration_minutes" type="number" name="duration_minutes" min="1"
                   value="{{ old('duration_minutes', $lesson?->duration_minutes) }}"
                   placeholder="Example: 30"
                   class="sad-field">
        </x-pw.field>
    </div>
</x-pw.form-section>

<x-pw.form-section title="Visibility">
    <x-slot:icon>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
    </x-slot:icon>

    <div class="grid gap-3 sm:grid-cols-2">
        <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-violet-100 bg-violet-50/50 px-4 py-3">
            <input type="checkbox" name="is_published"
                   class="mt-1 rounded border-slate-300 text-violet-600 focus:ring-violet-500"
                   @checked(old('is_published', $lesson?->is_published ?? true))>
            <span>
                <span class="block text-sm font-semibold text-slate-700">Published</span>
                <span class="mt-0.5 block text-xs text-slate-500">Make this lesson visible to students.</span>
            </span>
        </label>

        <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3">
            <input type="checkbox" name="is_preview"
                   class="mt-1 rounded border-slate-300 text-violet-600 focus:ring-violet-500"
                   @checked(old('is_preview', $lesson?->is_preview))>
            <span>
                <span class="block text-sm font-semibold text-slate-700">Free Preview</span>
                <span class="mt-0.5 block text-xs text-slate-500">Allow students to preview this lesson before enrolling.</span>
            </span>
        </label>
    </div>
</x-pw.form-section>
