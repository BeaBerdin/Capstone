<x-layouts::app :title="__('Student Invitations')">
@include('partials.soft3d')
    <div class="sad-page min-h-screen bg-slate-50">
        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">

            {{-- Header --}}
            {{-- Header card, as on My Certificates. --}}
            <section class="sad-banner mb-8 overflow-hidden rounded-3xl border border-violet-100 bg-white shadow-sm">
                <div class="relative px-6 py-6 sm:px-7">
                    <div class="pointer-events-none absolute -right-16 -top-20 h-52 w-52 rounded-full bg-violet-100/70 blur-3xl"></div>

            <div class="relative flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">
                        Student Invitations
                    </h1>

                    <p class="mt-1 text-sm text-slate-600">
                        Create invitation links and allow students to join your courses.
                    </p>
                </div>
            </div>
                </div>
            </section>

            {{-- Success message --}}
            @if (session('success'))
                <div class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Error message --}}
            @if (session('error'))
                <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    {{ session('error') }}
                </div>
            @endif

            {{-- Create invitation --}}
            <div class="sad-card mb-8 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="mb-5">
                    <h2 class="text-lg font-semibold text-slate-900">
                        Create Invitation
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Generate a unique invitation code for one of your courses.
                    </p>
                </div>

                <form method="POST" action="{{ route('course-invitations.store') }}">
                    @csrf

                    <div class="grid gap-5 md:grid-cols-2">
                        {{-- Course --}}
                        <div>
                            <label
                                for="course_id"
                                class="mb-2 block text-sm font-medium text-slate-700"
                            >
                                Course
                            </label>

                            <select
                                id="course_id"
                                name="course_id"
                                required
                                class="block w-full rounded-lg border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm focus:border-violet-500 focus:ring-violet-500"
                            >
                                <option value="">Select a course</option>

                                @foreach ($courses as $course)
                                    <option
                                        value="{{ $course->id }}"
                                        @selected(old('course_id') == $course->id)
                                    >
                                        {{ $course->title }}
                                    </option>
                                @endforeach
                            </select>

                            @error('course_id')
                                <p class="mt-1 text-sm text-red-600">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        {{-- Expiration --}}
                        <div>
                            <label
                                for="expires_at"
                                class="mb-2 block text-sm font-medium text-slate-700"
                            >
                                Expiration
                            </label>

                            <input
                                id="expires_at"
                                type="datetime-local"
                                name="expires_at"
                                value="{{ old('expires_at') }}"
                                class="block w-full rounded-lg border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm focus:border-violet-500 focus:ring-violet-500"
                            >

                            <p class="mt-1 text-xs text-slate-500">
                                Leave blank if the invitation should not expire.
                            </p>

                            @error('expires_at')
                                <p class="mt-1 text-sm text-red-600">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>
                    </div>

                    <div class="mt-5">
                        <button
                            type="submit"
                            class="sad-btn-primary inline-flex items-center justify-center h-11 rounded-xl bg-violet-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-violet-700"
                        >
                            Generate Invitation
                        </button>
                    </div>
                </form>
            </div>

            {{-- Invitation list --}}
            <div class="sad-card overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-6 py-5">
                    <h2 class="text-lg font-semibold text-slate-900">
                        Your Invitations
                    </h2>
                </div>

                @if ($invitations->isEmpty())
                    <div class="px-6 py-12 text-center">
                        <p class="text-sm text-slate-500">
                            You have not created any course invitations yet.
                        </p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                        Course
                                    </th>

                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                        Invitation Code
                                    </th>

                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                        Status
                                    </th>

                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                        Expires
                                    </th>

                                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">
                                        Actions
                                    </th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-slate-200">
                                @foreach ($invitations as $invitation)
                                    <tr>
                                        <td class="whitespace-nowrap px-6 py-4">
                                            <div class="text-sm font-medium text-slate-900">
                                                {{ $invitation->course->title ?? 'Course unavailable' }}
                                            </div>
                                        </td>

                                        <td class="whitespace-nowrap px-6 py-4">
                                            <code class="rounded bg-slate-100 px-2 py-1 text-sm font-semibold text-violet-700">
                                                {{ $invitation->code }}
                                            </code>
                                            @if ($invitation->isValid())
                                                @php $pwInviteLink = route('course-invitations.show', $invitation->code); @endphp
                                                <div class="mt-2 flex items-center gap-2">
                                                    <button
                                                        type="button"
                                                        data-copy="{{ $pwInviteLink }}"
                                                        data-test="copy-invite-link"
                                                        onclick="(function (b) { var t = b.getAttribute('data-copy'); var done = function () { b.textContent = 'Link copied'; setTimeout(function () { b.textContent = 'Copy link'; }, 2000); }; if (navigator.clipboard) { navigator.clipboard.writeText(t).then(done, function () { window.prompt('Copy this invitation link:', t); }); } else { window.prompt('Copy this invitation link:', t); } })(this)"
                                                        class="rounded-md border border-violet-200 px-2 py-0.5 text-xs font-semibold text-violet-700 transition hover:bg-violet-50"
                                                    >Copy link</button>
                                                    <span class="max-w-[14rem] whitespace-normal text-[11px] leading-4 text-slate-500">or share the code: students enter it under Course Invitations.</span>
                                                </div>
                                            @endif
                                        </td>

                                        <td class="whitespace-nowrap px-6 py-4">
                                            @if ($invitation->isAccepted())
                                                <span class="inline-flex rounded-full bg-green-100 px-2.5 py-1 text-xs font-medium text-green-700">
                                                    Accepted
                                                </span>
                                            @elseif ($invitation->isExpired())
                                                <span class="inline-flex rounded-full bg-red-100 px-2.5 py-1 text-xs font-medium text-red-700">
                                                    Expired
                                                </span>
                                            @else
                                                <span class="inline-flex rounded-full bg-blue-100 px-2.5 py-1 text-xs font-medium text-blue-700">
                                                    Active
                                                </span>
                                            @endif
                                        </td>

                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-600">
                                           {{ $invitation->expires_at?->timezone('Asia/Manila')->format('M d, Y h:i A') ?? 'Never' }}
                                        </td>

                                        <td class="whitespace-nowrap px-6 py-4 text-right">
                                            <div class="flex items-center justify-end gap-2">

                                                {{-- Search for one student by name or email (no full student list).
                                                     Invitations are single-use, so accepted or expired ones cannot be sent. --}}
                                                @if ($invitation->isValid())
                                                <form
                                                    method="GET"
                                                    action="{{ route('course-invitations.index') }}"
                                                    class="flex items-center gap-2"
                                                >
                                                    <input type="hidden" name="invitation" value="{{ $invitation->id }}">

                                                    <input
                                                        type="search"
                                                        name="q"
                                                        minlength="3"
                                                        required
                                                        value="{{ $searchInvitationId === $invitation->id ? $searchTerm : '' }}"
                                                        placeholder="Student name or email"
                                                        aria-label="Search student by name or email"
                                                        class="w-48 rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs text-slate-700"
                                                    >

                                                    <button
                                                        type="submit"
                                                        class="h-9 rounded-lg border border-violet-300 px-3 text-xs font-semibold text-violet-700 hover:bg-violet-50"
                                                    >
                                                        Find Student
                                                    </button>
                                                </form>
                                                @endif

                                                <form
                                                    method="POST"
                                                    action="{{ route('course-invitations.destroy', $invitation) }}"
                                                    onsubmit="return confirm('Delete this invitation?')"
                                                >
                                                    @csrf
                                                    @method('DELETE')

                                                    <button
                                                        type="submit"
                                                        class="h-9 rounded-lg border border-red-300 px-3 text-xs font-semibold text-red-600 hover:bg-red-50"
                                                    >
                                                        Delete
                                                    </button>
                                                </form>

                                            </div>
                                        </td>
                                    </tr>

                                    @if ($searchInvitationId === $invitation->id)
                                        <tr>
                                            <td colspan="5" class="bg-slate-50 px-6 py-4">
                                                @if ($searchError)
                                                    <p class="text-sm text-slate-600">
                                                        {{ $searchError }}
                                                    </p>
                                                @else
                                                    <ul class="space-y-2">
                                                        @foreach ($searchResults as $student)
                                                            <li class="flex items-center justify-between gap-4">
                                                                <span class="text-sm text-slate-800">
                                                                    {{ $student->name }} — {{ $student->email }}
                                                                </span>

                                                                <form
                                                                    method="POST"
                                                                    action="{{ route('course-invitations.send', $invitation) }}"
                                                                >
                                                                    @csrf

                                                                    <input type="hidden" name="student_id" value="{{ $student->id }}">

                                                                    <button
                                                                        type="submit"
                                                                        class="sad-btn-primary h-9 rounded-lg bg-violet-600 px-3 text-xs font-semibold text-white hover:bg-violet-700"
                                                                    >
                                                                        Send Invitation
                                                                    </button>
                                                                </form>
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                @endif
                                            </td>
                                        </tr>
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-layouts::app>