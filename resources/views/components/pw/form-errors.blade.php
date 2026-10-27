{{-- Validation summary at the top of a form card. --}}
@if ($errors->any())
    <div {{ $attributes->merge(['class' => 'mx-5 mt-5 rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 sm:mx-6']) }} role="alert">
        <p class="text-sm font-bold text-rose-700">Please correct the following:</p>
        <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-rose-600">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
