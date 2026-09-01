<x-layouts::app :title="__('Create Submission')">

<h1 class="text-2xl font-bold mb-6">
    Create Submission
</h1>

<form action="{{ route('submissions.store') }}"
      method="POST"
      class="space-y-4">

    @csrf

    <select name="student_id"
            class="w-full border rounded p-3">

        <option value="">Select Student</option>

        @foreach($students as $student)
            <option value="{{ $student->id }}">
                {{ $student->name }}
            </option>
        @endforeach

    </select>

    <select name="assignment_id"
            class="w-full border rounded p-3">

        <option value="">Select Assignment</option>

        @foreach($assignments as $assignment)
            <option value="{{ $assignment->id }}">
                {{ $assignment->title }}
            </option>
        @endforeach

    </select>

    <textarea
        name="answer_text"
        placeholder="Answer"
        class="w-full border rounded p-3"></textarea>

    <input
        type="text"
        name="file_path"
        placeholder="File Path"
        class="w-full border rounded p-3">

    <input
        type="number"
        name="score"
        placeholder="Score"
        class="w-full border rounded p-3">

    <textarea
        name="feedback"
        placeholder="Feedback"
        class="w-full border rounded p-3"></textarea>

    <select
        name="status"
        class="w-full border rounded p-3">

        <option value="submitted">Submitted</option>
        <option value="graded">Graded</option>
        <option value="returned">Returned</option>

    </select>

    <button class="bg-blue-600 text-white px-4 py-2 rounded">
        Save Submission
    </button>

</form>

</x-layouts::app>