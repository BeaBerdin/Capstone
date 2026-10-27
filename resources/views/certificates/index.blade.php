<x-layouts::app :title="__('Certificates')">
@include('partials.soft3d')

<style>
    .pw-card {
        background: #ffffff;
        border: 1px solid #e7e9ef;
        border-radius: 20px;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.035);
    }
</style>

<div class="sad-page min-h-screen bg-[#f8f9fc]">

    <main class="px-4 py-7 sm:px-6 lg:px-8 lg:py-9">

        <div class="mx-auto max-w-[1500px]">

            {{-- =====================================================
                HEADER
            ====================================================== --}}

            {{-- Header card, as on My Certificates. --}}
            <section class="sad-banner overflow-hidden rounded-3xl border border-violet-100 bg-white shadow-sm">
                <div class="relative px-6 py-6 sm:px-7">
                    <div class="pointer-events-none absolute -right-16 -top-20 h-52 w-52 rounded-full bg-violet-100/70 blur-3xl"></div>

            <div class="relative flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">

                <div>
                    <p class="text-xs font-bold uppercase tracking-[.12em] text-violet-600">
                        Academic Records
                    </p>

                    <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-950">
                        Certificates
                    </h1>

                    <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">
                        Issue, correct or revoke student certificates.
                    </p>
                </div>

                <a
                    href="{{ route('certificates.create') }}"
                    class="sad-btn-primary inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-violet-600 px-5 text-sm font-semibold text-white transition hover:bg-violet-700"
                >
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 5v14M5 12h14"/>
                    </svg>
                    Add Certificate
                </a>

            </div>
                </div>
            </section>


            @if(session('success'))
                <div class="mt-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-medium text-emerald-700">
                    {{ session('success') }}
                </div>
            @endif


            {{-- =====================================================
                TABLE
            ====================================================== --}}

            <section class="sad-card pw-card mt-7 overflow-hidden">

                <div class="overflow-x-auto">

                    <table class="w-full min-w-[760px] text-left text-sm">

                        <thead class="border-b border-slate-100 bg-slate-50/80">
                            <tr class="text-xs font-bold uppercase tracking-[.08em] text-slate-500">
                                <th class="px-6 py-4">Certificate No.</th>
                                <th class="px-6 py-4">Student</th>
                                <th class="px-6 py-4">Course</th>
                                <th class="px-6 py-4">Issued Date</th>
                                <th class="px-6 py-4">Status</th>
                                <th class="px-6 py-4 text-right">Actions</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100">

                            @forelse($certificates as $certificate)

                                <tr class="transition hover:bg-slate-50/70">

                                    <td class="px-6 py-5">
                                        <span class="inline-flex items-center rounded-lg bg-violet-50 px-3 py-1.5 text-xs font-bold text-violet-700">
                                            {{ $certificate->certificate_number }}
                                        </span>
                                    </td>

                                    <td class="px-6 py-5">
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-violet-100 text-xs font-bold text-violet-700">
                                                {{ strtoupper(substr($certificate->student->name ?? 'S', 0, 1)) }}
                                            </div>
                                            <p class="text-sm font-semibold text-slate-800">
                                                {{ $certificate->student->name ?? 'N/A' }}
                                            </p>
                                        </div>
                                    </td>

                                    <td class="px-6 py-5 text-sm text-slate-600">
                                        {{ $certificate->course->title ?? 'N/A' }}
                                    </td>

                                    <td class="px-6 py-5 text-xs font-semibold text-slate-600">
                                        {{ \Carbon\Carbon::parse($certificate->issued_date)->format('M d, Y') }}
                                    </td>

                                    <td class="px-6 py-5">
                                        @if($certificate->status === 'issued')
                                            <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-700">
                                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                                Issued
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 rounded-full bg-rose-50 px-2.5 py-1 text-xs font-bold text-rose-700">
                                                <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span>
                                                {{ ucfirst($certificate->status) }}
                                            </span>
                                        @endif
                                    </td>

                                    <td class="px-6 py-5">
                                        <div class="flex items-center justify-end gap-2">

                                            <a
                                                href="{{ route('certificates.edit', $certificate) }}"
                                                class="inline-flex h-9 items-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-600 transition hover:border-violet-200 hover:text-violet-700"
                                            >
                                                Edit
                                            </a>

                                            <form
                                                action="{{ route('certificates.destroy', $certificate) }}"
                                                method="POST"
                                                onsubmit="return confirm('Delete certificate?')"
                                            >
                                                @csrf
                                                @method('DELETE')

                                                <button
                                                    type="submit"
                                                    class="inline-flex h-9 items-center rounded-lg border border-rose-100 bg-white px-3 text-xs font-semibold text-rose-600 transition hover:bg-rose-50"
                                                >
                                                    Delete
                                                </button>
                                            </form>

                                        </div>
                                    </td>

                                </tr>

                            @empty

                                <tr>
                                    <td colspan="6" class="px-6 py-16 text-center">

                                        <div class="sad-chip sad-violet mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-violet-50 text-violet-600">
                                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <circle cx="12" cy="9" r="5"/>
                                                <path d="m9 13.5-1.5 7.5 4.5-2.5 4.5 2.5-1.5-7.5"/>
                                            </svg>
                                        </div>

                                        <h3 class="mt-4 text-sm font-bold text-slate-800">
                                            No certificates found.
                                        </h3>

                                        <p class="mt-1 text-xs text-slate-500">
                                            Certificates appear here when students complete courses, or when you add one.
                                        </p>

                                    </td>
                                </tr>

                            @endforelse

                        </tbody>

                    </table>

                </div>

            </section>

        </div>

    </main>

</div>

</x-layouts::app>
