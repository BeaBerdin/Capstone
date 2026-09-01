<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Forgot Password') }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-white flex items-center justify-center p-4">

    <div class="w-full max-w-md p-8 rounded-2xl border border-gray-200 shadow-lg">
        <div class="flex flex-col gap-6">

            <div class="text-center">
                <h1 class="text-3xl font-bold text-gray-900">
                    {{ __('Forgot Password') }}
                </h1>
                <p class="mt-2 text-sm text-gray-500">
                    {{ __('Enter your email and we will send you a reset link') }}
                </p>
            </div>

            <x-auth-session-status class="text-center text-emerald-600" :status="session('status')" />

            <form method="POST" action="{{ route('password.email') }}" class="flex flex-col gap-6">
                @csrf

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
                        autofocus
                        autocomplete="email"
                        placeholder="email@example.com"
                        class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-gray-900 placeholder:text-gray-400 outline-none focus:border-purple-500 focus:ring-2 focus:ring-purple-500/30"
                    >
                    @error('email')
                        <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" class="w-full rounded-xl bg-gray-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-gray-800">
                    {{ __('Send Reset Link') }}
                </button>
            </form>

            <div class="text-center text-sm text-gray-500">
                <a href="{{ route('login') }}" class="font-medium text-purple-600 hover:text-purple-700">
                    {{ __('Back to login') }}
                </a>
            </div>
        </div>
    </div>

</body>
</html>