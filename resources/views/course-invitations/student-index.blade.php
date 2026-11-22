<x-layouts::app :title="__('Course Invitations')">

    <div class="mx-auto w-full max-w-4xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">

        <div>
            <h1 class="text-2xl font-bold text-slate-900">Course Invitations</h1>
            <p class="mt-1 text-sm text-slate-600">
                Join a course with the invite code from your teacher, or open an invitation your teacher sent you.
            </p>
        </div>

        @foreach (['success' => 'border-emerald-200 bg-emerald-50 text-emerald-700', 'error' => 'border-red-200 bg-red-50 text-red-700'] as $flash => $classes)
            @if (session($flash))
                <div class="rounded-lg border px-4 py-3 text-sm {{ $classes }}" role="status">{{ session($flash) }}</div>
            @endif
        @endforeach

        {{-- Join with invite code --}}
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm" data-test="join-with-code">
            <h2 class="text-lg font-semibold text-slate-900">Join with invite code</h2>
            <p class="mt-1 text-sm text-slate-600">
                Type the 12-character code (you can also paste the whole invitation link).
            </p>

            <form method="POST" action="{{ route('student.invitations.join') }}" class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-start">
                @csrf
                <div class="flex-1">
                    <label for="invite-code" class="sr-only">Invite code</label>
                    <input
                        id="invite-code"
                        name="code"
                        type="text"
                        value="{{ old('code') }}"
                        required
                        maxlength="200"
                        autocomplete="off"
                        autocapitalize="characters"
                        spellcheck="false"
                        placeholder="e.g. AB12CD34EF56"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2.5 font-mono text-sm uppercase tracking-wider text-slate-900 focus:border-violet-500 focus:ring-violet-500"
                    >
                    @error('code')
                        <p class="mt-2 text-sm font-medium text-red-600" data-test="invite-code-error">{{ $message }}</p>
                    @enderror
                </div>
                <button type="submit" class="pw-btn-primary inline-flex items-center justify-center whitespace-nowrap rounded-lg px-5 py-2.5 text-sm font-semibold text-white transition">
                    Join Course
                </button>
            </form>

            <p class="mt-3 text-xs text-slate-500">
                Free courses: you are enrolled as soon as you accept. Paid courses: you continue to the usual payment.
            </p>
        </section>

        {{-- Invitations sent to this student --}}
        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm" data-test="my-invitations">
            <div class="border-b border-slate-100 px-6 py-4">
                <h2 class="text-lg font-semibold text-slate-900">Invitations from your teachers</h2>
            </div>

            @if ($invitations->isEmpty())
                <p class="px-6 py-10 text-center text-sm text-slate-500">
                    You have no open course invitations right now.
                </p>
            @else
                <ul class="divide-y divide-slate-100">
                    @foreach ($invitations as $invitation)
                        <li class="flex flex-col gap-3 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                            <div class="min-w-0">
                                <p class="truncate font-semibold text-slate-900">{{ $invitation->course->title }}</p>
                                <p class="mt-0.5 text-xs text-slate-500">
                                    From {{ $invitation->course->teacher?->name ?? 'your teacher' }}
                                    · {{ (float) $invitation->course->price > 0 ? '₱'.number_format((float) $invitation->course->price, 2) : 'Free' }}
                                    @if ($invitation->expires_at)
                                        · expires {{ $invitation->expires_at->timezone('Asia/Manila')->format('M d, Y g:i A') }}
                                    @endif
                                </p>
                            </div>
                            <a href="{{ route('course-invitations.show', $invitation->code) }}"
                               class="inline-flex items-center justify-center whitespace-nowrap rounded-lg border border-violet-200 px-4 py-2 text-sm font-semibold text-violet-700 transition hover:bg-violet-50">
                                Open invitation
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

    </div>

</x-layouts::app>
