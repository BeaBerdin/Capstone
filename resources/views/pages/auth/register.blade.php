<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Register') }}</title>
    <link rel="icon" href="/favicon.ico?v=3" sizes="any">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png?v=3">
    <link rel="icon" href="/favicon.png?v=3" type="image/png">
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-white flex items-center justify-center p-4">

    <div class="w-full max-w-md bg-white p-8 rounded-2xl border border-gray-200 shadow-lg">
        <div class="flex flex-col gap-6">

            <div class="text-center">
                <h1 class="text-3xl font-bold text-gray-900">
                    {{ __('Create an account') }}
                </h1>
                <p class="mt-2 text-sm text-gray-500">
                    {{ __('Enter your details below to create your account') }}
                </p>
            </div>

            <!-- Session Status -->
            <x-auth-session-status class="text-center text-emerald-600" :status="session('status')" />

            <form method="POST" action="{{ route('register.store') }}" enctype="multipart/form-data" class="flex flex-col gap-6" data-registration-form>
                @csrf

                <!-- First Name -->
                <div>
                    <label for="first_name" class="mb-2 block text-sm font-medium text-gray-700">
                        {{ __('First Name') }}
                    </label>
                    <input
                        id="first_name"
                        name="first_name"
                        value="{{ old('first_name') }}"
                        type="text"
                        required
                        autofocus
                        maxlength="100"
                        autocomplete="given-name"
                        placeholder="{{ __('First name') }}"
                        class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-gray-900 placeholder:text-gray-400 outline-none focus:border-purple-500 focus:ring-2 focus:ring-purple-500/30"
                    >
                    @error('first_name')
                        <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Middle Initial (optional) and Last Name -->
                <div class="grid grid-cols-[6.5rem_minmax(0,1fr)] items-end gap-3">
                    <div>
                        <label for="middle_initial" class="mb-2 block text-sm font-medium text-gray-700">
                            {{ __('Middle Initial') }}
                            <span class="font-normal text-gray-400">{{ __('(optional)') }}</span>
                        </label>
                        <input
                            id="middle_initial"
                            name="middle_initial"
                            value="{{ old('middle_initial') }}"
                            type="text"
                            maxlength="2"
                            autocomplete="additional-name"
                            placeholder="{{ __('M.I.') }}"
                            class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-center uppercase text-gray-900 placeholder:normal-case placeholder:text-gray-400 outline-none focus:border-purple-500 focus:ring-2 focus:ring-purple-500/30"
                        >
                    </div>

                    <div>
                        <label for="last_name" class="mb-2 block text-sm font-medium text-gray-700">
                            {{ __('Last Name') }}
                        </label>
                        <input
                            id="last_name"
                            name="last_name"
                            value="{{ old('last_name') }}"
                            type="text"
                            required
                            maxlength="100"
                            autocomplete="family-name"
                            placeholder="{{ __('Last name') }}"
                            class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-gray-900 placeholder:text-gray-400 outline-none focus:border-purple-500 focus:ring-2 focus:ring-purple-500/30"
                        >
                    </div>
                </div>

                @error('middle_initial')
                    <p class="-mt-4 text-sm text-red-500">{{ $message }}</p>
                @enderror
                @error('last_name')
                    <p class="-mt-4 text-sm text-red-500">{{ $message }}</p>
                @enderror

                <!-- Email Address -->
                <div>
                    <label for="email" class="mb-2 block text-sm font-medium text-gray-700">
                        {{ __('Email address') }}
                    </label>
                    <input
                        id="email"
                        name="email"
                        value="{{ old('email') }}"
                        type="email"
                        required
                        autocomplete="email"
                        placeholder="email@example.com"
                        class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-gray-900 placeholder:text-gray-400 outline-none focus:border-purple-500 focus:ring-2 focus:ring-purple-500/30"
                    >
                    @error('email')
                        <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Role selection: Student or Teacher -->
                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700">
                        {{ __('I am registering as a') }}
                    </label>
                    <div class="flex rounded-lg border border-gray-200 p-1 bg-gray-50">
                        <label class="flex-1 cursor-pointer">
                            <input type="radio" name="role" value="student" @checked(old('role', 'student') === 'student') class="peer sr-only">
                            <div class="rounded-md py-2 text-center text-sm font-medium text-gray-600 peer-checked:bg-white peer-checked:text-gray-900 peer-checked:shadow-sm transition">
                                {{ __('Student') }}
                            </div>
                        </label>
                        <label class="flex-1 cursor-pointer">
                            <input type="radio" name="role" value="teacher" @checked(old('role') === 'teacher') class="peer sr-only">
                            <div class="rounded-md py-2 text-center text-sm font-medium text-gray-600 peer-checked:bg-white peer-checked:text-gray-900 peer-checked:shadow-sm transition">
                                {{ __('Teacher') }}
                            </div>
                        </label>
                    </div>
                    @error('role')
                        <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                    <p class="mt-3 rounded-lg bg-purple-50 px-3 py-2 text-xs leading-5 text-purple-800" data-teacher-registration-note @if (old('role', 'student') !== 'teacher') hidden @endif>
                        {{ __('Teacher applicants need EDP approval confirming their faculty status. If approved, you will receive an email verification link. Teacher tools become available after both steps are complete.') }}
                    </p>
                </div>

                <!-- Study Load: students only (shown and required when Student is selected) -->
                @php $studentSelected = old('role', 'student') === 'student'; @endphp
                <div data-study-load-section @if (! $studentSelected) hidden @endif>
                    <label for="study_load" class="mb-2 block text-sm font-medium text-gray-700">
                        {{ __('Study Load') }}
                    </label>
                    <p class="mb-2 text-xs text-gray-500">
                        {{ __('Upload your current Study Load so the EDP office can verify that you are enrolled. PDF, JPG or PNG, up to 10 MB.') }}
                    </p>
                    <input
                        id="study_load"
                        name="study_load"
                        type="file"
                        accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"
                        @if ($studentSelected) required @endif
                        @unless ($studentSelected) disabled @endunless
                        data-study-load-input
                        class="block w-full rounded-xl border border-dashed border-purple-300 bg-purple-50/40 p-3 text-sm text-gray-600 file:mr-3 file:rounded-lg file:border-0 file:bg-purple-100 file:px-3 file:py-2 file:text-xs file:font-semibold file:text-purple-700"
                    >
                    <p class="mt-2 hidden text-sm text-red-500" data-study-load-size-error>
                        {{ \App\Models\StudentApplication::TOO_LARGE_MESSAGE }}
                    </p>
                    @error('study_load')
                        <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                    @if ($errors->any() && ! $errors->has('study_load') && $studentSelected)
                        <p class="mt-2 text-xs text-gray-500">{{ __('For your security, please choose your Study Load file again.') }}</p>
                    @endif
                    <p class="mt-3 rounded-lg bg-gray-50 px-3 py-2 text-xs leading-5 text-gray-500" data-privacy-notice>
                        {{ __('Data privacy: your Study Load contains personal information. It is used only to verify your enrollment, is accessed only by authorized EDP (Super Admin) personnel, and is stored securely and retained according to the institution\'s data-retention policy.') }}
                    </p>
                </div>

                <!-- Teacher ID: teachers only (shown and required when Teacher is selected) -->
                @php $teacherSelected = old('role') === 'teacher'; @endphp
                <div data-teacher-id-section @if (! $teacherSelected) hidden @endif>
                    <label for="teacher_id" class="mb-2 block text-sm font-medium text-gray-700">
                        {{ __('Teacher ID') }}
                    </label>
                    <p class="mb-2 text-xs text-gray-500">
                        {{ __('Upload your faculty or employee ID so the EDP office can verify that you teach here. PDF, JPG or PNG, up to 10 MB.') }}
                    </p>
                    <input
                        id="teacher_id"
                        name="teacher_id"
                        type="file"
                        accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"
                        @if ($teacherSelected) required @endif
                        @unless ($teacherSelected) disabled @endunless
                        data-teacher-id-input
                        class="block w-full rounded-xl border border-dashed border-purple-300 bg-purple-50/40 p-3 text-sm text-gray-600 file:mr-3 file:rounded-lg file:border-0 file:bg-purple-100 file:px-3 file:py-2 file:text-xs file:font-semibold file:text-purple-700"
                    >
                    <p class="mt-2 hidden text-sm text-red-500" data-teacher-id-size-error>
                        {{ \App\Models\StudentApplication::TOO_LARGE_MESSAGE }}
                    </p>
                    @error('teacher_id')
                        <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                    @if ($errors->any() && ! $errors->has('teacher_id') && $teacherSelected)
                        <p class="mt-2 text-xs text-gray-500">{{ __('For your security, please choose your Teacher ID file again.') }}</p>
                    @endif
                    <p class="mt-3 rounded-lg bg-gray-50 px-3 py-2 text-xs leading-5 text-gray-500" data-teacher-privacy-notice>
                        {{ __('Data privacy: your Teacher ID contains personal information. It is used only to verify your faculty status, is accessed only by authorized EDP (Super Admin) personnel, and is stored securely and retained according to the institution\'s data-retention policy.') }}
                    </p>
                </div>

                <!-- Password -->
                <div>
                    <label for="password" class="mb-2 block text-sm font-medium text-gray-700">
                        {{ __('Password') }}
                    </label>
                    <input
                        id="password"
                        name="password"
                        type="password"
                        required
                        autocomplete="new-password"
                        placeholder="{{ __('Password') }}"
                        class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-gray-900 placeholder:text-gray-400 outline-none focus:border-purple-500 focus:ring-2 focus:ring-purple-500/30"
                    >
                    @error('password')
                        <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Confirm Password -->
                <div>
                    <label for="password_confirmation" class="mb-2 block text-sm font-medium text-gray-700">
                        {{ __('Confirm password') }}
                    </label>
                    <input
                        id="password_confirmation"
                        name="password_confirmation"
                        type="password"
                        required
                        autocomplete="new-password"
                        placeholder="{{ __('Confirm password') }}"
                        class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-gray-900 placeholder:text-gray-400 outline-none focus:border-purple-500 focus:ring-2 focus:ring-purple-500/30"
                    >
                </div>

                <button type="submit" class="pw-btn-primary w-full rounded-xl px-4 py-3 text-sm font-semibold text-white transition">
                    {{ __('Create account') }}
                </button>
            </form>

            <div class="space-x-1 text-center text-sm text-gray-500 rtl:space-x-reverse">
                <span>{{ __('Already have an account?') }}</span>
                <a href="{{ route('login') }}" class="font-medium text-purple-600 hover:text-purple-700" wire:navigate>
                    {{ __('Log in') }}
                </a>
            </div>
        </div>
    </div>

    {{--
        Study Load field follows the selected role (no framework needed):
        Student shows it and makes it required; Teacher hides it, removes
        "required", clears any chosen file and disables the input so nothing
        is sent. Files over 10 MB are stopped before uploading. The server
        enforces all of this again.
    --}}
    <script>
        (function () {
            var form = document.querySelector('[data-registration-form]');
            if (!form) { return; }

            var section = form.querySelector('[data-study-load-section]');
            var input = form.querySelector('[data-study-load-input]');
            var sizeError = form.querySelector('[data-study-load-size-error]');
            var teacherNote = form.querySelector('[data-teacher-registration-note]');
            var maxBytes = {{ \App\Models\StudentApplication::MAX_KILOBYTES }} * 1024;

            function selectedRole() {
                var checked = form.querySelector('input[name="role"]:checked');
                return checked ? checked.value : 'student';
            }

            function sync() {
                var isStudent = selectedRole() === 'student';

                teacherNote.hidden = isStudent;
                section.hidden = !isStudent;
                input.required = isStudent;
                input.disabled = !isStudent;

                if (!isStudent) {
                    input.value = '';
                    sizeError.classList.add('hidden');
                }
            }

            function tooLarge() {
                var file = input.files && input.files[0];
                return !input.disabled && !!file && file.size > maxBytes;
            }

            form.querySelectorAll('input[name="role"]').forEach(function (radio) {
                radio.addEventListener('change', sync);
            });

            input.addEventListener('change', function () {
                sizeError.classList.toggle('hidden', !tooLarge());
            });

            form.addEventListener('submit', function (event) {
                if (tooLarge()) {
                    event.preventDefault();
                    sizeError.classList.remove('hidden');
                    input.focus();
                }
            });

            sync();
        })();
    </script>

    {{--
        Teacher ID field follows the selected role the same way: Teacher shows
        it and makes it required; Student hides it, clears any chosen file and
        disables the input so nothing is sent. Files over 10 MB are stopped
        before uploading. The server enforces all of this again.
    --}}
    <script>
        (function () {
            var form = document.querySelector('[data-registration-form]');
            if (!form) { return; }

            var section = form.querySelector('[data-teacher-id-section]');
            var input = form.querySelector('[data-teacher-id-input]');
            var sizeError = form.querySelector('[data-teacher-id-size-error]');
            if (!section || !input) { return; }
            var maxBytes = {{ \App\Models\StudentApplication::MAX_KILOBYTES }} * 1024;

            function isTeacher() {
                var checked = form.querySelector('input[name="role"]:checked');
                return !!checked && checked.value === 'teacher';
            }

            function syncTeacherId() {
                var teacher = isTeacher();

                section.hidden = !teacher;
                input.required = teacher;
                input.disabled = !teacher;

                if (!teacher) {
                    input.value = '';
                    sizeError.classList.add('hidden');
                }
            }

            function teacherIdTooLarge() {
                var file = input.files && input.files[0];
                return !input.disabled && !!file && file.size > maxBytes;
            }

            form.querySelectorAll('input[name="role"]').forEach(function (radio) {
                radio.addEventListener('change', syncTeacherId);
            });

            input.addEventListener('change', function () {
                sizeError.classList.toggle('hidden', !teacherIdTooLarge());
            });

            form.addEventListener('submit', function (event) {
                if (teacherIdTooLarge()) {
                    event.preventDefault();
                    sizeError.classList.remove('hidden');
                    input.focus();
                }
            });

            syncTeacherId();
        })();
    </script>

</body>
</html>