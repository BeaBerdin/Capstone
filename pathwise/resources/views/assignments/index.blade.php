<x-layouts::app :title="__('Assignments')">

<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold">
        Assignments
    </h1>

    <a href="{{ route('assignments.create') }}"
       class="bg-blue-600 text-white px-4 py-2 rounded">
        Add Assignment
    </a>
</div>

@if(session('success')) <div class="bg-green-100 text-green-700 p-3 rounded mb-4">
{{ session('success') }} </div>
@endif

<table class="w-full border">
    <thead>
        <tr>
            <th class="border p-2">Course</th>
            <th class="border p-2">Lesson</th>
            <th class="border p-2">Title</th>
            <th class="border p-2">Due Date</th>
            <th class="border p-2">Max Score</th>
            <th class="border p-2">Status</th>
            <th class="border p-2">Actions</th>
        </tr>
    </thead>

```
<tbody>
    @forelse($assignments as $assignment)
        <tr>
            <td class="border p-2">
                {{ $assignment->course->title ?? 'No course' }}
            </td>

            <td class="border p-2">
                {{ $assignment->lesson->title ?? 'No lesson' }}
            </td>

            <td class="border p-2">
                {{ $assignment->title }}
            </td>

            <td class="border p-2">
                {{ $assignment->due_date
                    ? \Carbon\Carbon::parse($assignment->due_date)->format('M d, Y')
                    : 'No due date'
                }}
            </td>

            <td class="border p-2">
                {{ $assignment->max_score }}
            </td>

            <td class="border p-2">
                {{ $assignment->is_published ? 'Published' : 'Draft' }}
            </td>

            <td class="border p-2">
                <a href="{{ route('assignments.edit', $assignment) }}"
                   class="text-blue-500 mr-2">
                    Edit
                </a>

                <form action="{{ route('assignments.destroy', $assignment) }}"
                      method="POST"
                      class="inline">

                    @csrf
                    @method('DELETE')

                    <button onclick="return confirm('Delete this assignment?')"
                            class="text-red-500">
                        Delete
                    </button>

                </form>
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="7" class="border p-4 text-center">
                No assignments found.
            </td>
        </tr>
    @endforelse
</tbody>
```

</table>

</x-layouts::app>
