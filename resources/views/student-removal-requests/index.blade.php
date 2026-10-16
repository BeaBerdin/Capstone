
<x-layouts::app :title="'Student Removal Requests'">

<div class="mx-auto max-w-7xl space-y-6 p-4 md:p-6">

    <div>
        <h1 class="text-2xl font-bold text-neutral-900 dark:text-white">
            Student Removal Requests
        </h1>
        <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
            Review and decide on instructor requests to remove students from courses.
        </p>
    </div>

    @if (session('success'))
        <div class="rounded-xl border border-green-200 bg-green-50 p-4 text-green-800">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-red-800">
            <ul class="list-inside list-disc">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="overflow-hidden rounded-2xl border border-neutral-200 bg-white shadow-sm dark:border-neutral-700 dark:bg-neutral-900">

        <div class="border-b border-neutral-200 p-5 dark:border-neutral-700">
            <h2 class="font-semibold text-neutral-900 dark:text-white">
                Requests for My Department
            </h2>
        </div>

        @if ($requests->isEmpty())
            <div class="p-10 text-center">
                <h3 class="font-semibold text-neutral-800 dark:text-white">
                    No removal requests found
                </h3>
                <p class="mt-2 text-sm text-neutral-500">
                    Instructor requests from your department will appear here.
                </p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-neutral-50 text-neutral-600 dark:bg-neutral-800 dark:text-neutral-300">
                        <tr>
                            <th class="p-4">Instructor</th>
                            <th class="p-4">Student</th>
                            <th class="p-4">Course</th>
                            <th class="p-4">Reason</th>
                            <th class="p-4">Status</th>
                            <th class="p-4">Decision</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-neutral-200 dark:divide-neutral-700">
                        @foreach ($requests as $item)
                            <tr>
                                <td class="p-4 text-neutral-800 dark:text-neutral-200">
                                    {{ $item->instructor->name ?? 'Unavailable' }}
                                </td>

                                <td class="p-4 font-medium text-neutral-900 dark:text-white">
                                    {{ $item->student->name ?? 'Unavailable' }}
                                    <div class="mt-1 text-xs text-neutral-500">
                                        {{ $item->student->email ?? '' }}
                                    </div>
                                </td>

                                <td class="p-4 text-neutral-700 dark:text-neutral-300">
                                    {{ $item->course->title ?? 'Unavailable' }}
                                </td>

                                <td class="max-w-xs whitespace-normal p-4 text-neutral-600 dark:text-neutral-300">
                                    {{ $item->reason }}
                                </td>

                                <td class="p-4">
                                    @php
                                        $statusClasses = [
                                            'pending' => 'bg-amber-100 text-amber-800',
                                            'approved' => 'bg-green-100 text-green-800',
                                            'rejected' => 'bg-red-100 text-red-800',
                                        ];
                                    @endphp

                                    <span class="inline-block rounded-full px-3 py-1 text-xs font-semibold {{ $statusClasses[$item->status] ?? 'bg-neutral-100 text-neutral-700' }}">
                                        {{ ucfirst($item->status) }}
                                    </span>

                                    @if ($item->reviewed_at)
                                        <div class="mt-1 text-xs text-neutral-500">
                                            {{ $item->reviewed_at->format('M d, Y') }}
                                        </div>
                                    @endif
                                </td>

                                <td class="min-w-64 p-4">
                                    @if ($item->status === 'pending')
                                        <form
                                            method="POST"
                                            action="{{ route('admin.student-removal-requests.review', $item) }}"
                                            class="space-y-3"
                                        >
                                            @csrf

                                            <textarea
                                                name="review_note"
                                                rows="2"
                                                maxlength="2000"
                                                placeholder="Review note (optional; required if rejecting)"
                                                class="w-full rounded-lg border border-neutral-300 bg-white p-2 text-sm text-neutral-900 dark:border-neutral-600 dark:bg-neutral-800 dark:text-white"
                                            ></textarea>

                                            <div class="flex flex-wrap gap-2">
                                                <button
                                                    type="submit"
                                                    name="decision"
                                                    value="approved"
                                                    onclick="return confirm('Approve this request and remove the student from this course?')"
                                                    class="rounded-lg bg-green-600 px-3 py-2 text-xs font-semibold text-white hover:bg-green-700"
                                                >
                                                    Approve
                                                </button>

                                                <button
                                                    type="submit"
                                                    name="decision"
                                                    value="rejected"
                                                    onclick="return confirm('Reject this student removal request?')"
                                                    class="rounded-lg bg-red-600 px-3 py-2 text-xs font-semibold text-white hover:bg-red-700"
                                                >
                                                    Reject
                                                </button>
                                            </div>
                                        </form>
                                    @else
                                        <div class="text-xs text-neutral-600 dark:text-neutral-300">
                                            <p>
                                                Reviewed by:
                                                {{ $item->reviewer->name ?? 'Unavailable' }}
                                            </p>

                                            @if ($item->review_note)
                                                <p class="mt-2">
                                                    Note: {{ $item->review_note }}
                                                </p>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="border-t border-neutral-200 p-4 dark:border-neutral-700">
                {{ $requests->links() }}
            </div>
        @endif

    </div>

</div>

</x-layouts::app>
