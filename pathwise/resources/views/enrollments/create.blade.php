<x-layouts::app title="Create Enrollment">

    <h1 class="text-2xl font-bold mb-6">
        Create Enrollment
    </h1>

    <form action="{{ route('enrollments.store') }}" method="POST">

        @csrf

        <div class="space-y-4">

            <select name="student_id"
                    class="w-full border rounded p-3"
                    required>

                <option value="">
                    Select Student
                </option>

                @foreach($students as $student)
                    <option value="{{ $student->id }}">
                        {{ $student->name }}
                    </option>
                @endforeach

            </select>

            <select name="course_id"
                    class="w-full border rounded p-3"
                    required>

                <option value="">
                    Select Course
                </option>

                @foreach($courses as $course)
                    <option value="{{ $course->id }}">
                        {{ $course->title }}
                    </option>
                @endforeach

            </select>

            <select name="status"
                    class="w-full border rounded p-3">

                <option value="active">
                    Active
                </option>

                <option value="completed">
                    Completed
                </option>

                <option value="cancelled">
                    Cancelled
                </option>

            </select>

            <input type="number"
                   name="progress_percentage"
                   value="0"
                   min="0"
                   max="100"
                   class="w-full border rounded p-3"
                   placeholder="Progress Percentage">

            <button
                class="bg-blue-600 text-white px-4 py-2 rounded">
                Save Enrollment
            </button>

        </div>

    </form>

</x-layouts::app>