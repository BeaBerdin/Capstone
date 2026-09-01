<x-layouts::app title="Edit Enrollment">

    <h1 class="text-2xl font-bold mb-6">
        Edit Enrollment
    </h1>

    <form action="{{ route('enrollments.update',$enrollment) }}"
          method="POST">

        @csrf
        @method('PUT')

        <div class="space-y-4">

            <select name="student_id"
                    class="w-full border rounded p-3">

                @foreach($students as $student)

                    <option value="{{ $student->id }}"
                        {{ $enrollment->student_id == $student->id ? 'selected' : '' }}>

                        {{ $student->name }}

                    </option>

                @endforeach

            </select>

            <select name="course_id"
                    class="w-full border rounded p-3">

                @foreach($courses as $course)

                    <option value="{{ $course->id }}"
                        {{ $enrollment->course_id == $course->id ? 'selected' : '' }}>

                        {{ $course->title }}

                    </option>

                @endforeach

            </select>

            <select name="status"
                    class="w-full border rounded p-3">

                <option value="active"
                    {{ $enrollment->status == 'active' ? 'selected' : '' }}>
                    Active
                </option>

                <option value="completed"
                    {{ $enrollment->status == 'completed' ? 'selected' : '' }}>
                    Completed
                </option>

                <option value="cancelled"
                    {{ $enrollment->status == 'cancelled' ? 'selected' : '' }}>
                    Cancelled
                </option>

            </select>

            <input type="number"
                   name="progress_percentage"
                   value="{{ $enrollment->progress_percentage }}"
                   min="0"
                   max="100"
                   class="w-full border rounded p-3">

            <button
                class="bg-blue-600 text-white px-4 py-2 rounded">
                Update Enrollment
            </button>

        </div>

    </form>

</x-layouts::app>