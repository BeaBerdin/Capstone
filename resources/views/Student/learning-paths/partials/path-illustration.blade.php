{{--
    Soft-3D learning illustration for the left panel of a learning path card:
    a stack of books with a graduation cap resting on top, a floating book,
    a lightbulb tile and a few sparkles. Drawn inline (no image files), in the
    same soft-3D style as the student dashboard banner.

    $uid: a value unique on the page, so each card's gradient ids differ.
--}}

@php($u = 'lp'.$uid)

<svg viewBox="0 -36 160 236" preserveAspectRatio="xMidYMid meet" aria-hidden="true" class="h-full w-full overflow-visible">
    <defs>
        <radialGradient id="{{ $u }}Glow" cx="0.5" cy="0.55" r="0.5">
            <stop offset="0" stop-color="#ffffff" stop-opacity="0.95" />
            <stop offset="1" stop-color="#ffffff" stop-opacity="0" />
        </radialGradient>

        {{-- Books: front (spine), top cover and page block --}}
        <linearGradient id="{{ $u }}BookA" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0" stop-color="#8b5cf6" />
            <stop offset="1" stop-color="#6d28d9" />
        </linearGradient>
        <linearGradient id="{{ $u }}BookB" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0" stop-color="#818cf8" />
            <stop offset="1" stop-color="#4f46e5" />
        </linearGradient>
        <linearGradient id="{{ $u }}BookC" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0" stop-color="#d8b4fe" />
            <stop offset="1" stop-color="#a855f7" />
        </linearGradient>
        <linearGradient id="{{ $u }}Pages" x1="0" y1="0" x2="1" y2="0">
            <stop offset="0" stop-color="#ffffff" />
            <stop offset="1" stop-color="#e9e5fb" />
        </linearGradient>

        {{-- Graduation cap --}}
        <linearGradient id="{{ $u }}Board" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0" stop-color="#6d28d9" />
            <stop offset="0.55" stop-color="#4c1d95" />
            <stop offset="1" stop-color="#312e81" />
        </linearGradient>
        <linearGradient id="{{ $u }}Skull" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0" stop-color="#4338ca" />
            <stop offset="1" stop-color="#312e81" />
        </linearGradient>
        <linearGradient id="{{ $u }}Gold" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0" stop-color="#fde68a" />
            <stop offset="1" stop-color="#f59e0b" />
        </linearGradient>

        {{-- Floating tile --}}
        <linearGradient id="{{ $u }}Tile" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0" stop-color="#ffffff" />
            <stop offset="1" stop-color="#ede9fe" />
        </linearGradient>

        <filter id="{{ $u }}Soft" x="-30%" y="-30%" width="160%" height="170%">
            <feDropShadow dx="0" dy="7" stdDeviation="5" flood-color="#4c1d95" flood-opacity="0.22" />
        </filter>
        <filter id="{{ $u }}Lift" x="-40%" y="-40%" width="180%" height="190%">
            <feDropShadow dx="0" dy="5" stdDeviation="3.5" flood-color="#4c1d95" flood-opacity="0.2" />
        </filter>
    </defs>

    {{-- Soft light behind the scene, and the shadow it rests on --}}
    <circle cx="80" cy="96" r="84" fill="url(#{{ $u }}Glow)" />
    <ellipse cx="80" cy="186" rx="58" ry="7" fill="#5b21b6" opacity="0.14" />
    <ellipse cx="80" cy="186" rx="38" ry="4" fill="#5b21b6" opacity="0.12" />

    {{-- Book stack --}}
    <g filter="url(#{{ $u }}Soft)">
        {{-- Bottom book --}}
        <polygon points="24,160 116,160 132,150 40,150" fill="#c4b5fd" />
        <polygon points="116,160 132,150 132,170 116,180" fill="url(#{{ $u }}Pages)" />
        <path d="M118 165 L131 156.5 M118 170 L131 161.5 M118 175 L131 166.5" stroke="#d6cdf7" stroke-width="1" />
        <rect x="24" y="160" width="92" height="20" rx="2.5" fill="url(#{{ $u }}BookA)" />
        <rect x="24" y="164" width="92" height="2.5" fill="#ffffff" opacity="0.28" />
        <rect x="24" y="173" width="92" height="2.5" fill="#ffffff" opacity="0.28" />
        <rect x="58" y="167.5" width="24" height="4" rx="2" fill="#fde68a" opacity="0.9" />

        {{-- Middle book --}}
        <polygon points="32,142 110,142 124,133 46,133" fill="#c7d2fe" />
        <polygon points="110,142 124,133 124,149 110,158" fill="url(#{{ $u }}Pages)" />
        <path d="M112 147 L123 139.5 M112 152 L123 144.5" stroke="#d6cdf7" stroke-width="1" />
        <rect x="32" y="142" width="78" height="17" rx="2.5" fill="url(#{{ $u }}BookB)" />
        <rect x="32" y="146" width="78" height="2" fill="#ffffff" opacity="0.3" />
        <rect x="32" y="153" width="78" height="2" fill="#ffffff" opacity="0.3" />

        {{-- Top book --}}
        <polygon points="40,126 104,126 116,118 52,118" fill="#f3e8ff" />
        <polygon points="104,126 116,118 116,132 104,140" fill="url(#{{ $u }}Pages)" />
        <path d="M106 131 L115 125 M106 135.5 L115 129.5" stroke="#d6cdf7" stroke-width="1" />
        <rect x="40" y="126" width="64" height="15" rx="2.5" fill="url(#{{ $u }}BookC)" />
        <rect x="40" y="130" width="64" height="2" fill="#ffffff" opacity="0.35" />
    </g>

    {{-- Graduation cap resting on the stack --}}
    <g class="lp-float-slow" filter="url(#{{ $u }}Soft)">
        {{-- Skull cap --}}
        <path d="M56 98 L56 113 Q80 126 104 113 L104 98 L80 108 Z" fill="url(#{{ $u }}Skull)" />
        <path d="M56 104 Q80 116 104 104" fill="none" stroke="#6366f1" stroke-width="1.5" opacity="0.6" />

        {{-- Board: thickness, then the top face and its highlight --}}
        <polygon points="26,88 80,108 134,88 134,92 80,112 26,92" fill="#2e1065" />
        <polygon points="26,88 80,68 134,88 80,108" fill="url(#{{ $u }}Board)" />
        <polygon points="26,88 80,68 134,88 80,70.5" fill="#a78bfa" opacity="0.35" />

        {{-- Button, cord and tassel --}}
        <circle cx="80" cy="88" r="3.6" fill="url(#{{ $u }}Gold)" />
        <path d="M80 88 L122 95 L122 116" fill="none" stroke="#fbbf24" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" />
        <path d="M118.5 115 h7 l1.5 10 h-10 z" fill="url(#{{ $u }}Gold)" />
        <rect x="119" y="113" width="6" height="4" rx="1.5" fill="#d97706" />
    </g>

    <g transform="translate(0 -26)">

    {{-- Floating book (top left) --}}
    <g class="lp-float" filter="url(#{{ $u }}Lift)">
        <g transform="rotate(-16 33 44)">
            <path d="M14 36 Q24 31 33 36 L33 56 Q24 51 14 56 Z" fill="url(#{{ $u }}Pages)" />
            <path d="M52 36 Q42 31 33 36 L33 56 Q42 51 52 56 Z" fill="url(#{{ $u }}Pages)" />
            <path d="M12 56 Q23 51 33 57 Q43 51 54 56 L54 59 Q43 54 33 60 Q23 54 12 59 Z" fill="url(#{{ $u }}BookA)" />
            <path d="M18 41 Q24 38 30 41 M18 46 Q24 43 30 46 M36 41 Q42 38 48 41 M36 46 Q42 43 48 46" fill="none" stroke="#c4b5fd" stroke-width="1.4" stroke-linecap="round" />
        </g>
    </g>

    {{-- Floating lightbulb tile (top right) --}}
    <g class="lp-float-alt" filter="url(#{{ $u }}Lift)">
        <rect x="110" y="22" width="34" height="34" rx="10" fill="url(#{{ $u }}Tile)" />
        <rect x="110" y="22" width="34" height="2" rx="1" fill="#ffffff" />
        <path d="M127 30 a8 8 0 0 0 -4.8 14.4 V48 h9.6 v-3.6 A8 8 0 0 0 127 30 Z" fill="url(#{{ $u }}Gold)" />
        <rect x="122.6" y="48.5" width="8.8" height="2.6" rx="1.3" fill="#7c3aed" />
        <path d="M124.5 38 q2.5 -3 5 0" fill="none" stroke="#ffffff" stroke-width="1.4" stroke-linecap="round" opacity="0.85" />
    </g>

    {{-- Sparkles and small light dots --}}
    <path d="M70 18 C71 24 72 25 78 26 C72 27 71 28 70 34 C69 28 68 27 62 26 C68 25 69 24 70 18 Z" fill="#8b5cf6" opacity="0.85" />
    <path d="M147 76 C147.6 79.5 148.5 80.4 152 81 C148.5 81.6 147.6 82.5 147 86 C146.4 82.5 145.5 81.6 142 81 C145.5 80.4 146.4 79.5 147 76 Z" fill="#a78bfa" />
    <path d="M14 104 C14.6 107.5 15.5 108.4 19 109 C15.5 109.6 14.6 110.5 14 114 C13.4 110.5 12.5 109.6 9 109 C12.5 108.4 13.4 107.5 14 104 Z" fill="#c084fc" />
    <path d="M98 30 l2.4 4.8 5.3 .8 -3.8 3.7 .9 5.3 -4.8 -2.5 -4.8 2.5 .9 -5.3 -3.8 -3.7 5.3 -.8z" fill="url(#{{ $u }}Gold)" opacity="0.95" />
    <circle cx="144" cy="128" r="3" fill="#ddd6fe" />
    <circle cx="22" cy="80" r="2.5" fill="#ddd6fe" />
    <circle cx="52" cy="20" r="2" fill="#c4b5fd" />

    </g>
</svg>
