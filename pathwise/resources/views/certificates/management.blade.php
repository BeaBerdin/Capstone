<x-layouts::app :title="'Certificates Management'">

<div class="space-y-6">

    <div>
        <h1 class="text-2xl font-bold">
            Certificates Management
        </h1>

        <p class="text-gray-00">
            View all issued student certificates.
        </p>
    </div>

    <div class="rounded-xl border bg-white p-6 shadow-sm dark:bg-neutral-900 dark:border-neutral-700">

        <table class="w-full">
            <thead>
                <tr class="border-b">
                    <th class="p-3 text-left">Certificate No.</th>
                    <th class="p-3 text-left">Student</th>
                    <th class="p-3 text-left">Course</th>
                    <th class="p-3 text-left">Issued Date</th>
                    <th class="p-3 text-left">Status</th>
                </tr>
            </thead>

            <tbody>
                @forelse($certificates as $certificate)
                    <tr class="border-b">
                        <td class="p-3">
                            {{ $certificate->certificate_number }}
                        </td>

                        <td class="p-3">
                            {{ $certificate->student->name ?? 'N/A' }}
                        </td>

                        <td class="p-3">
                            {{ $certificate->course->title ?? 'N/A' }}
                        </td>

                        <td class="p-3">
                            {{ $certificate->issued_date }}
                        </td>

                        <td class="p-3">
                            <span class="text-green-600 font-semibold">
                                {{ ucfirst($certificate->status) }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="p-4 text-center">
                            No certificates found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

    </div>

</div>

</x-layouts::app>