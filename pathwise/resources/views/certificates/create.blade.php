<x-layouts::app :title="__('Create Certificate')">

<h1 class="text-2xl font-bold mb-6">Create Certificate</h1>

<form action="{{ route('certificates.store') }}"
      method="POST"
      class="space-y-4">

    @csrf

    <select name="student_id" class="w-full border rounded p-3">
        <option value="">Select Student</option>

        @foreach($students as $student)
            <option value="{{ $student->id }}">
                {{ $student->name }}
            </option>
        @endforeach
    </select>

    <select name="course_id" class="w-full border rounded p-3">
        <option value="">Select Course</option>

        @foreach($courses as $course)
            <option value="{{ $course->id }}">
                {{ $course->title }}
            </option>
        @endforeach
    </select>

    <input type="text"
           name="certificate_number"
           placeholder="Certificate Number"
           class="w-full border rounded p-3">

    <input type="date"
           name="issued_date"
           class="w-full border rounded p-3">

    <input type="text"
           name="certificate_file"
           placeholder="Certificate File Path (optional)"
           class="w-full border rounded p-3">

    <select name="status" class="w-full border rounded p-3">
        <option value="issued">Issued</option>
        <option value="revoked">Revoked</option>
    </select>

    <button type="submit"
            class="bg-blue-600 text-white px-4 py-2 rounded">
        Save Certificate
    </button>

</form>

</x-layouts::app>