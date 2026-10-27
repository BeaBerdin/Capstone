{{--
    Administrator quiz fields, shared by Create and Edit. $quiz is null when
    creating. Field names and defaults (75%, 15 minutes, published) are unchanged.
--}}
@php
    $quiz = $quiz ?? null;
@endphp

<x-pw.form-section title="Quiz Details" first>
    <x-slot:icon>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"></circle><path d="M9.5 9a2.5 2.5 0 1 1 3.5 2.3c-.6.3-1 .9-1 1.6V14M12 17h.01"></path></svg>
    </x-slot:icon>

    <div class="grid gap-5 sm:grid-cols-2">
        <x-pw.field label="Course" for="course_id" required hint="The course this quiz belongs to.">
            <select id="course_id" name="course_id" required class="sad-field">
                <option value="">Select the course for this quiz</option>
                @foreach($courses as $course)
                    <option value="{{ $course->id }}" @selected(old('course_id', $quiz?->course_id) == $course->id)>
                        {{ $course->title }}
                    </option>
                @endforeach
            </select>
        </x-pw.field>

        <x-pw.field label="Related Lesson" for="lesson_id" hint="Leave empty for the course's final quiz.">
            <select id="lesson_id" name="lesson_id" class="sad-field">
                <option value="">No Lesson — this is a course final quiz</option>
                @foreach($lessons as $lesson)
                    <option value="{{ $lesson->id }}" @selected(old('lesson_id', $quiz?->lesson_id) == $lesson->id)>
                        {{ $lesson->course->title ?? 'No Course' }} — {{ $lesson->title }}
                    </option>
                @endforeach
            </select>
        </x-pw.field>
    </div>

    <x-pw.field label="Quiz Title" for="title" required>
        <input id="title" type="text" name="title" required maxlength="255"
               value="{{ old('title', $quiz?->title) }}"
               placeholder="Example: Entrepreneurship Final Quiz"
               class="sad-field">
    </x-pw.field>

    <x-pw.field label="Quiz Description" for="description" hint="Optional.">
        <textarea id="description" name="description" rows="4"
                  placeholder="Briefly describe what this quiz covers..."
                  class="sad-field">{{ old('description', $quiz?->description) }}</textarea>
    </x-pw.field>
</x-pw.form-section>

<x-pw.form-section title="Scoring & Availability">
    <x-slot:icon>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="13" r="8"></circle><path d="M12 9v4l2.5 2.5M9 2h6"></path></svg>
    </x-slot:icon>

    <div class="grid gap-5 sm:grid-cols-2">
        <x-pw.field label="Passing Score (%)" for="passing_score" required>
            <input id="passing_score" type="number" name="passing_score" min="1" max="100" required
                   value="{{ old('passing_score', $quiz?->passing_score ?? 75) }}"
                   class="sad-field">
        </x-pw.field>

        <x-pw.field label="Time Limit (minutes)" for="time_limit_minutes" hint="Optional. Leave blank for no time limit.">
            <input id="time_limit_minutes" type="number" name="time_limit_minutes" min="1"
                   value="{{ old('time_limit_minutes', $quiz ? $quiz->time_limit_minutes : 15) }}"
                   placeholder="Optional"
                   class="sad-field">
        </x-pw.field>
    </div>

    <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-violet-100 bg-violet-50/50 px-4 py-3">
        <input type="checkbox" name="is_published"
               class="mt-1 rounded border-slate-300 text-violet-600 focus:ring-violet-500"
               @checked(old('is_published', $quiz?->is_published ?? true))>
        <span>
            <span class="block text-sm font-semibold text-slate-700">Published</span>
            <span class="mt-0.5 block text-xs text-slate-500">
                Make this quiz available to students after they complete the required lessons.
            </span>
        </span>
    </label>
</x-pw.form-section>
