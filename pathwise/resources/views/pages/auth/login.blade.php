<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Log in') }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-white flex items-center justify-center p-4">

    <div class="w-full max-w-md bg-white p-8 rounded-2xl border border-gray-200 shadow-lg">
        <div class="flex flex-col gap-6">

            <div class="text-center">
                <h1 class="text-3xl font-bold text-gray-900">
                    Log in to your account
                </h1>
                <p class="mt-2 text-sm text-gray-500">
                    Enter your email and password below to log in
                </p>
            </div>

            <x-auth-session-status class="text-center text-emerald-600" :status="session('status')" />

            <x-passkey-verify />

            <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-6">
                @csrf

                <div>
                    <label for="email" class="mb-2 block text-sm font-medium text-gray-700">
                        Email address
                    </label>
                    <input
                        id="email" name="email" value="{{ old('email') }}" type="email" required autofocus autocomplete="email"
                        placeholder="email@example.com"
                        class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-gray-900 placeholder:text-gray-400 outline-none focus:border-purple-500 focus:ring-2 focus:ring-purple-500/30"
                    >
                    @error('email')
                        <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <div class="mb-2 flex items-center justify-between">
                        <label for="password" class="block text-sm font-medium text-gray-700">
                            Password
                        </label>
                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}" class="text-sm font-medium text-purple-600 hover:text-purple-700" wire:navigate>
                                Forgot your password?
                            </a>
                        @endif
                    </div>
                    <input
                        id="password" name="password" type="password" required autocomplete="current-password"
                        placeholder="Password"
                        class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-gray-900 placeholder:text-gray-400 outline-none focus:border-purple-500 focus:ring-2 focus:ring-purple-500/30"
                    >
                    @error('password')
                        <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <label class="flex items-center gap-3 text-sm text-gray-600">
                    <input type="checkbox" name="remember" @checked(old('remember'))
                        class="h-4 w-4 rounded border-gray-300 bg-white text-purple-600 focus:ring-purple-600">
                    <span>Remember me</span>
                </label>

                <button type="submit" class="w-full rounded-xl bg-purple-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-purple-700">
                    Log in
                </button>
            </form>

            <div class="space-x-1 text-center text-sm text-gray-500 rtl:space-x-reverse">
                <span>Don't have an account?</span>
                <a href="{{ route('register') }}" class="font-medium text-purple-600 hover:text-purple-700" wire:navigate>
                    Sign up
                </a>
            </div>
        </div>
    </div>

</body>
</html>