
<x-layouts::app :title="'Student Removal Requests'">

<div class="mx-auto max-w-7xl space-y-6 p-4 md:p-6">

    <div>
        <h1 class="text-2xl font-bold text-neutral-900 dark:text-white">
            Student Removal Requests
        </h1>
        <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
            Track requests submitted for Department Head approval.
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
                My Requests
            </h2>
        </div>

        @if ($requests->isEmpty())
            <div class="p-10 text-center">
                <div class="text-lg font-semibold text-neutral-800 dark:text-white">
                    No removal requests yet
                </div>
                <p class="mt-2 text-sm text-neutral-500">
                    Requests you submit will appear here.
                </p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-neutral-50 text-neutral-600 dark:bg-neutral-800 dark:text-neutral-300">
                        <tr>
                            <th class="p-4">Student</th>
                            <th class="p-4">Course</th>
                            <th class="p-4">Reason</th>
                            <th class="p-4">Status</th>
                            <th class="p-4">Review Details</th>
                            <th class="p-4">Date</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-neutral-200 dark:divide-neutral-700">
                        @foreach ($requests as $item)
                            <tr>
                                <td class="p-4 font-medium text-neutral-900 dark:text-white">
                                    {{ $item->student->name ?? 'Student unavailable' }}
                                </td>

                                <td class="p-4 text-neutral-700 dark:text-neutral-300">
                                    {{ $item->course->title ?? 'Course unavailable' }}
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

                                        $status = $item->status;
                                    @endphp

                                    <span class="inline-block rounded-full px-3 py-1 text-xs font-semibold {{ $statusClasses[$status] ?? 'bg-neutral-100 text-neutral-700' }}">
                                        {{ ucfirst($status) }}
                                    </span>
                                </td>

                                <td class="p-4 text-neutral-600 dark:text-neutral-300">
                                    <div>
                                        {{ $item->reviewer->name ?? 'Not reviewed yet' }}
                                    </div>

                                    @if ($item->review_note)
                                        <p class="mt-1 text-xs">
                                            {{ $item->review_note }}
                                        </p>
                                    @endif
                                </td>

                                <td class="whitespace-nowrap p-4 text-neutral-500">
                                    {{ $item->created_at?->format('M d, Y') }}
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
