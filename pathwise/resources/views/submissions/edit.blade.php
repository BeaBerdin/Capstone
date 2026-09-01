<x-layouts::app :title="__('Edit Submission')">

<h1 class="text-2xl font-bold mb-6">
    Edit Submission
</h1>

<form action="{{ route('submissions.update', $submission) }}"
      method="POST"
      class="space-y-4">

    @csrf
    @method('PUT')

    <select name="student_id"
            class="w-full border rounded p-3">

        @foreach($students as $student)
            <option value="{{ $student->id }}"
                {{ $submission->student_id == $student->id ? 'selected' : '' }}>
                {{ $student->name }}
            </option>
        @endforeach

    </select>

    <select name="assignment_id"
            class="w-full border rounded p-3">

        @foreach($assignments as $assignment)
            <option value="{{ $assignment->id }}"
                {{ $submission->assignment_id == $assignment->id ? 'selected' : '' }}>
                {{ $assignment->title }}
            </option>
        @endforeach

    </select>

    <textarea
        name="answer_text"
        class="w-full border rounded p-3">{{ $submission->answer_text }}</textarea>

    <input
        type="text"
        name="file_path"
        value="{{ $submission->file_path }}"
        class="w-full border rounded p-3">

    <input
        type="number"
        name="score"
        value="{{ $submission->score }}"
        class="w-full border rounded p-3">

    <textarea
        name="feedback"
        class="w-full border rounded p-3">{{ $submission->feedback }}</textarea>

    <select
        name="status"
        class="w-full border rounded p-3">

        <option value="submitted"
            {{ $submission->status == 'submitted' ? 'selected' : '' }}>
            Submitted
        </option>

        <option value="graded"
            {{ $submission->status == 'graded' ? 'selected' : '' }}>
            Graded
        </option>

        <option value="returned"
            {{ $submission->status == 'returned' ? 'selected' : '' }}>
            Returned
        </option>

    </select>

    <button class="bg-blue-600 text-white px-4 py-2 rounded">
        Update Submission
    </button>

</form>

</x-layouts::app>