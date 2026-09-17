<x-layouts::app :title="'My Certificates'">

<div class="min-h-screen bg-slate-50/70">
    <main class="px-4 py-6 sm:px-5 lg:px-6 lg:py-7">
        <div class="w-full max-w-none space-y-5">

            <section class="overflow-hidden rounded-3xl border border-violet-100 bg-white shadow-sm">
                <div class="relative px-6 py-6 sm:px-7">
                    <div class="pointer-events-none absolute -right-16 -top-20 h-52 w-52 rounded-full bg-violet-100/70 blur-3xl"></div>

                    <div class="relative flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <div class="inline-flex items-center gap-2 rounded-full bg-violet-50 px-3 py-1.5 text-[11px] font-bold uppercase tracking-[0.14em] text-violet-700">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="8" r="5"></circle>
                                    <path d="M8.5 12.5 7 21l5-2 5 2-1.5-8.5"></path>
                                </svg>
                                Student Records
                            </div>

                            <h1 class="mt-3 text-3xl font-bold tracking-tight text-slate-950">
                                My Certificates
                            </h1>

                            <p class="mt-2 text-sm text-slate-500">
                                View and download certificates earned from your completed courses.
                            </p>
                        </div>

                        @if($certificates->isNotEmpty())
                            <div class="inline-flex w-fit items-center gap-3 rounded-2xl border border-violet-100 bg-violet-50/70 px-4 py-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-white text-violet-600 shadow-sm">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="8" r="5"></circle>
                                        <path d="M8.5 12.5 7 21l5-2 5 2-1.5-8.5"></path>
                                    </svg>
                                </div>

                                <div>
                                    <p class="text-[10px] font-bold uppercase tracking-[0.1em] text-violet-500">
                                        Certificates Earned
                                    </p>
                                    <p class="mt-0.5 text-xl font-bold text-violet-900">
                                        {{ $certificates->count() }}
                                    </p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </section>

            <section class="grid grid-cols-1 gap-4">
                @forelse($certificates as $certificate)

                    <article class="group overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-violet-200 hover:shadow-md">
                        <div class="grid grid-cols-1 gap-4 p-5 sm:grid-cols-[56px_minmax(0,1fr)_auto] sm:items-center">

                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-violet-50 text-violet-600">
                                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <circle cx="12" cy="8" r="5"></circle>
                                    <path d="M8.5 12.5 7 21l5-2 5 2-1.5-8.5"></path>
                                </svg>
                            </div>

                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h2 class="truncate text-lg font-bold text-slate-900">
                                        {{ $certificate->course->title }}
                                    </h2>

                                    <span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-emerald-700">
                                        {{ ucfirst($certificate->status) }}
                                    </span>
                                </div>

                                <p class="mt-1 text-xs font-medium text-emerald-600">
                                    Certificate earned
                                </p>

                                <div class="mt-3 flex flex-wrap gap-x-8 gap-y-2">
                                    <div>
                                        <p class="text-[9px] font-bold uppercase tracking-[0.08em] text-slate-400">
                                            Certificate Number
                                        </p>
                                        <p class="mt-0.5 text-sm font-semibold text-slate-700">
                                            {{ $certificate->certificate_number }}
                                        </p>
                                    </div>

                                    <div>
                                        <p class="text-[9px] font-bold uppercase tracking-[0.08em] text-slate-400">
                                            Date Issued
                                        </p>
                                        <p class="mt-0.5 text-sm font-semibold text-slate-700">
                                            {{ \Carbon\Carbon::parse($certificate->issued_date)->format('F d, Y') }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="sm:justify-self-end">
                                <a
                                    href="{{ route('student.certificate.view', $certificate) }}"
                                    class="inline-flex h-10 w-full items-center justify-center gap-2 rounded-xl bg-violet-600 px-4 text-xs font-semibold text-white shadow-sm transition hover:bg-violet-700 sm:w-auto"
                                >
                                    View Certificate
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="m9 18 6-6-6-6"></path>
                                    </svg>
                                </a>
                            </div>
                        </div>
                    </article>

                @empty

                    <div class="rounded-3xl border border-dashed border-slate-300 bg-white px-6 py-14 text-center shadow-sm">
                        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-violet-50 text-violet-600">
                            <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <circle cx="12" cy="8" r="5"></circle>
                                <path d="M8.5 12.5 7 21l5-2 5 2-1.5-8.5"></path>
                            </svg>
                        </div>

                        <h2 class="mt-5 text-xl font-bold text-slate-900">
                            No Certificates Yet
                        </h2>

                        <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-slate-500">
                            Complete an eligible course to earn a certificate and it will appear here.
                        </p>

                        <a
                            href="{{ route('student.my-courses') }}"
                            class="mt-6 inline-flex h-10 items-center justify-center gap-2 rounded-xl bg-violet-600 px-5 text-xs font-semibold text-white transition hover:bg-violet-700"
                        >
                            Go to My Courses
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="m9 18 6-6-6-6"></path>
                            </svg>
                        </a>
                    </div>

                @endforelse
            </section>

        </div>
    </main>
</div>

</x-layouts::app>
