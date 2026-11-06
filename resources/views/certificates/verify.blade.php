<x-layouts::auth :title="'Verify a Certificate'">
    <div class="flex flex-col gap-6">

        <div class="text-center">
            <h1 class="text-xl font-bold text-slate-900">Verify a Certificate</h1>
            <p class="mt-1 text-sm text-slate-500">
                Enter the certificate number printed on a PATHWISE certificate.
            </p>
        </div>

        <form method="GET" action="{{ route('certificates.verify') }}" class="flex flex-col gap-3">
            <label for="number" class="text-sm font-semibold text-slate-700">
                Certificate number
            </label>

            <input
                id="number"
                name="number"
                type="text"
                maxlength="50"
                required
                value="{{ $number }}"
                placeholder="PATH-2026-00001"
                autocomplete="off"
                class="h-11 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm uppercase text-slate-800
                       outline-none focus:border-violet-300 focus:ring-4 focus:ring-violet-100"
            >

            @error('number')
                <p class="text-xs text-rose-600">{{ $message }}</p>
            @enderror

            <button
                type="submit"
                class="pw-btn-primary inline-flex h-11 items-center justify-center rounded-xl text-sm font-semibold
                       text-white transition"
            >
                Verify
            </button>
        </form>

        @if($result)
            @if(! $result['found'])
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-center" data-verification="not-found">
                    <p class="text-sm font-bold text-slate-800">No certificate found</p>
                    <p class="mt-1 text-xs text-slate-500">
                        No PATHWISE certificate has the number {{ $result['number'] }}. Check the number and try again.
                    </p>
                </div>
            @else
                <div
                    class="rounded-2xl border p-4 {{ $result['valid'] ? 'border-emerald-200 bg-emerald-50' : 'border-rose-200 bg-rose-50' }}"
                    data-verification="{{ $result['valid'] ? 'valid' : 'revoked' }}"
                >
                    <p class="text-sm font-bold {{ $result['valid'] ? 'text-emerald-800' : 'text-rose-800' }}">
                        {{ $result['valid'] ? 'Valid certificate' : 'This certificate has been revoked' }}
                    </p>

                    <dl class="mt-3 space-y-2 text-sm">
                        <div class="flex justify-between gap-4">
                            <dt class="text-slate-500">Certificate number</dt>
                            <dd class="text-right font-semibold text-slate-800">{{ $result['number'] }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-slate-500">Student</dt>
                            <dd class="text-right font-semibold text-slate-800">{{ $result['student'] }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-slate-500">Course</dt>
                            <dd class="text-right font-semibold text-slate-800">{{ $result['course'] }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-slate-500">Issue date</dt>
                            <dd class="text-right font-semibold text-slate-800">{{ $result['issued'] }}</dd>
                        </div>
                    </dl>
                </div>
            @endif
        @endif

    </div>
</x-layouts::auth>
