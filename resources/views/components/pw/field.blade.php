{{--
    One form field: label (with a red * when required), the control in the
    slot (give it class="sad-field"), then the field's validation error, or
    the hint when there is no error.
    - error: the validation key; defaults to `for`.
--}}
@props([
    'label',
    'for' => null,
    'required' => false,
    'hint' => null,
    'error' => null,
])

@php
    $errorKey = $error ?? $for;
@endphp

<div {{ $attributes }}>
    <label @if ($for) for="{{ $for }}" @endif class="block text-sm font-semibold text-slate-700">
        {{ $label }}
        @if ($required)
            <span class="text-rose-500">*</span>
        @endif
    </label>

    {{ $slot }}

    @if ($errorKey && $errors->has($errorKey))
        <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $errors->first($errorKey) }}</p>
    @elseif ($hint)
        <p class="mt-1.5 text-xs text-slate-500">{{ $hint }}</p>
    @endif
</div>
