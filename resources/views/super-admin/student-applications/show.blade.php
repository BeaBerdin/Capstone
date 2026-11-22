<x-layouts::app :title="__('Student Application')">
@include('partials.soft3d')

@php
    $badge = fn (string $status) => match ($status) {
        'approved' => 'bg-emerald-50 text-emerald-700',
        'rejected' => 'bg-red-50 text-red-700',
        default => 'bg-amber-50 text-amber-700',
    };
    $fileUrl = route('super-admin.student-applications.study-load', $application);
    $isImage = in_array($application->study_load_mime, ['image/jpeg', 'image/png'], true);
    $isPdf = $application->study_load_mime === 'application/pdf';
    $isTeacher = $application->isTeacherApplication();
    $who = $isTeacher ? 'teacher' : 'student';
@endphp

    <div class="space-y-6 px-5 py-7 sm:px-6 lg:px-8 lg:py-9">

        <a href="{{ route('super-admin.student-applications.index', ['status' => $isLatest ? $application->status : 'pending']) }}"
           class="inline-flex items-center gap-1 text-sm font-medium text-slate-600 hover:text-violet-700">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"></path></svg>
            Back to Student Applications
        </a>

        @foreach (['success' => 'border-emerald-200 bg-emerald-50 text-emerald-700', 'warning' => 'border-amber-200 bg-amber-50 text-amber-800', 'error' => 'border-red-200 bg-red-50 text-red-700'] as $flash => $classes)
            @if (session($flash))
                <div class="rounded-xl border px-4 py-3 text-sm font-medium {{ $classes }}" role="status" data-test="flash-{{ $flash }}">{{ session($flash) }}</div>
            @endif
        @endforeach

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1fr)_380px]">

            @if ($isTeacher)
            {{-- Teacher registration: Faculty Verification Document (private file, Super Admin only) --}}
            @php
                $edpStartedReview = in_array($application->review_origin, ['edp_created', 'role_change', 'email_change', 'edp_legacy_review'], true);
            @endphp
            <section class="sad-card overflow-hidden rounded-2xl border border-slate-200 bg-white" data-test="teacher-registration">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4 sm:px-6">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-sky-700">Faculty Verification Document · Submission #{{ $application->submission_no }}</p>
                        <h1 class="mt-0.5 text-lg font-bold text-slate-900">{{ $student?->name ?? 'Unknown teacher' }}</h1>
                    </div>
                    @if ($application->hasStudyLoad())
                        <a href="{{ $fileUrl }}" target="_blank" rel="noopener"
                           class="inline-flex items-center gap-1.5 rounded-lg border border-violet-200 px-3 py-1.5 text-xs font-semibold text-violet-700 transition hover:bg-violet-50">
                            Open in new tab
                        </a>
                    @endif
                </div>

                @if ($application->hasStudyLoad())
                    <div class="bg-slate-50 p-3 sm:p-4" data-test="teacher-id-preview">
                        @if ($isPdf)
                            <iframe src="{{ $fileUrl }}" title="Faculty Verification Document of {{ $student?->name }}" class="h-[70vh] min-h-[420px] w-full rounded-xl border border-slate-200 bg-white"></iframe>
                        @elseif ($isImage)
                            <img src="{{ $fileUrl }}" alt="Faculty Verification Document of {{ $student?->name }}" class="mx-auto max-h-[75vh] w-auto rounded-xl border border-slate-200 bg-white object-contain">
                        @else
                            <p class="px-2 py-10 text-center text-sm text-slate-500">This file cannot be previewed. Use "Open in new tab" to download it.</p>
                        @endif
                    </div>

                    <p class="border-t border-slate-100 px-5 py-3 text-xs text-slate-500 sm:px-6">
                        {{ $application->study_load_original_name }} · {{ strtoupper(pathinfo($application->study_load_original_name, PATHINFO_EXTENSION)) ?: 'FILE' }} · {{ $application->sizeForHumans() }}
                    </p>
                @else
                    <p class="border-b border-slate-100 bg-slate-50 px-5 py-4 text-sm font-medium text-slate-600 sm:px-6" data-test="no-teacher-id">
                        @if (filled($application->study_load_original_name))
                            Faculty Verification Document replaced by a newer upload; this older file was removed.
                        @elseif ($edpStartedReview)
                            No Faculty Verification Document uploaded yet (this review was started by the EDP office).
                        @else
                            No Faculty Verification Document uploaded yet (registered before this requirement).
                        @endif
                    </p>
                @endif

                <div class="space-y-3 px-5 py-6 text-sm leading-6 text-slate-600 sm:px-6">
                    @if ($application->review_email)
                        <p>Confirm with the department that <strong>{{ $application->review_email }}</strong> belongs to this faculty member before approving.</p>
                    @else
                        <p>This historical review has no recorded email. Open Manage Users to start a review for the current address.</p>
                    @endif
                    <p>Teachers can access teacher tools after EDP approval and email confirmation.</p>
                </div>
            </section>
            @else
            {{-- Study Load preview --}}
            <section class="sad-card overflow-hidden rounded-2xl border border-slate-200 bg-white">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4 sm:px-6">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-violet-600">Study Load · Submission #{{ $application->submission_no }}</p>
                        <h1 class="mt-0.5 text-lg font-bold text-slate-900">{{ $student?->name ?? 'Unknown student' }}</h1>
                    </div>
                    <a href="{{ $fileUrl }}" target="_blank" rel="noopener"
                       class="inline-flex items-center gap-1.5 rounded-lg border border-violet-200 px-3 py-1.5 text-xs font-semibold text-violet-700 transition hover:bg-violet-50">
                        Open in new tab
                    </a>
                </div>

                <div class="bg-slate-50 p-3 sm:p-4">
                    @if ($isPdf)
                        <iframe src="{{ $fileUrl }}" title="Study Load of {{ $student?->name }}" class="h-[70vh] min-h-[420px] w-full rounded-xl border border-slate-200 bg-white"></iframe>
                    @elseif ($isImage)
                        <img src="{{ $fileUrl }}" alt="Study Load of {{ $student?->name }}" class="mx-auto max-h-[75vh] w-auto rounded-xl border border-slate-200 bg-white object-contain">
                    @else
                        <p class="px-2 py-10 text-center text-sm text-slate-500">This file cannot be previewed. Use "Open in new tab" to download it.</p>
                    @endif
                </div>

                <p class="border-t border-slate-100 px-5 py-3 text-xs text-slate-500 sm:px-6">
                    {{ $application->study_load_original_name }} · {{ strtoupper(pathinfo($application->study_load_original_name, PATHINFO_EXTENSION)) ?: 'FILE' }} · {{ $application->sizeForHumans() }}
                </p>
            </section>
            @endif

            <div class="space-y-6">

                {{-- Student and decision --}}
                <section class="sad-card rounded-2xl border border-slate-200 bg-white p-5 sm:p-6">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="text-base font-bold text-slate-900">Application</h2>
                        <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $badge($application->status) }}" data-test="application-status">{{ ucfirst($application->status) }}</span>
                    </div>

                    <dl class="mt-4 space-y-3 text-sm">
                        <div><dt class="text-xs text-slate-500">Registered as</dt><dd class="font-semibold text-slate-800" data-test="applicant-type">{{ $isTeacher ? 'Teacher' : 'Student' }}</dd></div>
                        <div><dt class="text-xs text-slate-500">Name</dt><dd class="font-semibold text-slate-800">{{ $student?->name }}</dd></div>
                        <div><dt class="text-xs text-slate-500">Email</dt><dd class="break-all font-semibold text-slate-800">{{ $student?->email }}</dd></div>
                        @if ($isTeacher)
                            <div><dt class="text-xs text-slate-500">Review email</dt><dd class="break-all text-slate-700">{{ $application->review_email ?? 'Not recorded' }}</dd></div>
                            <div><dt class="text-xs text-slate-500">Review source</dt><dd class="text-slate-700">{{ \Illuminate\Support\Str::headline($application->review_origin ?? 'Historical review') }}</dd></div>
                        @endif
                        <div><dt class="text-xs text-slate-500">Registered</dt><dd class="text-slate-700">{{ $student?->created_at?->format('M d, Y g:i A') }}</dd></div>
                        <div><dt class="text-xs text-slate-500">Submitted</dt><dd class="text-slate-700">{{ $application->submitted_at?->format('M d, Y g:i A') }}</dd></div>
                        <div><dt class="text-xs text-slate-500">Email verified</dt><dd class="text-slate-700">{{ $student?->hasVerifiedEmail() ? 'Yes' : 'Not yet' }}</dd></div>
                        @if ($application->reviewed_at)
                            <div><dt class="text-xs text-slate-500">Decided by</dt><dd class="text-slate-700">{{ $application->reviewer?->name ?? '—' }} · {{ $application->reviewed_at->format('M d, Y g:i A') }}</dd></div>
                        @endif
                        @if ($application->rejection_reason)
                            <div><dt class="text-xs text-slate-500">Rejection reason</dt><dd class="whitespace-pre-line rounded-lg bg-red-50 px-3 py-2 text-red-700">{{ $application->rejection_reason }}</dd></div>
                        @endif
                    </dl>

                    @if ($canReview)
                        <div class="mt-6 space-y-4 border-t border-slate-100 pt-5">
                            @if ($isTeacher && ! $application->hasStudyLoad())
                            {{-- No document: approval is refused (also on the server). Rejecting is still possible. --}}
                            <p class="rounded-xl border border-amber-200 bg-amber-50 px-3 py-3 text-sm text-amber-800" data-test="approve-needs-document">
                                This teacher application cannot be approved until a Faculty Verification Document is uploaded.
                                The teacher can upload it from their PathWise status page. You can still reject the application.
                            </p>
                            @else
                            <form method="POST" action="{{ route('super-admin.student-applications.approve', $application) }}"
                                  onsubmit="if (!confirm('Approve this application? The {{ $who }} will be emailed a link to verify their email.')) { return false; } this.querySelector('button').disabled = true;">
                                @csrf
                                @if ($isTeacher)
                                    <label class="mb-3 flex items-start gap-2 text-sm text-slate-700">
                                        <input type="checkbox" name="faculty_confirmed" value="1" required class="mt-1">
                                        I confirmed this person’s faculty status and the review email with their department.
                                    </label>
                                    @error('faculty_confirmed')
                                        <p class="mb-2 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                @endif
                                <button type="submit" class="sad-btn-primary inline-flex h-11 w-full items-center justify-center rounded-xl bg-violet-600 px-4 text-sm font-semibold text-white transition hover:bg-violet-700" data-test="approve-button">
                                    Approve application
                                </button>
                            </form>
                            @endif

                            <form method="POST" action="{{ route('super-admin.student-applications.reject', $application) }}" class="space-y-2"
                                  onsubmit="this.querySelector('button').disabled = true;">
                                @csrf
                                <label for="rejection_reason" class="block text-sm font-semibold text-slate-700">Reason for rejection</label>
                                <textarea id="rejection_reason" name="rejection_reason" rows="4" required minlength="10" maxlength="2000"
                                          placeholder="Tell the {{ $who }} what to correct (at least 10 characters)."
                                          class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-800 focus:border-violet-500 focus:ring-violet-500">{{ old('rejection_reason') }}</textarea>
                                @error('rejection_reason')
                                    <p class="text-xs font-medium text-red-600">{{ $message }}</p>
                                @enderror
                                <button type="submit" class="inline-flex h-11 w-full items-center justify-center rounded-xl border border-red-200 bg-white px-4 text-sm font-semibold text-red-600 transition hover:bg-red-50" data-test="reject-button">
                                    Reject application
                                </button>
                            </form>
                        </div>
                    @endif
                </section>

                {{-- History --}}
                <section class="sad-card rounded-2xl border border-slate-200 bg-white p-5 sm:p-6">
                    <h2 class="text-base font-bold text-slate-900">Submission history</h2>
                    <ol class="mt-4 space-y-3" data-test="history">
                        @foreach ($history as $item)
                            <li class="rounded-xl border px-3 py-3 text-sm {{ $item->is($application) ? 'border-violet-200 bg-violet-50/50' : 'border-slate-100' }}">
                                <div class="flex items-center justify-between gap-2">
                                    <a href="{{ route('super-admin.student-applications.show', $item) }}" class="font-semibold text-slate-800 hover:text-violet-700">Submission #{{ $item->submission_no }}</a>
                                    <span class="rounded-full px-2.5 py-0.5 text-[11px] font-semibold {{ $badge($item->status) }}">{{ ucfirst($item->status) }}</span>
                                </div>
                                <p class="mt-1 text-xs text-slate-500">Submitted {{ $item->submitted_at?->format('M d, Y g:i A') }}</p>
                                @if ($item->reviewed_at)
                                    <p class="text-xs text-slate-500">{{ ucfirst($item->status) }} by {{ $item->reviewer?->name ?? '—' }} · {{ $item->reviewed_at->format('M d, Y g:i A') }}</p>
                                @endif
                                @if ($item->rejection_reason)
                                    <p class="mt-1 whitespace-pre-line text-xs text-red-700">{{ $item->rejection_reason }}</p>
                                @endif
                                @if ($item->hasStudyLoad())
                                    <a href="{{ route('super-admin.student-applications.study-load', $item) }}" target="_blank" rel="noopener" class="mt-1 inline-block text-xs font-semibold text-violet-700 hover:underline">Open {{ $item->documentLabel() }}</a>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                </section>

            </div>
        </div>
    </div>

</x-layouts::app>
