@props([
    'rating' => null,
    'size' => 'h-4 w-4',
])

{{-- Five stars for a rating from 0 to 5, rounded to the nearest whole star. --}}
@php
    $filled = $rating === null ? 0 : (int) round((float) $rating);
    $label = $rating === null
        ? 'No rating yet'
        : number_format((float) $rating, 1).' out of 5 stars';
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-0.5']) }} role="img" aria-label="{{ $label }}">
    @for ($star = 1; $star <= 5; $star++)
        <svg
            class="{{ $size }} {{ $star <= $filled ? 'text-amber-400' : 'text-slate-300' }}"
            viewBox="0 0 20 20"
            fill="currentColor"
            aria-hidden="true"
        >
            <path d="M9.05 2.93c.3-.92 1.6-.92 1.9 0l1.36 4.18a1 1 0 0 0 .95.69h4.4c.97 0 1.37 1.24.59 1.81l-3.56 2.59a1 1 0 0 0-.36 1.12l1.36 4.18c.3.92-.75 1.69-1.54 1.12l-3.56-2.59a1 1 0 0 0-1.18 0l-3.56 2.59c-.79.57-1.84-.2-1.54-1.12l1.36-4.18a1 1 0 0 0-.36-1.12L1.75 9.6c-.78-.57-.38-1.81.59-1.81h4.4a1 1 0 0 0 .95-.69l1.36-4.18Z" />
        </svg>
    @endfor
</span>
