<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head', ['title' => __('Application status')])
</head>
@php
    $isTeacher = (bool) ($isTeacher ?? false);
    $status = $latest->status;
    $badge = match ($status) {
        'approved' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'rejected' => 'bg-red-50 text-red-700 ring-red-200',
        default => 'bg-amber-50 text-amber-700 ring-amber-200',
    };
    $label = match ($status) {
        'approved' => 'Approved',
        'rejected' => $isTeacher ? 'Not approved yet' : 'Needs a corrected Study Load',
        default => 'Pending review',
    };
@endphp
<body class="min-h-screen bg-[#f6f5fb] text-gray-900 antialiased">
    <div class="flex min-h-svh flex-col items-center px-4 py-8 sm:px-6 sm:py-12">
        <div class="flex w-full max-w-2xl flex-col gap-6">

            <a href="{{ route('home') }}" class="flex flex-col items-center gap-2">
                <img src="{{ asset('images/pathwise-logo-full.png') }}" alt="PATHWISE" class="h-12 w-auto">
            </a>

            <main class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xl sm:p-8"
                  data-application-poll
                  data-check-url="{{ route('student-application.check') }}"
                  data-user-id="{{ $user->id }}"
                  data-status="{{ $status }}"
                  data-verified="{{ $user->hasVerifiedEmail() ? '1' : '0' }}">

                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.12em] text-violet-600">{{ $isTeacher ? 'Teacher registration' : 'Student application' }}</p>
                        <h1 class="mt-1 text-2xl font-bold text-gray-900">Hi {{ $user->name }}</h1>
                    </div>
                    <span class="inline-flex self-start rounded-full px-3 py-1 text-xs font-bold ring-1 {{ $badge }}" data-test="application-status">{{ $label }}</span>
                </div>

                @foreach (['success' => 'border-emerald-200 bg-emerald-50 text-emerald-700', 'error' => 'border-red-200 bg-red-50 text-red-700'] as $flash => $classes)
                    @if (session($flash))
                        <div class="mt-5 rounded-xl border px-4 py-3 text-sm font-medium {{ $classes }}" role="status">{{ session($flash) }}</div>
                    @endif
                @endforeach
                @if (session('status') === 'verification-link-sent')
                    <div class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700" role="status">
                        A new verification link has been sent to {{ $user->email }}.
                    </div>
                @endif

                {{-- What happens now --}}
                <div class="mt-6 rounded-xl bg-violet-50/60 px-4 py-4 text-sm leading-6 text-gray-700">
                    @if ($isTeacher && $status === 'pending')
                        <p>Thank you for registering as a teacher. The EDP office is reviewing your registration.</p>
                        <p class="mt-2">You will receive an email at <strong>{{ $user->email }}</strong> once your registration is approved. The email contains a link to verify your email address; after that you can use your Teacher Dashboard and create courses.</p>
                    @elseif ($isTeacher && $status === 'rejected')
                        <p>The EDP office could not approve your teacher registration yet. Please read the reason below. When the issue is resolved, you can ask the EDP office to review it again. You keep the same account.</p>
                    @elseif ($status === 'pending')
                        <p>Thank you for registering. The EDP office is reviewing the Study Load you uploaded.</p>
                        <p class="mt-2">You will receive an email at <strong>{{ $user->email }}</strong> once your application is approved. The email contains a link to verify your email address; after that you can use your Student Dashboard.</p>
                    @elseif ($status === 'rejected')
                        <p>The EDP office could not approve your application yet. Please read the reason below, then upload a corrected Study Load. You keep the same account.</p>
                    @elseif (! $user->hasVerifiedEmail())
                        <p>Your application is approved. Please open the email we sent to <strong>{{ $user->email }}</strong> and click <strong>Verify Email Address</strong>.</p>
                        <p class="mt-2">If the link expired or you cannot find the email, send a new one below.</p>
                    @else
                        <p>Your application is approved and your email is verified.</p>
                    @endif
                </div>

                @if ($status === 'rejected' && $latest->rejection_reason)
                    <div class="mt-4 rounded-xl border border-red-100 bg-red-50 px-4 py-3">
                        <p class="text-xs font-bold uppercase tracking-wide text-red-700">Reason from the EDP office</p>
                        <p class="mt-1 whitespace-pre-line text-sm text-red-700" data-test="rejection-reason">{{ $latest->rejection_reason }}</p>
                    </div>

                    @if ($isTeacher)
                    {{-- Teacher: ask for another review (no file) --}}
                    <form method="POST" action="{{ route('student-application.resubmit') }}" class="mt-6"
                          onsubmit="const b = this.querySelector('button[type=submit]'); if (b.disabled) { return false; } b.disabled = true; b.innerText = 'Sending...';"
                          data-test="teacher-review-again-form">
                        @csrf
                        <button type="submit" class="pw-btn-primary w-full rounded-xl px-4 py-3 text-sm font-semibold text-white transition">
                            Ask the EDP office to review again
                        </button>
                    </form>
                    @else
                    {{-- Resubmission --}}
                    <form method="POST" action="{{ route('student-application.resubmit') }}" enctype="multipart/form-data" class="mt-6 space-y-3"
                          onsubmit="const b = this.querySelector('button[type=submit]'); if (b.disabled) { return false; } b.disabled = true; b.innerText = 'Uploading...';"
                          data-test="resubmit-form">
                        @csrf
                        <label for="study_load" class="block text-sm font-semibold text-gray-700">Corrected Study Load</label>
                        <input id="study_load" name="study_load" type="file" required accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"
                               class="block w-full rounded-xl border border-dashed border-violet-200 bg-violet-50/40 p-3 text-sm text-gray-600 file:mr-3 file:rounded-lg file:border-0 file:bg-violet-100 file:px-3 file:py-2 file:text-xs file:font-semibold file:text-violet-700">
                        <p class="text-xs text-gray-500">PDF, JPG or PNG, up to 10 MB.</p>
                        @error('study_load')
                            <p class="text-sm font-medium text-red-600">{{ $message }}</p>
                        @enderror
                        <button type="submit" class="pw-btn-primary w-full rounded-xl px-4 py-3 text-sm font-semibold text-white transition">
                            Submit corrected Study Load
                        </button>
                    </form>
                    @endif
                @endif

                @if ($status === 'approved' && ! $user->hasVerifiedEmail())
                    <form method="POST" action="{{ route('verification.send') }}" class="mt-6">
                        @csrf
                        <button type="submit" class="pw-btn-primary w-full rounded-xl px-4 py-3 text-sm font-semibold text-white transition" data-test="resend-verification">
                            Resend verification email
                        </button>
                    </form>
                @elseif ($status === 'approved')
                    <a href="{{ route('dashboard') }}" class="pw-btn-primary mt-6 inline-flex w-full items-center justify-center rounded-xl px-4 py-3 text-sm font-semibold text-white transition">
                        Go to my dashboard
                    </a>
                @endif

                {{-- History --}}
                <section class="mt-8">
                    <h2 class="text-sm font-bold text-gray-900">Your submissions</h2>
                    <ol class="mt-3 divide-y divide-gray-100 rounded-xl border border-gray-100">
                        @foreach ($applications as $application)
                            <li class="px-4 py-3 text-sm">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="font-semibold text-gray-800">Submission #{{ $application->submission_no }}</span>
                                    <span class="text-xs font-semibold {{ match ($application->status) { 'approved' => 'text-emerald-700', 'rejected' => 'text-red-700', default => 'text-amber-700' } }}">{{ ucfirst($application->status) }}</span>
                                </div>
                                <p class="mt-0.5 text-xs text-gray-500">
                                    {{ $application->hasStudyLoad() ? $application->study_load_original_name.' · ' : '' }}submitted {{ $application->submitted_at?->format('M d, Y g:i A') }}
                                    @if ($application->reviewed_at)
                                        · reviewed {{ $application->reviewed_at->format('M d, Y g:i A') }}
                                    @endif
                                </p>
                                @if ($application->rejection_reason && ! $application->is($latest))
                                    <p class="mt-1 whitespace-pre-line text-xs text-red-700">{{ $application->rejection_reason }}</p>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                </section>

                <div class="mt-8 flex flex-wrap items-center justify-between gap-3 border-t border-gray-100 pt-5 text-sm">
                    <a href="{{ route('profile.edit') }}" class="font-medium text-violet-700 hover:text-violet-800">Account settings</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="font-medium text-gray-500 hover:text-gray-800" data-test="logout-button">Log out</button>
                    </form>
                </div>
            </main>
        </div>
    </div>

    {{--
        Follows the application by itself, so the student never has to
        refresh: the EDP's decision and the email verification usually happen
        elsewhere (the EDP's computer, the Gmail app).
          - every 5 seconds, and at once when the tab becomes visible again;
          - pending -> approved/rejected: reload once to show the new state;
          - approved and verified: go to the dashboard;
          - the session now belongs to another account, or it expired: stop
            (never redirect someone else, never switch accounts).
        The server only ever answers for the signed-in account.
    --}}
    <script>
        (function () {
            var root = document.querySelector('[data-application-poll]');
            if (!root || !window.fetch) { return; }

            var checkUrl = root.dataset.checkUrl;
            var userId = Number(root.dataset.userId);
            var shownStatus = root.dataset.status;
            var shownVerified = root.dataset.verified === '1';
            var interval = 5000;
            var timer = null;
            var busy = false;
            var stopped = false;

            function stop() {
                stopped = true;
                clearTimeout(timer);
            }

            function schedule() {
                if (stopped) { return; }
                clearTimeout(timer);
                timer = setTimeout(check, interval);
            }

            function check() {
                if (stopped || busy) { return; }
                busy = true;

                fetch(checkUrl, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                    cache: 'no-store'
                })
                    .then(function (response) {
                        // Logged out or session expired (sent to the login
                        // page, or refused): nothing to follow.
                        var type = response.headers.get('Content-Type') || '';
                        if (response.status === 401 || response.status === 419
                            || response.redirected || type.indexOf('application/json') === -1) {
                            stop();
                            return null;
                        }

                        return response.ok ? response.json() : null;
                    })
                    .then(function (data) {
                        if (!data) { return; }

                        // This browser is now signed in to a different account.
                        if (Number(data.user_id) !== userId) {
                            stop();
                            return;
                        }

                        if (data.ready) {
                            stop();
                            window.location.assign(data.dashboard_url);
                            return;
                        }

                        if (data.status !== shownStatus || data.verified !== shownVerified) {
                            stop();
                            window.location.reload();
                        }
                    })
                    .catch(function () { /* network blip: try again next time */ })
                    .then(function () {
                        busy = false;
                        schedule();
                    });
            }

            document.addEventListener('visibilitychange', function () {
                if (document.visibilityState === 'visible' && !stopped) {
                    clearTimeout(timer);
                    check();
                }
            });

            window.addEventListener('pagehide', stop);

            // Restored from the back/forward cache: reload so the state is
            // current and polling starts again.
            window.addEventListener('pageshow', function (event) {
                if (event.persisted) { window.location.reload(); }
            });

            schedule();
        })();
    </script>
</body>
</html>
