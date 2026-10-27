{{--
    One titled section inside a form card. Sections after the first get a
    divider above them. Put the section's svg in the icon slot.
--}}
@props([
    'title',
    'first' => false,
])

<section {{ $attributes->class(['space-y-5 p-5 sm:p-6', 'border-t border-slate-100' => ! $first]) }}>
    <h2 class="flex items-center gap-3 text-base font-bold text-slate-900">
        @isset($icon)
            <span class="sad-section-icon" aria-hidden="true">{{ $icon }}</span>
        @endisset
        {{ $title }}
    </h2>

    {{ $slot }}
</section>
