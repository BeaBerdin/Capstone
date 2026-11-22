{{--
    Previous / next navigation for students (App\Support\LessonNavigation).
    $nav: previous, next, courseUrl, isLast, certificate.
    Stacked full-width buttons on phones; titles are truncated, never wrapped.
--}}
@php
    $btnBase = 'inline-flex min-h-12 w-full items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold transition sm:w-auto sm:max-w-xs';
@endphp

<nav aria-label="Lesson navigation" class="mt-6 flex flex-col gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:items-center sm:justify-between" data-test="lesson-nav">

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
        @if ($nav['previous'])
            <a href="{{ $nav['previous']['url'] }}" title="{{ $nav['previous']['title'] }}" data-test="lesson-nav-previous"
               class="{{ $btnBase }} justify-start border border-slate-200 bg-white text-slate-600 hover:border-violet-200 hover:text-violet-700">
                <span aria-hidden="true" class="shrink-0">←</span>
                <span class="min-w-0 truncate">Previous: {{ $nav['previous']['title'] }}</span>
            </a>
        @endif

        <a href="{{ $nav['courseUrl'] }}" data-test="lesson-nav-course"
           class="{{ $btnBase }} justify-center border border-slate-200 bg-white text-slate-600 hover:border-violet-200 hover:text-violet-700">
            <span class="truncate">Back to course</span>
        </a>
    </div>

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
        @if ($nav['next'] && $nav['next']['locked'])
            <span aria-disabled="true" title="{{ $nav['next']['reason'] }}" data-test="lesson-nav-next-locked"
                  class="{{ $btnBase }} cursor-not-allowed justify-between border border-slate-200 bg-slate-50 text-slate-400">
                <span class="min-w-0">
                    <span class="block truncate">Next: {{ $nav['next']['title'] }}</span>
                    <span class="block truncate text-xs font-medium">{{ $nav['next']['reason'] }}</span>
                </span>
                <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="5" y="11" width="14" height="10" rx="2"></rect><path d="M8 11V7a4 4 0 0 1 8 0v4"></path></svg>
            </span>
        @elseif ($nav['next'])
            <a href="{{ $nav['next']['url'] }}" title="{{ $nav['next']['title'] }}" data-test="lesson-nav-next" data-next-lesson
               class="{{ $btnBase }} sad-btn-primary justify-between bg-violet-600 text-white hover:bg-violet-700">
                <span class="min-w-0 truncate">Next: {{ $nav['next']['title'] }}</span>
                <span aria-hidden="true" class="shrink-0">→</span>
            </a>
        @elseif ($nav['isLast'])
            @if ($nav['certificate'])
                <a href="{{ $nav['certificate']['url'] }}" data-test="lesson-nav-certificate"
                   class="{{ $btnBase }} justify-center border border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100">
                    <span class="truncate">{{ $nav['certificate']['label'] }}</span>
                </a>
            @endif
            <a href="{{ $nav['courseUrl'] }}" data-test="lesson-nav-finish"
               class="{{ $btnBase }} justify-center bg-emerald-600 text-white hover:bg-emerald-700">
                <span class="truncate">Back to course</span>
            </a>
        @endif
    </div>

</nav>
