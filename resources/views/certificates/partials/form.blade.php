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

<style>
    .pw-card {
        background: #ffffff;
        border: 1px solid #e7e9ef;
        border-radius: 20px;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.035);
    }

    .pw-label {
        display: block;
        margin-bottom: 6px;
        font-size: 12px;
        font-weight: 600;
        color: #475569;
    }

    .pw-input {
        width: 100%;
        min-height: 44px;
        border: 1px solid #e1e4ea;
        border-radius: 12px;
        background: #ffffff;
        padding: 10px 14px;
        color: #334155;
        font-size: 14px;
        transition: border-color 160ms ease, box-shadow 160ms ease;
    }

    .pw-input:focus {
        outline: none;
        border-color: #8b5cf6;
        box-shadow: 0 0 0 4px rgba(139, 92, 246, 0.08);
    }
</style>

@if($errors->any())
    <div class="rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm text-rose-700" role="alert">
        <p class="font-semibold">Please fix the following:</p>
        <ul class="mt-2 list-disc space-y-1 pl-5">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="grid grid-cols-1 gap-5 sm:grid-cols-2">

    <div>
        <label for="student_id" class="pw-label">Student</label>
        <select id="student_id" name="student_id" class="pw-input" required>
            <option value="">Select Student</option>
            @foreach($students as $student)
                <option value="{{ $student->id }}" @selected((string) old('student_id', $certificate?->student_id) === (string) $student->id)>
                    {{ $student->name }}
                </option>
            @endforeach
        </select>
    </div>

    <div>
        <label for="course_id" class="pw-label">Course</label>
        <select id="course_id" name="course_id" class="pw-input" required>
            <option value="">Select Course</option>
            @foreach($courses as $course)
                <option value="{{ $course->id }}" @selected((string) old('course_id', $certificate?->course_id) === (string) $course->id)>
                    {{ $course->title }}
                </option>
            @endforeach
        </select>
    </div>

    <div>
        <label for="certificate_number" class="pw-label">Certificate Number</label>
        <input
            type="text"
            id="certificate_number"
            name="certificate_number"
            value="{{ old('certificate_number', $certificate?->certificate_number) }}"
            placeholder="Certificate Number"
            class="pw-input"
            required
        >
    </div>

    <div>
        <label for="issued_date" class="pw-label">Issued Date</label>
        <input
            type="date"
            id="issued_date"
            name="issued_date"
            value="{{ old('issued_date', $issuedDate) }}"
            class="pw-input"
            required
        >
    </div>

    <div>
        <label for="certificate_file" class="pw-label">
            Certificate File Path <span class="font-normal text-slate-400">(optional)</span>
        </label>
        <input
            type="text"
            id="certificate_file"
            name="certificate_file"
            value="{{ old('certificate_file', $certificate?->certificate_file) }}"
            placeholder="Certificate File Path (optional)"
            class="pw-input"
        >
    </div>

    <div>
        <label for="status" class="pw-label">Status</label>
        <select id="status" name="status" class="pw-input" required>
            <option value="issued" @selected(old('status', $certificate?->status ?? 'issued') === 'issued')>Issued</option>
            <option value="revoked" @selected(old('status', $certificate?->status) === 'revoked')>Revoked</option>
        </select>
    </div>

</div>
