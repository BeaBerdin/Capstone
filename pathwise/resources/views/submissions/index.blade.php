<x-layouts::app :title="__('Submissions')">

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold">Submissions</h1>

    <a href="{{ route('submissions.create') }}"
       class="bg-blue-600 text-white px-4 py-2 rounded">
        Add Submission
    </a>
</div>

@if(session('success'))
    <div class="bg-green-100 text-green-700 p-3 rounded mb-4">
        {{ session('success') }}
    </div>
@endif

<table class="w-full border">
    <thead>
        <tr>
            <th class="border p-2">Student</th>
            <th class="border p-2">Assignment</th>
            <th class="border p-2">Score</th>
            <th class="border p-2">Status</th>
            <th class="border p-2">Submitted At</th>
            <th class="border p-2">Actions</th>
        </tr>
    </thead>

    <tbody>
        @forelse($submissions as $submission)
            <tr>
                <td class="border p-2">
                    {{ $submission->student->name ?? 'N/A' }}
                </td>

                <td class="border p-2">
                    {{ $submission->assignment->title ?? 'N/A' }}
                </td>

                <td class="border p-2">
                    {{ $submission->score ?? '-' }}
                </td>

                <td class="border p-2">
                    {{ ucfirst($submission->status) }}
                </td>

                <td class="border p-2">
                    {{ $submission->submitted_at
                        ? \Carbon\Carbon::parse($submission->submitted_at)->format('M d, Y')
                        : '-'
                    }}
                </td>

                <td class="border p-2">
                    <a href="{{ route('submissions.edit', $submission) }}"
                       class="text-blue-500 mr-2">
                        Edit
                    </a>

                    <form action="{{ route('submissions.destroy', $submission) }}"
                          method="POST"
                          class="inline">

                        @csrf
                        @method('DELETE')

                        <button
                            onclick="return confirm('Delete submission?')"
                            class="text-red-500">
                            Delete
                        </button>

                    </form>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="border p-4 text-center">
                    No submissions found.
                </td>
            </tr>
        @endforelse
    </tbody>
</table>

</x-layouts::app>