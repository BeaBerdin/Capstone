<x-layouts::app title="Enrollments">

    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Enrollments</h1>

        <a href="{{ route('enrollments.create') }}"
           class="px-4 py-2 bg-blue-600 text-white rounded">
            Add Enrollment
        </a>
    </div>

    @if(session('success'))
        <div class="mb-4 p-3 bg-green-100 text-green-700 rounded">
            {{ session('success') }}
        </div>
    @endif

    <div class="overflow-x-auto">
        <table class="w-full border">
            <thead>
                <tr>
                    <th class="border p-2">Student</th>
                    <th class="border p-2">Course</th>
                    <th class="border p-2">Status</th>
                    <th class="border p-2">Progress</th>
                    <th class="border p-2">Actions</th>
                </tr>
            </thead>

            <tbody>
                @foreach($enrollments as $enrollment)
                    <tr>
                        <td class="border p-2">
                            {{ $enrollment->student->name }}
                        </td>

                        <td class="border p-2">
                            {{ $enrollment->course->title }}
                        </td>

                        <td class="border p-2">
                            {{ $enrollment->status }}
                        </td>

                        <td class="border p-2">
                            {{ $enrollment->progress_percentage }}%
                        </td>

                        <td class="border p-2">
                            <a href="{{ route('enrollments.edit',$enrollment) }}"
                               class="text-blue-500">
                                Edit
                            </a>

                            <form action="{{ route('enrollments.destroy',$enrollment) }}"
                                  method="POST"
                                  class="inline">

                                @csrf
                                @method('DELETE')

                                <button
                                    onclick="return confirm('Delete Enrollment?')"
                                    class="text-red-500">
                                    Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>

        </table>
    </div>

</x-layouts::app>