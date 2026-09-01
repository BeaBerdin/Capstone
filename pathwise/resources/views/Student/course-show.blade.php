<x-layouts::app :title="$course->title">

@php
    $isEnrolled = \App\Models\Enrollment::where('student_id', auth()->id())
        ->where('course_id', $course->id)
        ->exists();

    $pendingTransaction = \App\Models\Transaction::where('student_id', auth()->id())
        ->where('course_id', $course->id)
        ->where('status', 'pending')
        ->latest()
        ->first();
@endphp

<div class="min-h-screen bg-black p-6">

    <div class="mx-auto max-w-7xl space-y-6">

        {{-- Back Button --}}
        <div class="flex items-center gap-2">
            <a href="{{ route('student.marketplace') }}"
               class="inline-flex items-center gap-2 rounded-xl border border-purple-500/20 bg-zinc-950 px-4 py-2 text-sm font-semibold text-purple-300 transition hover:bg-purple-500/10">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                Back to Marketplace
            </a>
        </div>

        {{-- Course Info Card --}}
        <div class="rounded-2xl border border-purple-500/20 bg-zinc-950 p-6 shadow-xl">

            {{-- Category Badge --}}
            <span class="inline-flex rounded-full bg-purple-500/15 px-3 py-1 text-xs font-semibold text-purple-400 border border-purple-500/20">
                {{ $course->category->name ?? 'No Category' }}
            </span>

            <h1 class="mt-3 text-3xl font-bold text-white">
                {{ $course->title }}
            </h1>

            <p class="mt-4 text-gray-400 leading-relaxed">
                {{ $course->description }}
            </p>

            {{-- Meta Grid --}}
            <div class="mt-5 grid gap-4 sm:grid-cols-3">
                <div class="rounded-xl border border-purple-500/10 bg-[#111111] p-4">
                    <div class="flex items-center gap-2">
                        <svg class="h-4 w-4 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                        <span class="text-xs uppercase tracking-wider text-gray-400">Level</span>
                    </div>
                    <p class="mt-1 text-lg font-semibold text-white">{{ ucfirst($course->difficulty_level) }}</p>
                </div>

                <div class="rounded-xl border border-purple-500/10 bg-[#111111] p-4">
                    <div class="flex items-center gap-2">
                        <svg class="h-4 w-4 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <span class="text-xs uppercase tracking-wider text-gray-400">Hours</span>
                    </div>
                    <p class="mt-1 text-lg font-semibold text-white">{{ $course->estimated_hours ?? 'N/A' }}</p>
                </div>

                <div class="rounded-xl border border-purple-500/10 bg-[#111111] p-4">
                    <div class="flex items-center gap-2">
                        <svg class="h-4 w-4 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <span class="text-xs uppercase tracking-wider text-gray-400">Price</span>
                    </div>
                    <p class="mt-1 text-lg font-semibold text-white">₱{{ number_format($course->price, 2) }}</p>
                </div>
            </div>

            {{-- Status / Action Area --}}
            @if($isEnrolled)

                <div class="mt-6 flex items-center gap-3 rounded-xl border border-green-500/30 bg-green-500/10 p-4 text-green-400">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span>You are already enrolled in this course.</span>
                </div>

                <a href="{{ route('student.learn.course', $course) }}"
                   class="mt-4 inline-flex items-center gap-2 rounded-xl bg-purple-600 px-6 py-3 text-sm font-semibold text-white shadow-lg transition hover:bg-purple-700">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    Continue Learning
                </a>

            @elseif($pendingTransaction)

                <div class="mt-6 flex items-center gap-3 rounded-xl border border-yellow-500/30 bg-yellow-500/10 p-4 text-yellow-400">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span>You already have a pending payment for this course.</span>
                </div>

                <a href="{{ route('student.transactions.show', $pendingTransaction) }}"
                   class="mt-4 inline-flex items-center gap-2 rounded-xl bg-yellow-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-yellow-700">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                    View Pending Transaction
                </a>

            @elseif($course->price > 0)

                <div class="mt-6 flex items-center gap-3 rounded-xl border border-orange-500/30 bg-orange-500/10 p-4 text-orange-400">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    <span>This is a paid course. Please purchase the course and upload your proof of payment for admin verification.</span>
                </div>

                <form action="{{ route('student.transactions.store', $course) }}"
                      method="POST"
                      class="mt-4">
                    @csrf
                    <button class="inline-flex items-center gap-2 rounded-xl bg-purple-600 px-6 py-3 text-sm font-semibold text-white shadow-lg transition hover:bg-purple-700">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                        Purchase Course
                    </button>
                </form>

            @else

                <form action="{{ route('student.enroll', $course) }}"
                      method="POST"
                      class="mt-6">
                    @csrf
                    <button class="inline-flex items-center gap-2 rounded-xl bg-green-600 px-6 py-3 text-sm font-semibold text-white shadow-lg transition hover:bg-green-700">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Enroll for Free
                    </button>
                </form>

            @endif

        </div>

        {{-- Course Lessons --}}
        <div class="rounded-2xl border border-purple-500/20 bg-zinc-950 p-6 shadow-xl">

            <div class="mb-4 flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-purple-500/20">
                    <svg class="h-5 w-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                </div>
                <h2 class="text-xl font-bold text-white">Course Lessons</h2>
            </div>

            @forelse($course->lessons as $lesson)
                <div class="mb-3 flex items-center justify-between rounded-xl border border-neutral-800 bg-[#111111] p-4 transition hover:border-purple-500/20 hover:bg-[#1a1a1a]">
                    <div class="flex items-center gap-3">
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-purple-500/20 text-sm font-bold text-purple-400">
                            {{ $lesson->lesson_order }}
                        </div>
                        <span class="font-medium text-white">
                            {{ $lesson->title }}
                        </span>
                    </div>

                    @if($lesson->is_preview)
                        <span class="rounded-full bg-purple-500/15 px-3 py-1 text-xs font-semibold text-purple-400 border border-purple-500/20">
                            Preview
                        </span>
                    @endif
                </div>
            @empty
                <div class="rounded-xl border border-dashed border-purple-500/20 bg-[#111111] p-8 text-center">
                    <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-purple-500/20 mx-auto">
                        <svg class="h-8 w-8 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                    </div>
                    <p class="text-lg font-semibold text-white">No lessons added yet</p>
                    <p class="text-sm text-gray-400">Lessons will appear here once added.</p>
                </div>
            @endforelse

        </div>

    </div>
</div>

</x-layouts::app>