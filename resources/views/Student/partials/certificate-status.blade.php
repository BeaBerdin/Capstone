{{--
    Certificate button for a student (App\Support\StudentCertificateStatus).
    $status: ['state' => issued|pending|none, 'certificate' => ?Certificate, 'reason' => ?string]
    $size: 'sm' (dashboard), 'md' (cards, default) or 'lg' (course pages).
--}}
@php
    $size = $size ?? 'md';
    $sizeClasses = match ($size) {
        'sm' => 'h-9 px-3.5 text-xs',
        'lg' => 'h-11 w-full px-4 text-sm',
        default => 'h-10 px-3 text-xs',
    };
@endphp

@if ($status['state'] === \App\Support\StudentCertificateStatus::ISSUED)
    <a href="{{ route('student.certificate.view', $status['certificate']) }}"
       class="inline-flex {{ $sizeClasses }} items-center justify-center gap-1.5 rounded-xl bg-emerald-600 font-semibold text-white transition hover:bg-emerald-700"
       data-certificate-link data-test="certificate-issued">
        View Certificate
    </a>
@elseif ($status['state'] === \App\Support\StudentCertificateStatus::PENDING)
    <span aria-disabled="true"
          title="{{ $status['reason'] }}"
          class="inline-flex {{ $sizeClasses }} cursor-not-allowed items-center justify-center rounded-xl bg-slate-100 text-center font-semibold text-slate-500"
          data-test="certificate-pending">
        Certificate pending
        @if ($status['reason'])
            <span class="sr-only">: {{ $status['reason'] }}</span>
        @endif
    </span>
@endif
