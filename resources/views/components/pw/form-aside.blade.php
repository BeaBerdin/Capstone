{{--
    Form side panel (desktop artwork + guidelines card). The artwork is drawn
    inline and hidden below lg. The default slot adds cards under the tips.
    One per page (the svg ids are fixed).
--}}
@props([
    'title' => 'Guidelines',
    'tips' => [],
])

<aside {{ $attributes->merge(['class' => 'space-y-6 lg:sticky lg:top-24']) }}>

    <div class="hidden overflow-hidden rounded-[22px] border border-violet-100 bg-linear-to-br from-[#f3efff] to-[#e6defe] lg:block" aria-hidden="true">
        <svg viewBox="0 0 300 200" class="h-auto w-full">
            <defs>
                <linearGradient id="pwfScreen" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0" stop-color="#ede9fe" />
                    <stop offset="1" stop-color="#c4b5fd" />
                </linearGradient>
                <linearGradient id="pwfBase" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0" stop-color="#ddd6fe" />
                    <stop offset="1" stop-color="#a78bfa" />
                </linearGradient>
                <linearGradient id="pwfSheet" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0" stop-color="#ffffff" />
                    <stop offset="1" stop-color="#f5f3ff" />
                </linearGradient>
                <linearGradient id="pwfBookA" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0" stop-color="#a78bfa" />
                    <stop offset="1" stop-color="#7c3aed" />
                </linearGradient>
                <linearGradient id="pwfBookB" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0" stop-color="#fcd34d" />
                    <stop offset="1" stop-color="#f59e0b" />
                </linearGradient>
                <linearGradient id="pwfLeaf" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0" stop-color="#6ee7b7" />
                    <stop offset="1" stop-color="#059669" />
                </linearGradient>
                <filter id="pwfSoft" x="-20%" y="-20%" width="140%" height="160%">
                    <feDropShadow dx="0" dy="7" stdDeviation="6" flood-color="#4c1d95" flood-opacity="0.18" />
                </filter>
            </defs>

            <circle cx="160" cy="96" r="78" fill="#ffffff" opacity="0.4" />
            <rect x="20" y="168" width="260" height="20" rx="9" fill="#ffffff" opacity="0.7" />

            {{-- Laptop --}}
            <g filter="url(#pwfSoft)">
                <rect x="96" y="66" width="122" height="84" rx="10" fill="url(#pwfScreen)" />
                <rect x="104" y="74" width="106" height="68" rx="6" fill="#faf8ff" />
                <rect x="80" y="150" width="154" height="10" rx="5" fill="url(#pwfBase)" />
            </g>

            {{-- Sheet floating in front --}}
            <g filter="url(#pwfSoft)">
                <rect x="124" y="40" width="66" height="84" rx="9" fill="url(#pwfSheet)" transform="rotate(6 157 82)" />
                <g transform="rotate(6 157 82)">
                    <rect x="134" y="52" width="30" height="6" rx="3" fill="#a78bfa" />
                    <rect x="134" y="66" width="46" height="4" rx="2" fill="#ddd6fe" />
                    <rect x="134" y="76" width="40" height="4" rx="2" fill="#ddd6fe" />
                    <rect x="134" y="86" width="44" height="4" rx="2" fill="#ddd6fe" />
                    <circle cx="170" cy="108" r="9" fill="#10b981" />
                    <path d="M165.5 108 l3 3 6 -6" fill="none" stroke="#ffffff" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" />
                </g>
            </g>

            {{-- Books --}}
            <g filter="url(#pwfSoft)">
                <rect x="30" y="148" width="58" height="16" rx="5" fill="url(#pwfBookA)" />
                <rect x="36" y="132" width="50" height="16" rx="5" fill="url(#pwfBookB)" />
            </g>

            {{-- Plant --}}
            <g filter="url(#pwfSoft)">
                <path d="M252 132 C242 116 244 102 254 96 C258 110 258 122 252 132 Z" fill="url(#pwfLeaf)" />
                <path d="M258 132 C262 114 274 108 284 110 C278 122 270 130 258 132 Z" fill="url(#pwfLeaf)" />
                <path d="M244 132 L268 132 L263 166 L249 166 Z" fill="#ffffff" />
            </g>
        </svg>
    </div>

    @if ($tips)
        <div class="sad-card p-5">
            <h2 class="flex items-center gap-2 text-sm font-bold text-slate-900">
                <span class="sad-section-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><path d="M14 2v6h6"></path></svg>
                </span>
                {{ $title }}
            </h2>

            <ul class="mt-4 space-y-3 text-[13px] leading-5 text-slate-600">
                @foreach ($tips as $tip)
                    <li class="flex gap-2.5">
                        <svg class="mt-0.5 h-4 w-4 shrink-0 text-violet-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path d="m5 12 4 4L19 6"></path></svg>
                        <span>{{ $tip }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    {{ $slot }}
</aside>
