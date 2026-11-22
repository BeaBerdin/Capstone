<x-layouts::app :title="__('Course Invitation')">
    @include('partials.soft3d')

    <main class="sad-page mx-auto max-w-2xl space-y-4 px-4 py-8 sm:px-6">
        @if (isset($approvalPending))
            <section class="sad-card rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <h1 class="text-2xl font-bold text-slate-900">Course invitation</h1>
                <p class="mt-2 text-sm text-slate-600">
                    {{ $approvalPending ? 'Your application must be approved before you can join a course. Keep the invitation link for later.' : 'Verify your email, then reopen this invitation link to join the course.' }}
                </p>
                <a href="{{ route($approvalPending ? 'student-application.status' : 'verification.notice') }}"
                   class="mt-4 inline-flex text-sm font-semibold text-violet-700 underline">
                    {{ $approvalPending ? 'View application status' : 'Verify email' }}
                </a>
            </section>
        @else
            <a href="{{ route('student.invitations.index') }}" class="inline-flex text-sm font-semibold text-violet-700 underline">
                Back to Course Invitations
            </a>
            <section class="sad-card rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                @php $courseAvailable = $invitation->courseIsAvailable(); @endphp
                @if ($courseAvailable && $invitation->status() === 'Active')
                    <x-pw.course-cover :course="$invitation->course" class="mb-5 rounded-xl" />
                @endif
                <h1 class="text-2xl font-bold text-slate-900">{{ $courseAvailable ? $invitation->course->title : 'Course invitation' }}</h1>
                <p class="mt-1 text-sm text-slate-600">
                    @if ($courseAvailable)
                        From {{ $invitation->course->teacher?->name ?? 'your teacher' }} ·
                    @endif
                    {{ $invitation->expires_at ? 'Expires '.$invitation->expires_at->timezone('Asia/Manila')->format('M d, Y g:i A').' PHT' : 'No expiry' }}
                </p>

                @error('invitation')
                    <p role="alert" class="mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $message }}</p>
                @enderror
                @if (session('success'))
                    <p role="status" class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</p>
                @endif

                @if ($enrollment?->grantsAccess() && $courseAvailable)
                    @unless (session('success'))
                        <p role="status" class="mt-5 text-sm text-slate-700">You are already enrolled in this course.</p>
                    @endunless
                    <a href="{{ route('student.learn.course', $invitation->course_id) }}"
                       class="sad-btn-primary mt-3 inline-flex rounded-xl bg-violet-600 px-4 py-2.5 text-sm font-semibold text-white">Open course</a>
                @elseif ($unavailableMessage)
                    @unless ($errors->has('invitation'))
                        <p role="status" class="mt-5 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                            {{ $unavailableMessage }}
                        </p>
                    @endunless
                    @if ($unavailableMessage === \App\Models\Transaction::ENROLLMENT_PAYMENT_CONFLICT)
                        <a href="{{ route('student.transactions') }}" class="mt-3 inline-flex text-sm font-semibold text-violet-700 underline">
                            View transactions
                        </a>
                    @endif
                @else
                    <p class="mt-5 rounded-xl border border-violet-100 bg-violet-50 px-4 py-3 text-sm text-violet-900">
                        This invitation covers the full course fee. You can enroll for free.
                    </p>
                    <form method="POST" action="{{ route('course-invitations.accept', $invitation->code) }}" class="mt-4"
                          onsubmit="const button = this.querySelector('[data-accept-button]'); if (button.disabled) return false; button.disabled = true; button.innerText = 'Joining course...';">
                        @csrf
                        <button type="submit" data-accept-button class="sad-btn-primary w-full rounded-xl bg-violet-600 px-4 py-2.5 text-sm font-semibold text-white sm:w-auto">
                            Accept and join for free
                        </button>
                    </form>
                @endif
            </section>
        @endif
    </main>
</x-layouts::app>
