<x-layouts::app :title="__('Edit Assignment')">

<h1 class="text-2xl font-bold mb-6">Edit Assignment</h1>

<form action="{{ route('assignments.update', $assignment) }}"
      method="POST"
      class="space-y-4">

    @csrf
    @method('PUT')

    <select name="course_id" class="w-full border rounded p-3">
        @foreach($courses as $course)
            <option value="{{ $course->id }}"
                {{ $assignment->course_id == $course->id ? 'selected' : '' }}>
                {{ $course->title }}
            </option>
        @endforeach
    </select>

    <select name="lesson_id" class="w-full border rounded p-3">
        <option value="">No Lesson</option>

        @foreach($lessons as $lesson)
            <option value="{{ $lesson->id }}"
                {{ $assignment->lesson_id == $lesson->id ? 'selected' : '' }}>
                {{ $lesson->title }}
            </option>
        @endforeach
    </select>

    <input type="text"
           name="title"
           value="{{ $assignment->title }}"
           class="w-full border rounded p-3">

    <textarea name="description"
              class="w-full border rounded p-3">{{ $assignment->description }}</textarea>

    <input type="datetime-local"
           name="due_date"
           value="{{ $assignment->due_date ? date('Y-m-d\TH:i', strtotime($assignment->due_date)) : '' }}"
           class="w-full border rounded p-3">

    <input type="number"
           name="max_score"
           value="{{ $assignment->max_score }}"
           class="w-full border rounded p-3">

    <select name="is_published" class="w-full border rounded p-3">
        <option value="1" {{ $assignment->is_published ? 'selected' : '' }}>
            Published
        </option>
        <option value="0" {{ !$assignment->is_published ? 'selected' : '' }}>
            Draft
        </option>
    </select>

    <button type="submit"
            class="bg-blue-600 text-white px-4 py-2 rounded">
        Update Assignment
    </button>

</form>

</x-layouts::app>