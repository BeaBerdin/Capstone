{{--
    Form card footer: primary submit button and a Cancel link.
    The icon slot goes before the button label.
--}}
@props([
    'submit',
    'cancelUrl' => null,
])

<div {{ $attributes->merge(['class' => 'flex flex-col gap-3 border-t border-slate-100 bg-slate-50/70 px-5 py-4 sm:flex-row sm:items-center sm:px-6']) }}>
    <button type="submit" class="sad-btn sad-btn-primary">
        {{ $icon ?? '' }}
        {{ $submit }}
    </button>

    @if ($cancelUrl)
        <a href="{{ $cancelUrl }}" class="sad-btn sad-btn-light border border-slate-200">
            Cancel
        </a>
    @endif

    {{ $slot }}
</div>
