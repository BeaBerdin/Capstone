<x-layouts::app :title="'Course Students'">

<div class="space-y-6">

    <div>
        <h1 class="text-3xl font-bold text-black">
            {{ $course->title }}
        </h1>

        <p class="text-black">
            Enrolled Students
        </p>
    </div>

    <div class="bg-neutral-400 border border-neutral-700 rounded-xl overflow-hidden">

        <table class="w-full">

            <thead>
                <tr class="border-b border-neutral-700">
                    <th class="p-4 text-left">Student</th>
                    <th class="p-4 text-left">Email</th>
                    <th class="p-4 text-left">Status</th>
                    <th class="p-4 text-left">Enrolled At</th>
                    <th class="p-4 text-left">Progress</th>
                    <th class="p-4 text-left">Actions</th>
                </tr>
            </thead>

            <tbody>

                @forelse($enrollments as $enrollment)

                    <tr class="border-b border-neutral-800">

                        <td class="p-4">
                            {{ $enrollment->student->name }}
                        </td>

                        <td class="p-4">
                            {{ $enrollment->student->email }}
                        </td>

                        <td class="p-4">
                            {{ ucfirst($enrollment->status) }}
                        </td>

                        <td class="p-4">
                            {{ $enrollment->enrolled_at }}
                        </td>

                        <td class="p-4">
                            {{ number_format($enrollment->progress_percentage, 0) }}%
                        </td>

                        <td class="p-4">
                            <a
                                href="{{ route('teacher.student.progress', [$course, $enrollment->student]) }}"
                                class="px-3 py-2 bg-green-600 text-white rounded-lg text-sm hover:bg-green-700"
                            >
                                View Progress
                            </a>
                        </td>

                    </tr>

                @empty

                    <tr>
                        <td colspan="6" class="p-6 text-center text-gray-400">
                            No students enrolled yet.
                        </td>
                    </tr>

                @endforelse

            </tbody>

        </table>

    </div>

</div>

</x-layouts::app>