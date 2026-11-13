{{--
    Administrator course fields, shared by Create and Edit. $course is null
    when creating. Field names, options and defaults are unchanged
    (Rejected is only offered when editing, as before).
--}}
@php
    $course = $course ?? null;

    // "Approved" is not offered: no page treats it as visible to students.
    $statuses = ['draft' => 'Draft', 'pending' => 'Pending']
        + ($course ? ['rejected' => 'Rejected'] : [])
        + ['published' => 'Published'];
@endphp

<x-pw.form-section title="Course Information" first>
    <x-slot:icon>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20V3H6.5A2.5 2.5 0 0 0 4 5.5z"></path><path d="M4 19.5A2.5 2.5 0 0 0 6.5 22H20v-5"></path></svg>
    </x-slot:icon>

    <x-pw.field label="Course Title" for="title" required>
        <input id="title" type="text" name="title" required maxlength="255"
               value="{{ old('title', $course?->title) }}"
               placeholder="Example: Entrepreneurship Essentials"
               class="sad-field">
    </x-pw.field>

    <x-pw.field label="Description" for="description" required hint="Shown on the marketplace and the course page.">
        <textarea id="description" name="description" rows="5" required
                  placeholder="Describe what students will learn..."
                  class="sad-field">{{ old('description', $course?->description) }}</textarea>
    </x-pw.field>

    <x-pw.field label="Category" for="category_id" required>
        <select id="category_id" name="category_id" required class="sad-field">
            <option value="">Select Category</option>
            @foreach($categories as $category)
                <option value="{{ $category->id }}" @selected(old('category_id', $course?->category_id) == $category->id)>
                    {{ $category->name }}
                </option>
            @endforeach
        </select>
    </x-pw.field>
</x-pw.form-section>

<x-pw.form-section title="Pricing & Level">
    <x-slot:icon>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3v18h18"></path><path d="m7 15 4-4 3 3 5-6"></path></svg>
    </x-slot:icon>

    <div class="grid gap-5 sm:grid-cols-2">
        <x-pw.field label="Price (PHP)" for="price" required hint="Enter 0 to make this course free.">
            <input id="price" type="number" step="0.01" min="0" name="price" required
                   value="{{ old('price', $course?->price ?? 0) }}"
                   class="sad-field">
        </x-pw.field>

        <x-pw.field label="Estimated Hours" for="estimated_hours" hint="Optional.">
            <input id="estimated_hours" type="number" min="1" name="estimated_hours"
                   value="{{ old('estimated_hours', $course?->estimated_hours) }}"
                   class="sad-field">
        </x-pw.field>
    </div>

    <div class="grid gap-5 sm:grid-cols-2">
        <x-pw.field label="Difficulty Level" for="difficulty_level" required>
            <select id="difficulty_level" name="difficulty_level" class="sad-field">
                @foreach(['beginner' => 'Beginner', 'intermediate' => 'Intermediate', 'advanced' => 'Advanced'] as $value => $label)
                    <option value="{{ $value }}" @selected(old('difficulty_level', $course?->difficulty_level ?? 'intermediate') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </x-pw.field>

        <x-pw.field label="Course Status" for="status" required hint="Published courses appear in the marketplace.">
            <select id="status" name="status" class="sad-field">
                @foreach($statuses as $value => $label)
                    <option value="{{ $value }}" @selected(old('status', $course?->status ?? 'published') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </x-pw.field>
    </div>

    <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-violet-100 bg-violet-50/50 px-4 py-3">
        <input type="checkbox" name="certificate_available" value="1"
               class="mt-1 rounded border-slate-300 text-violet-600 focus:ring-violet-500"
               @checked(old('certificate_available', $course?->certificate_available ?? true))>
        <span>
            <span class="block text-sm font-semibold text-slate-700">Certificate Available</span>
            <span class="mt-0.5 block text-xs text-slate-500">
                Allow students to receive a certificate after successfully completing the course.
            </span>
        </span>
    </label>
</x-pw.form-section>
