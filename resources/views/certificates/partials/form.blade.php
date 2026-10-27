{{--
    Certificate form fields, shared by certificates.create and certificates.edit.

    $certificate: the certificate being edited, or null when creating
--}}

@php
    $certificate = $certificate ?? null;

    $issuedDate = $certificate?->issued_date
        ? \Carbon\Carbon::parse($certificate->issued_date)->format('Y-m-d')
        : '';
@endphp

<x-pw.form-section title="Recipient" first>
    <x-slot:icon>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"></circle><path d="M4 21a8 8 0 0 1 16 0"></path></svg>
    </x-slot:icon>

    <div class="grid gap-5 sm:grid-cols-2">
        <x-pw.field label="Student" for="student_id" required>
            <select id="student_id" name="student_id" required class="sad-field">
                <option value="">Select student</option>
                @foreach($students as $student)
                    <option value="{{ $student->id }}" @selected((string) old('student_id', $certificate?->student_id) === (string) $student->id)>
                        {{ $student->name }}
                    </option>
                @endforeach
            </select>
        </x-pw.field>

        <x-pw.field label="Course" for="course_id" required>
            <select id="course_id" name="course_id" required class="sad-field">
                <option value="">Select course</option>
                @foreach($courses as $course)
                    <option value="{{ $course->id }}" @selected((string) old('course_id', $certificate?->course_id) === (string) $course->id)>
                        {{ $course->title }}
                    </option>
                @endforeach
            </select>
        </x-pw.field>
    </div>
</x-pw.form-section>

<x-pw.form-section title="Certificate Details">
    <x-slot:icon>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="9" r="6"></circle><path d="m8.5 14 -1.5 8 5-3 5 3-1.5-8"></path></svg>
    </x-slot:icon>

    <div class="grid gap-5 sm:grid-cols-2">
        <x-pw.field label="Certificate Number" for="certificate_number" required>
            <input id="certificate_number" type="text" name="certificate_number" required
                   value="{{ old('certificate_number', $certificate?->certificate_number) }}"
                   placeholder="e.g. PW-2026-0001"
                   class="sad-field">
        </x-pw.field>

        <x-pw.field label="Issued Date" for="issued_date" required>
            <input id="issued_date" type="date" name="issued_date" required
                   value="{{ old('issued_date', $issuedDate) }}"
                   class="sad-field">
        </x-pw.field>

        <x-pw.field label="Certificate File Path" for="certificate_file" hint="Optional.">
            <input id="certificate_file" type="text" name="certificate_file"
                   value="{{ old('certificate_file', $certificate?->certificate_file) }}"
                   placeholder="e.g. certificates/pw-2026-0001.pdf"
                   class="sad-field">
        </x-pw.field>

        <x-pw.field label="Status" for="status" required>
            <select id="status" name="status" required class="sad-field">
                <option value="issued" @selected(old('status', $certificate?->status ?? 'issued') === 'issued')>Issued</option>
                <option value="revoked" @selected(old('status', $certificate?->status) === 'revoked')>Revoked</option>
            </select>
        </x-pw.field>
    </div>
</x-pw.form-section>
