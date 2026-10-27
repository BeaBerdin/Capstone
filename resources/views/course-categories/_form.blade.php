{{-- Category fields, shared by Create and Edit. $courseCategory is null when creating. --}}
@php
    $courseCategory = $courseCategory ?? null;
@endphp

<x-pw.form-section title="Category Details" first>
    <x-slot:icon>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path></svg>
    </x-slot:icon>

    <x-pw.field label="Category Name" for="name" required>
        <input id="name" type="text" name="name" required maxlength="255"
               value="{{ old('name', $courseCategory?->name) }}"
               placeholder="e.g. Programming, Business, Design"
               class="sad-field">
    </x-pw.field>

    <x-pw.field label="Description" for="description" hint="Optional. A short description of the courses in this category.">
        <textarea id="description" name="description" rows="5"
                  placeholder="Short description about this category..."
                  class="sad-field">{{ old('description', $courseCategory?->description) }}</textarea>
    </x-pw.field>
</x-pw.form-section>
