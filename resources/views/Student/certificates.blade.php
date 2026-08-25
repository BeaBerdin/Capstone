<x-layouts::app :title="'My Certificates'">

<div class="space-y-6">

    <div>
        <h1 class="text-3xl font-bold text-black">
            My Certificates
        </h1>
        <p class="text-sm text-gray-400">
            Certificates earned from completed courses.
        </p>
    </div>

    @forelse($certificates as $certificate)

        <div class="rounded-2xl border border-neutral-700 bg-neutral-900 p-6 shadow-lg shadow-purple-950/10">

            <div class="flex justify-between items-start">

                <div>
                    <h2 class="text-xl font-bold text-white">
                        {{ $certificate->course->title }}
                    </h2>

                    <p class="text-sm text-gray-500 mt-2">
                        Certificate Number:
                        <span class="font-semibold text-gray-300">{{ $certificate->certificate_number }}</span>
                    </p>

                    <p class="text-sm text-gray-500">
                        Issued:
                        <span class="text-gray-300">{{ \Carbon\Carbon::parse($certificate->issued_date)->format('F d, Y') }}</span>
                    </p>

                    <p class="text-sm text-green-400 mt-2">
                        🏆 Certificate Earned
                    </p>

                    <div class="mt-4">
                        <a href="{{ route('student.certificate.view', $certificate) }}"
                           class="inline-block rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                            View Certificate
                        </a>
                    </div>
                </div>

                <div>
                    <span class="rounded-full bg-green-500/15 px-3 py-1 text-xs font-semibold text-green-400">
                        {{ ucfirst($certificate->status) }}
                    </span>
                </div>

            </div>

        </div>

    @empty

        <div class="rounded-2xl border border-neutral-700 bg-neutral-900 p-8 text-center">

            <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-purple-600/20 text-2xl mx-auto">
                🏆
            </div>

            <h2 class="text-xl font-semibold text-white">
                No Certificates Yet
            </h2>

            <p class="text-gray-400 mt-2">
                Complete courses to earn certificates.
            </p>

            <a href="{{ route('student.my-courses') }}"
               class="mt-5 inline-block rounded-xl bg-purple-600 px-5 py-2 text-sm font-semibold text-white hover:bg-purple-700">
                Go to My Courses →
            </a>

        </div>

    @endforelse

</div>

</x-layouts::app>