{{--
    Learning path fields, shared by Create and Edit. $learningPath is null
    when creating; $selectedCourses holds the ids of the path's courses.
--}}
@php
    $learningPath = $learningPath ?? null;
    $checkedCourses = array_map('intval', old('course_ids', $selectedCourses ?? []));
@endphp

<x-pw.form-section title="Path Details" first>
    <x-slot:icon>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="6" cy="19" r="2"></circle><circle cx="18" cy="5" r="2"></circle><path d="M8 19h7a3.5 3.5 0 0 0 0-7H9a3.5 3.5 0 0 1 0-7h7"></path></svg>
    </x-slot:icon>

    <x-pw.field label="Path Name" for="name" required>
        <input id="name" type="text" name="name" required maxlength="255"
               value="{{ old('name', $learningPath?->name) }}"
               placeholder="e.g. Web Development Fundamentals"
               class="sad-field">
    </x-pw.field>

    <x-pw.field label="Description" for="description" hint="Optional. What students achieve by following this path.">
        <textarea id="description" name="description" rows="4"
                  placeholder="Short description of this learning path..."
                  class="sad-field">{{ old('description', $learningPath?->description) }}</textarea>
    </x-pw.field>
</x-pw.form-section>

<x-pw.form-section title="Courses">
    <x-slot:icon>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20V3H6.5A2.5 2.5 0 0 0 4 5.5z"></path><path d="M4 19.5A2.5 2.5 0 0 0 6.5 22H20v-5"></path></svg>
    </x-slot:icon>

    @if($courses->isEmpty())
        <p class="rounded-2xl border border-dashed border-slate-200 px-4 py-6 text-center text-sm text-slate-500">
            No courses are available yet.
        </p>
    @else
        <div class="grid gap-3 sm:grid-cols-2">
            @foreach($courses as $course)
                <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 transition hover:border-violet-200 has-checked:border-violet-300 has-checked:bg-violet-50/60">
                    <input type="checkbox" name="course_ids[]" value="{{ $course->id }}"
                           @checked(in_array($course->id, $checkedCourses, true))
                           class="h-4 w-4 rounded border-slate-300 text-violet-600 focus:ring-violet-500">
                    <span class="min-w-0 truncate font-medium">{{ $course->title }}</span>
                </label>
            @endforeach
        </div>
    @endif

    @error('course_ids')
        <p class="text-xs font-medium text-rose-600">{{ $message }}</p>
    @enderror
</x-pw.form-section>
