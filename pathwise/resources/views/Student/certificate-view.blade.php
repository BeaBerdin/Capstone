<x-layouts::app :title="'Certificate of Completion'">
    <style>
        .certificate-title,
        .certificate-name {
            font-family: 'Cinzel', serif;
        }
        /* Subtle decorative corner flourishes */
        .cert-corner {
            position: absolute;
            width: 48px;
            height: 48px;
            border-color: rgb(126 34 206 / 0.45);
        }
    </style>

<div class="max-w-[1600px] mx-auto px-6 space-y-6">

    <div class="flex justify-between">
        <x-back-button :href="route('student.certificates')" label="Back to Certificates" />

        <a href="{{ route('student.certificate.download', $certificate) }}"
           class="inline-flex items-center gap-2 rounded-lg bg-linear-to-r from-purple-500 to-indigo-600 px-5 py-2 text-white shadow-lg shadow-purple-500/20 transition hover:opacity-90">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v12m0 0 4-4m-4 4-4-4M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2" />
            </svg>
            Download PDF
        </a>
    </div>

    <div class="flex justify-center">

        <div class="relative w-full max-w-1400 overflow-hidden rounded-3xl border-10 border-purple-700 bg-white shadow-2xl">

            <!-- A4 Landscape Ratio -->
            <div class="relative aspect-297/210 w-full p-10 text-center text-black lg:p-16">

                <!-- Soft corner glows for a premium feel -->
                <div class="pointer-events-none absolute -left-24 -top-24 h-64 w-64 rounded-full bg-purple-300/30 blur-3xl"></div>
                <div class="pointer-events-none absolute -bottom-24 -right-24 h-64 w-64 rounded-full bg-indigo-300/30 blur-3xl"></div>

                <!-- Inner Border -->
                <div class="pointer-events-none absolute inset-4 rounded-2xl border-2 border-purple-200 lg:inset-6"></div>

                <!-- Decorative corner accents -->
                <div class="cert-corner left-8 top-8 border-l-2 border-t-2 rounded-tl-lg"></div>
                <div class="cert-corner right-8 top-8 border-r-2 border-t-2 rounded-tr-lg"></div>
                <div class="cert-corner bottom-8 left-8 border-b-2 border-l-2 rounded-bl-lg"></div>
                <div class="cert-corner bottom-8 right-8 border-b-2 border-r-2 rounded-br-lg"></div>

                <div class="relative z-10 flex h-full flex-col justify-between">

                    <!-- Header -->
                    <div>

                        <div class="mb-5 flex justify-center">
                            <img
                                src="{{ asset('images/pathwise-icon.png') }}"
                                alt="PathWise"
                                class="h-20 object-contain">
                        </div>

                        <h1 class="certificate-title text-5xl font-extrabold tracking-[0.18em] text-purple-800 lg:text-7xl">
                            CERTIFICATE
                        </h1>

                        <h2 class="mt-2 text-2xl font-bold tracking-wide text-purple-600 lg:text-4xl">
                            OF COMPLETION
                        </h2>

                        <div class="mt-4 text-3xl text-purple-600">
                            ❦
                        </div>

                    </div>

                    <!-- Body -->
                    <div>

                        <p class="text-lg text-gray-600">
                            This certificate is proudly awarded to
                        </p>

                        <h2 class="certificate-name mt-4 inline-block border-b-2 border-purple-300 px-12 pb-2 text-4xl font-bold text-purple-900 lg:text-5xl">
                            {{ $certificate->student->name }}
                        </h2>

                        <p class="mt-8 text-gray-600">
                            for successfully completing the course
                        </p>

                        <h3 class="mt-3 text-3xl font-bold text-purple-800 lg:text-4xl">
                            {{ $certificate->course->title }}
                        </h3>

                        <div class="mx-auto mt-8 max-w-4xl">
                            <p class="leading-relaxed text-gray-600">
                                This certificate is proudly awarded in recognition of the
                                learner&rsquo;s dedication, commitment, and successful completion
                                of all course requirements, assessments, and learning
                                activities under the academic standards of the PathWise
                                AI-Powered E-Learning Platform.
                            </p>
                        </div>

                    </div>

                    <!-- Footer -->
                    <div>

                        <div class="mx-auto grid max-w-3xl grid-cols-2 gap-8">

                            <div>
                                <p class="text-xs uppercase tracking-wide text-gray-500">
                                    Certificate Number
                                </p>

                                <p class="mt-1 text-xl font-bold text-purple-900">
                                    {{ $certificate->certificate_number }}
                                </p>

                                <p class="mt-2 text-xs text-gray-400">
                                    Verify this certificate at: pathwise.test
                                </p>
                            </div>

                            <div>
                                <p class="text-xs uppercase tracking-wide text-gray-500">
                                    Date Issued
                                </p>

                                <p class="mt-1 text-xl font-bold text-purple-900">
                                    {{ \Carbon\Carbon::parse($certificate->issued_date)->format('F d, Y') }}
                                </p>
                            </div>

                        </div>

                        <div class="mt-10">
                            <div class="mx-auto w-72 border-t-2 border-purple-700"></div>

                            <p class="mt-3 text-lg font-bold tracking-wide text-purple-900">
                                PATHWISE
                            </p>

                            <p class="text-sm text-gray-500">
                                AI-Powered E-Learning Platform
                            </p>
                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>

</x-layouts::app>