<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Register') }}</title>
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

            <form method="POST" action="{{ route('register.store') }}" class="flex flex-col gap-6">
                @csrf

                <!-- Name -->
                <div>
                    <label for="name" class="mb-2 block text-sm font-medium text-gray-700">
                        {{ __('Name') }}
                    </label>
                    <input
                        id="name"
                        name="name"
                        value="{{ old('name') }}"
                        type="text"
                        required
                        autofocus
                        autocomplete="name"
                        placeholder="{{ __('Full name') }}"
                        class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-gray-900 placeholder:text-gray-400 outline-none focus:border-purple-500 focus:ring-2 focus:ring-purple-500/30"
                    >
                    @error('name')
                        <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

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

                <button type="submit" class="w-full rounded-xl bg-gray-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-gray-800">
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

</body>
</html>