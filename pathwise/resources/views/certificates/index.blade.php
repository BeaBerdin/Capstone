<x-layouts::app :title="__('Certificates')">

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold">Certificates</h1>

    <a href="{{ route('certificates.create') }}"
       class="bg-blue-600 text-white px-4 py-2 rounded">
        Add Certificate
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
            <th class="border p-2">Course</th>
            <th class="border p-2">Certificate No.</th>
            <th class="border p-2">Issued Date</th>
            <th class="border p-2">Status</th>
            <th class="border p-2">Actions</th>
        </tr>
    </thead>

    <tbody>
        @forelse($certificates as $certificate)
            <tr>
                <td class="border p-2">{{ $certificate->student->name ?? 'N/A' }}</td>
                <td class="border p-2">{{ $certificate->course->title ?? 'N/A' }}</td>
                <td class="border p-2">{{ $certificate->certificate_number }}</td>
                <td class="border p-2">
                    {{ \Carbon\Carbon::parse($certificate->issued_date)->format('M d, Y') }}
                </td>
                <td class="border p-2">{{ ucfirst($certificate->status) }}</td>
                <td class="border p-2">
                    <a href="{{ route('certificates.edit', $certificate) }}"
                       class="text-blue-500 mr-2">
                        Edit
                    </a>

                    <form action="{{ route('certificates.destroy', $certificate) }}"
                          method="POST"
                          class="inline">
                        @csrf
                        @method('DELETE')

                        <button onclick="return confirm('Delete certificate?')"
                                class="text-red-500">
                            Delete
                        </button>
                    </form>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="border p-4 text-center">
                    No certificates found.
                </td>
            </tr>
        @endforelse
    </tbody>
</table>

</x-layouts::app>