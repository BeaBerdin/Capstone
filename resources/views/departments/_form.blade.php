{{-- Department fields, shared by Create and Edit. $department is null when creating. --}}
@php
    $department = $department ?? null;
@endphp

<x-pw.form-section title="Department Details" first>
    <x-slot:icon>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6M9 10h.01M15 10h.01"></path></svg>
    </x-slot:icon>

    <x-pw.field label="Name" for="name" required>
        <input id="name" type="text" name="name" required maxlength="255"
               value="{{ old('name', $department?->name) }}"
               placeholder="e.g. Information Technology"
               class="sad-field">
    </x-pw.field>

    <x-pw.field label="Short code" for="code" hint="Optional. Up to 10 letters or numbers, e.g. CCS. Shown in the department head's greeting.">
        <input id="code" type="text" name="code" maxlength="10"
               value="{{ old('code', $department?->code) }}"
               placeholder="e.g. CCS"
               autocapitalize="characters"
               style="text-transform: uppercase"
               class="sad-field">
    </x-pw.field>

    <x-pw.field label="Description" for="description" hint="Optional.">
        <textarea id="description" name="description" rows="4"
                  placeholder="What does this department handle?"
                  class="sad-field">{{ old('description', $department?->description) }}</textarea>
    </x-pw.field>
</x-pw.form-section>
