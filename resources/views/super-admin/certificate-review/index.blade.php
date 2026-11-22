<x-layouts::app :title="__('Certificates to Review')">

    <div class="space-y-6 px-5 py-7 sm:px-6 lg:px-8 lg:py-9">

        <section class="overflow-hidden rounded-3xl border border-violet-100 bg-white shadow-sm">
            <div class="px-6 py-6 sm:px-7">
                <p class="text-sm font-medium text-violet-600">EDP Review</p>
                <h1 class="mt-1 text-3xl font-bold tracking-tight text-slate-900">Certificates to Review</h1>
                <p class="mt-2 max-w-3xl text-sm text-slate-500">
                    Issued certificates whose course still has activities or assignments without a passing final
                    teacher grade for that student (passing = 75% of the maximum score). Nothing is changed
                    automatically. To revoke one, open Certificate Management.
                </p>
            </div>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
            @if ($rows->isEmpty())
                <p class="px-6 py-14 text-center text-sm text-slate-500" data-test="no-certificates-to-review">
                    Every issued certificate has passing final grades on all activities.
                </p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[760px] text-left text-sm">
                        <thead>
                            <tr class="border-b border-slate-100 bg-slate-50/70 text-xs text-slate-500">
                                <th class="px-5 py-3 font-medium">Certificate</th>
                                <th class="px-3 py-3 font-medium">Student</th>
                                <th class="px-3 py-3 font-medium">Course</th>
                                <th class="px-3 py-3 font-medium">Still missing</th>
                                <th class="px-5 py-3 text-right font-medium"><span class="sr-only">Open</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($rows as $row)
                                @php $certificate = $row['certificate']; @endphp
                                <tr class="align-top" data-test="certificate-review-row">
                                    <td class="px-5 py-3">
                                        <p class="font-semibold text-slate-800">{{ $certificate->certificate_number }}</p>
                                        <p class="text-xs text-slate-500">Issued {{ \Illuminate\Support\Carbon::parse($certificate->issued_date)->format('M d, Y') }}</p>
                                    </td>
                                    <td class="px-3 py-3">
                                        <p class="text-slate-800">{{ $certificate->student->name }}</p>
                                        <p class="text-xs text-slate-500">{{ $certificate->student->email }}</p>
                                    </td>
                                    <td class="px-3 py-3 text-slate-700">{{ $certificate->course->title }}</td>
                                    <td class="px-3 py-3">
                                        <ul class="space-y-0.5 text-xs text-amber-700">
                                            @foreach ($row['blockers'] as $blocker)
                                                <li>{{ $blocker }}</li>
                                            @endforeach
                                        </ul>
                                    </td>
                                    <td class="px-5 py-3 text-right">
                                        <a href="{{ route('certificates.edit', $certificate) }}" class="inline-flex items-center rounded-lg border border-violet-200 px-3 py-1.5 text-xs font-semibold text-violet-700 hover:bg-violet-50">
                                            Open
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($rows->hasPages())
                    <div class="border-t border-slate-100 px-5 py-3">{{ $rows->links() }}</div>
                @endif
            @endif
        </section>

    </div>

</x-layouts::app>
