{{--
    Page frame for a form (PathWise forms reference): header, then the form
    card (default slot) beside a side panel (aside slot) on desktop.
    - intro slot: notices shown between the header and the form.
    - subtitle slot: header line with markup (instead of the subtitle prop).
    Include partials.soft3d on the page.
--}}
@props([
    'crumbs' => [],
    'title',
    'subtitle' => null,
    'backUrl' => null,
    'backLabel' => 'Back',
    'required' => true,
])

<div class="sad-page min-h-screen">
    <main class="px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
        <div class="mx-auto max-w-6xl">

            <x-pw.form-header
                :crumbs="$crumbs"
                :title="$title"
                :subtitle="$subtitle"
                :back-url="$backUrl"
                :back-label="$backLabel"
                :required="$required"
            >{{ $subtitleSlot ?? '' }}</x-pw.form-header>

            @isset($intro)
                <div class="mt-5 space-y-4">{{ $intro }}</div>
            @endisset

            <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1fr)_300px] lg:items-start">
                <div class="min-w-0">{{ $slot }}</div>

                {{ $aside ?? '' }}
            </div>

        </div>
    </main>
</div>
