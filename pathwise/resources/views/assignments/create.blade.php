<x-layouts::app :title="__('Create Assignment')">

<h1 class="text-2xl font-bold mb-6">Create Assignment</h1>

<form action="{{ route('assignments.store') }}"
      method="POST"
      class="space-y-4">

    @csrf

    <select name="course_id" class="w-full border rounded p-3">
        <option value="">Select Course</option>

        @foreach($courses as $course)
            <option value="{{ $course->id }}">
                {{ $course->title }}
            </option>
        @endforeach
    </select>

    <select name="lesson_id" class="w-full border rounded p-3">
        <option value="">No Lesson</option>

        @foreach($lessons as $lesson)
            <option value="{{ $lesson->id }}">
                {{ $lesson->title }}
            </option>
        @endforeach
    </select>

    <input type="text"
           name="title"
           placeholder="Assignment Title"
           class="w-full border rounded p-3">

    <textarea name="description"
              placeholder="Assignment Description"
              class="w-full border rounded p-3"></textarea>

    <input type="datetime-local"
           name="due_date"
           class="w-full border rounded p-3">

    <input type="number"
           name="max_score"
           value="100"
           class="w-full border rounded p-3">

    <select name="is_published" class="w-full border rounded p-3">
        <option value="1">Published</option>
        <option value="0">Draft</option>
    </select>

    <button type="submit"
            class="bg-blue-600 text-white px-4 py-2 rounded">
        Save Assignment
    </button>

</form>

</x-layouts::app>