{{--
    Course cover, shared by every role.

    Always shows the cover stored in courses.thumbnail for that course, in a
    16:9 frame, and only falls back to the PathWise placeholder when the
    course has no cover (or the image cannot be loaded). The image itself is
    never replaced, blurred or duplicated.

    <x-pw.course-cover :course="$course" />                    cover that fills its card (16:9)
    <x-pw.course-cover :course="$course" framed />             the same, with rounded corners and a subtle edge
    <x-pw.course-cover :course="$course" variant="thumb" />    small cover for rows
    <x-pw.course-cover :thumbnail="$path" :title="$title" />   when only the path is known

    Anything placed inside the tag (badges, price) is laid over the cover.
--}}

@props([
    'course' => null,
    'thumbnail' => null,
    'title' => null,
    'variant' => 'card',
    'framed' => false,
])

@php
    $coverUrl = \App\Models\Course::thumbnailUrlFor($thumbnail ?? $course?->thumbnail);
    $coverAlt = $title ?? $course?->title ?? 'Course';
    $isThumb = $variant === 'thumb';
@endphp

<div {{ $attributes->class([
    'relative overflow-hidden bg-slate-100',
    'aspect-video w-full' => ! $isThumb,
    'rounded-xl ring-1 ring-slate-900/5 shadow-sm' => $framed && ! $isThumb,
    'h-9 w-16 shrink-0 rounded-lg ring-1 ring-slate-200/70' => $isThumb,
]) }} @if($isThumb) title="{{ $coverAlt }}" @endif>

    {{-- Placeholder: shown when there is no cover. A framed cover keeps it
         hidden (no gradient behind the artwork) unless the image fails. --}}
    <div class="absolute inset-0 flex items-center justify-center bg-linear-to-br from-violet-500 via-indigo-500 to-blue-500" aria-hidden="true" data-cover-placeholder @if($framed && $coverUrl) hidden @endif>
        <svg
            class="{{ $isThumb ? 'h-4 w-4' : 'h-12 w-12' }} text-white/75"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="1.5"
        >
            <path d="M4 19.5A2.5 2.5 0 016.5 17H20"></path>
            <path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"></path>
        </svg>
    </div>

    @if($coverUrl)
        <img
            src="{{ $coverUrl }}"
            alt="{{ $coverAlt }}"
            loading="lazy"
            onerror="const placeholder = this.parentElement.querySelector('[data-cover-placeholder]'); if (placeholder) { placeholder.hidden = false; } this.remove()"
            class="absolute inset-0 h-full w-full object-cover object-center"
        >
    @endif

    {{ $slot }}

</div>
