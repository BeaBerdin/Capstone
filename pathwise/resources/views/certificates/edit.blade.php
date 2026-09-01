<x-layouts::app :title="__('Edit Certificate')">

<h1 class="text-2xl font-bold mb-6">Edit Certificate</h1>

<form action="{{ route('certificates.update', $certificate) }}"
      method="POST"
      class="space-y-4">

    @csrf
    @method('PUT')

    <select name="student_id" class="w-full border rounded p-3">
        @foreach($students as $student)
            <option value="{{ $student->id }}"
                {{ $certificate->student_id == $student->id ? 'selected' : '' }}>
                {{ $student->name }}
            </option>
        @endforeach
    </select>

    <select name="course_id" class="w-full border rounded p-3">
        @foreach($courses as $course)
            <option value="{{ $course->id }}"
                {{ $certificate->course_id == $course->id ? 'selected' : '' }}>
                {{ $course->title }}
            </option>
        @endforeach
    </select>

    <input type="text"
           name="certificate_number"
           value="{{ $certificate->certificate_number }}"
           class="w-full border rounded p-3">

    <input type="date"
           name="issued_date"
           value="{{ $certificate->issued_date }}"
           class="w-full border rounded p-3">

    <input type="text"
           name="certificate_file"
           value="{{ $certificate->certificate_file }}"
           class="w-full border rounded p-3">

    <select name="status" class="w-full border rounded p-3">
        <option value="issued" {{ $certificate->status == 'issued' ? 'selected' : '' }}>
            Issued
        </option>

        <option value="revoked" {{ $certificate->status == 'revoked' ? 'selected' : '' }}>
            Revoked
        </option>
    </select>

    <button type="submit"
            class="bg-blue-600 text-white px-4 py-2 rounded">
        Update Certificate
    </button>

</form>

</x-layouts::app>