<x-layouts::app :title="__('Invitations')">
    @include('partials.soft3d')

    <main class="sad-page mx-auto max-w-4xl space-y-6 px-4 py-8 sm:px-6">
        <header class="sad-banner rounded-3xl border border-violet-100 bg-white p-6 shadow-sm">
            <h1 class="text-2xl font-bold text-slate-900">Invitations</h1>
            <p class="mt-1 text-sm text-slate-600">Use your teacher’s code or open a private invitation to join a course for free.</p>
        </header>

        @if (session('success'))
            <p role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</p>
        @endif
        @if (session('error'))
            <p role="alert" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</p>
        @endif

        <section aria-labelledby="join-course-heading" class="sad-card rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6" data-test="join-with-code">
            <h2 id="join-course-heading" class="text-lg font-semibold text-slate-900">Join Course via Invite Code</h2>
            <form method="POST" action="{{ route('student.invitations.join') }}" class="mt-4 space-y-2">
                @csrf
                <label for="invite-code" class="block text-sm font-medium text-slate-700">Invitation code or link</label>
                <div class="flex flex-col gap-3 sm:flex-row">
                    <input id="invite-code" name="code" type="text" value="{{ is_string(old('code')) ? old('code') : '' }}" maxlength="2048"
                           required autocomplete="off" autocapitalize="characters" spellcheck="false"
                           aria-describedby="invite-code-hint @error('code') invite-code-error @enderror"
                           @error('code') aria-invalid="true" @enderror
                           placeholder="Enter a code or paste a link"
                           class="sad-field w-full min-w-0 flex-1">
                    <button type="submit" class="sad-btn-primary min-h-11 shrink-0 rounded-xl bg-violet-600 px-4 py-2.5 text-sm font-semibold text-white">
                        Join Course
                    </button>
                </div>
                <p id="invite-code-hint" class="text-xs text-slate-500">Enter the 12-character code or paste the full link. You can review the invitation before joining.</p>
                @error('code')
                    <p id="invite-code-error" role="alert" class="text-sm font-medium text-red-700" data-test="invite-code-error">{{ $message }}</p>
                @enderror
            </form>
        </section>

        <section aria-labelledby="my-invitations-heading" class="sad-card rounded-2xl border border-slate-200 bg-white shadow-sm" data-test="my-invitations">
            <h2 id="my-invitations-heading" class="border-b border-slate-200 px-5 py-4 text-lg font-semibold text-slate-900">My Invitations</h2>
            @if ($invitations->isEmpty())
                <div class="px-5 py-8">
                    <p class="text-sm font-semibold text-slate-900">No invitations yet</p>
                    <p class="mt-1 text-sm text-slate-600">Private invitations and codes you redeem appear here.</p>
                </div>
            @else
                <ul class="divide-y divide-slate-200">
                    @foreach ($invitations as $invitation)
                        @php
                            $enrollment = $enrollments->get($invitation->course_id);
                            $courseAvailable = $invitation->courseIsAvailable();
                            $status = $enrollment && $courseAvailable
                                ? ($enrollment->grantsAccess() ? 'Joined' : 'Enrollment cancelled')
                                : ($invitation->redeemed_by_student && $courseAvailable ? 'Previously redeemed' : $invitation->status());
                        @endphp
                        <li class="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                            <div class="min-w-0">
                                <p class="break-words font-semibold text-slate-900">{{ $courseAvailable ? $invitation->course->title : 'Course unavailable' }}</p>
                                @if ($courseAvailable)
                                    <p class="mt-1 text-sm text-slate-600">From {{ $invitation->course->teacher?->name ?? 'your teacher' }}</p>
                                @endif
                                <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-slate-600">
                                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $status === 'Active' ? 'bg-violet-50 text-violet-800' : 'bg-slate-100 text-slate-700' }}">{{ $status }}</span>
                                    @if ($invitation->expires_at)
                                        <span>Expires <time datetime="{{ $invitation->expires_at->toIso8601String() }}">{{ $invitation->expires_at->timezone('Asia/Manila')->format('M d, Y g:i A') }} PHT</time></span>
                                    @else
                                        <span>No expiry</span>
                                    @endif
                                </div>
                            </div>
                            @if ($status === 'Active')
                                <a href="{{ route('course-invitations.show', $invitation->code) }}"
                                   class="inline-flex min-h-11 shrink-0 items-center justify-center rounded-lg border border-violet-200 px-3 py-2 text-sm font-semibold text-violet-700 hover:bg-violet-50">
                                    Review and join
                                </a>
                            @elseif ($status === 'Joined' && $courseAvailable)
                                <a href="{{ route('student.learn.course', $invitation->course_id) }}"
                                   class="shrink-0 text-sm font-semibold text-violet-700 underline">Open course</a>
                            @endif
                        </li>
                    @endforeach
                </ul>
                @if ($invitations->hasPages())
                    <div class="border-t border-slate-200 p-4">{{ $invitations->links() }}</div>
                @endif
            @endif
        </section>
    </main>
</x-layouts::app>
