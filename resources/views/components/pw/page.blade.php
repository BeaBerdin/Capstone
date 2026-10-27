{{--
    Standard wrapper for authenticated pages: the shared page padding, a
    width cap and 24px between sections. Content stays left-aligned, so the
    page title starts at the same position on every page.
    Widths: wide (dashboards, tables, grids), form (create/edit forms),
    reading (detail and reading pages).
--}}
@props([
    'width' => 'wide',
])

@php
    $maxWidth = match ($width) {
        'form' => 'max-w-4xl',
        'reading' => 'max-w-5xl',
        default => 'max-w-[1600px]',
    };
@endphp

<main {{ $attributes->class(['px-5 py-7 sm:px-6 lg:px-8 lg:py-9']) }}>
    <div class="w-full {{ $maxWidth }} space-y-6">
        {{ $slot }}
    </div>
</main>
