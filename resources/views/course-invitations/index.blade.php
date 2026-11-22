<x-layouts::app :title="__('Course Invitations')">
    @include('partials.soft3d')

    <main class="sad-page min-h-screen">
        <div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
            <header class="sad-banner rounded-3xl border border-violet-100 bg-white p-6 shadow-sm">
                <h1 class="text-2xl font-bold text-slate-900">Course Invitations</h1>
                <p class="mt-1 text-sm text-slate-600">Share a reusable code or send a private invitation. Students who redeem an active invitation join for free.</p>
            </header>

            @if (session('success'))
                <p role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</p>
            @endif
            @if (session('error'))
                <p role="alert" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</p>
            @endif
            @if ($errors->any())
                <ul role="alert" class="list-disc rounded-xl border border-red-200 bg-red-50 px-8 py-3 text-sm text-red-800">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            @endif

            <section class="sad-card rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <h2 class="text-lg font-semibold text-slate-900">Create invitation code</h2>
                @if ($courses->isEmpty())
                    <p class="mt-2 text-sm text-slate-600">Publish one of your courses to create an invitation.</p>
                @else
                    <form method="POST" action="{{ route('course-invitations.store') }}" class="mt-4 space-y-4">
                        @csrf
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="course_id" class="block text-sm font-medium text-slate-700">Course</label>
                                <select id="course_id" name="course_id" required class="sad-field mt-1 w-full">
                                    <option value="">Select a published course</option>
                                    @foreach ($courses as $course)
                                        <option value="{{ $course->id }}" @selected(is_scalar(old('course_id')) && (string) old('course_id') === (string) $course->id)>{{ $course->title }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="expires_at" class="block text-sm font-medium text-slate-700">Expiration in Philippine time</label>
                                <input id="expires_at" name="expires_at" type="datetime-local" value="{{ is_string(old('expires_at')) ? old('expires_at') : '' }}"
                                       aria-describedby="expiry-hint" class="sad-field mt-1 w-full">
                                <p id="expiry-hint" class="mt-1 text-xs text-slate-500">Leave blank for a code that stays active until deactivated.</p>
                            </div>
                        </div>
                        <button type="submit" class="sad-btn-primary rounded-xl bg-violet-600 px-4 py-2.5 text-sm font-semibold text-white">
                            Create invitation
                        </button>
                    </form>
                @endif
            </section>

            <section aria-labelledby="manage-invitations" class="space-y-4"
                     onclick="const button = event.target.closest('[data-copy]'); if (button) { const fallback = () => window.prompt('Copy this value:', button.dataset.copy); if (navigator.clipboard?.writeText) { navigator.clipboard.writeText(button.dataset.copy).then(() => { button.textContent = 'Copied'; setTimeout(() => { button.textContent = button.dataset.copyLabel; }, 1800); }, fallback); } else { fallback(); } }">
                <h2 id="manage-invitations" class="text-lg font-semibold text-slate-900">Your invitations</h2>

                @forelse ($invitations as $invitation)
                    @php
                        $status = $invitation->status();
                        $private = $invitation->is_private || $invitation->student_id !== null;
                    @endphp
                    <article class="sad-card rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto]">
                            <div class="min-w-0">
                                <h3 class="break-words font-semibold text-slate-900">{{ $invitation->course->title ?? 'Course unavailable' }}</h3>
                                <p class="mt-1 text-sm text-slate-600">
                                    {{ $private ? 'Private invitation' : 'Reusable code' }}
                                    @if ($private && $invitation->student)
                                        <span class="break-words">for {{ $invitation->student->name }}</span>
                                    @endif
                                </p>
                                <p class="mt-2 text-xs text-slate-600">
                                    <span class="font-semibold">{{ $status }}</span>
                                    · {{ $invitation->expires_at ? 'Expires '.$invitation->expires_at->timezone('Asia/Manila')->format('M d, Y g:i A').' PHT' : 'No expiry' }}
                                    · <span data-test="invitation-usage">{{ $invitation->redeemed_by_count }} {{ Str::plural('student', $invitation->redeemed_by_count) }} joined</span>
                                </p>
                            </div>

                            <div class="min-w-0">
                                <code class="rounded-md bg-violet-50 px-2 py-1 text-sm font-semibold text-violet-800">{{ $invitation->code }}</code>
                                @if ($status === 'Active')
                                    @php $link = route('course-invitations.show', $invitation->code); @endphp
                                    <p class="mt-2 break-all text-xs text-slate-500">{{ $link }}</p>
                                    <div class="mt-2 flex flex-wrap gap-2">
                                        <button type="button" data-copy="{{ $invitation->code }}" data-copy-label="Copy code" data-test="copy-invite-code" aria-live="polite"
                                                class="rounded-lg border border-violet-200 px-3 py-2 text-xs font-semibold text-violet-700">
                                            Copy code
                                        </button>
                                        <button type="button" data-copy="{{ $link }}" data-copy-label="Copy link" data-test="copy-invite-link" aria-live="polite"
                                                class="rounded-lg border border-violet-200 px-3 py-2 text-xs font-semibold text-violet-700">
                                            Copy link
                                        </button>
                                    </div>
                                @endif
                            </div>

                            @if (! $invitation->deactivated_at && ! $invitation->parentInvitation?->deactivated_at)
                                <form method="POST" action="{{ route('course-invitations.destroy', $invitation) }}"
                                      onsubmit="return confirm('{{ $private ? 'Deactivate this private invitation?' : 'Deactivate this code and the private invitations sent from it?' }}')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-lg border border-red-200 px-3 py-2 text-sm font-semibold text-red-700 hover:bg-red-50">
                                        Deactivate
                                    </button>
                                </form>
                            @endif
                        </div>

                        @if (! $private && $status === 'Active')
                            <div class="mt-4 border-t border-slate-200 pt-4">
                                <form method="GET" action="{{ route('course-invitations.index') }}" class="flex flex-col gap-2 sm:flex-row sm:items-end">
                                    <input type="hidden" name="invitation" value="{{ $invitation->id }}">
                                    <div class="w-full sm:max-w-xs">
                                        <label for="student-{{ $invitation->id }}" class="block text-sm font-medium text-slate-700">Send privately</label>
                                        <input id="student-{{ $invitation->id }}" name="q" type="search" minlength="3" maxlength="255" required
                                               value="{{ $searchInvitationId === $invitation->id ? $searchTerm : '' }}"
                                               placeholder="Student name or email" class="sad-field mt-1 w-full">
                                    </div>
                                    <button type="submit" class="rounded-lg border border-violet-200 px-3 py-2 text-sm font-semibold text-violet-700">Find student</button>
                                </form>

                                @if ($searchInvitationId === $invitation->id)
                                    @if ($searchError)
                                        <p role="status" class="mt-3 text-sm text-slate-600">{{ $searchError }}</p>
                                    @else
                                        <ul class="mt-3 space-y-2">
                                            @foreach ($searchResults as $student)
                                                <li class="flex flex-col gap-2 rounded-lg bg-slate-50 p-3 sm:flex-row sm:items-center sm:justify-between">
                                                    <div class="min-w-0">
                                                        <p class="break-words text-sm font-medium text-slate-900">{{ $student->name }}</p>
                                                        <p class="break-all text-xs text-slate-600">{{ $student->email }}</p>
                                                    </div>
                                                    <form method="POST" action="{{ route('course-invitations.send', $invitation) }}">
                                                        @csrf
                                                        <input type="hidden" name="student_id" value="{{ $student->id }}">
                                                        <button type="submit" aria-label="Send private invitation to {{ $student->name }}"
                                                                class="rounded-lg border border-violet-200 px-3 py-2 text-sm font-semibold text-violet-700">
                                                            Send invitation
                                                        </button>
                                                    </form>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endif
                                @endif
                            </div>
                        @endif
                    </article>
                @empty
                    <p class="sad-card rounded-2xl border border-slate-200 bg-white p-6 text-sm text-slate-600">You have no course invitations yet.</p>
                @endforelse
            </section>
        </div>
    </main>
</x-layouts::app>