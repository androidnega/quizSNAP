@extends('layouts.app')

@section('title', 'Staff Login')

@section('body_class', 'bg-offwhite')

@section('content')
@php
    $institutionName = trim((string) \App\Models\Setting::getValue(\App\Models\Setting::KEY_INSTITUTION_NAME, ''));
    $institutionLogo = trim((string) \App\Models\Setting::getValue(\App\Models\Setting::KEY_INSTITUTION_LOGO, ''));
    $appName = trim((string) \App\Models\Setting::getValue(\App\Models\Setting::KEY_APP_NAME, 'QuizSnap')) ?: 'QuizSnap';
@endphp
<div class="min-h-[100dvh] min-h-screen flex items-center justify-center px-4 py-6">
    <div class="w-full max-w-[400px]">
        <div class="bg-white border border-gray-200 rounded-3xl shadow-sm">
            <div class="px-6 pt-6 pb-1 text-center">
                @if($institutionLogo !== '')
                    <img
                        src="{{ $institutionLogo }}"
                        alt="{{ $institutionName !== '' ? $institutionName : $appName }}"
                        class="h-9 w-auto mx-auto mb-3 object-contain"
                    >
                @endif
                <h1 class="text-xl font-semibold text-gray-900 tracking-tight">Staff sign in</h1>
                <p class="mt-1 text-xs text-gray-500">
                    @if($institutionName !== '')
                        {{ $institutionName }} · {{ $appName }}
                    @else
                        Sign in to your {{ $appName }} account
                    @endif
                </p>
            </div>

            <div class="px-6 py-5">
                @if(session('error'))
                    <div class="mb-4 rounded-2xl border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800" role="alert">
                        {{ session('error') }}
                    </div>
                @endif
                @if(session('info'))
                    <div class="mb-4 rounded-2xl border border-blue-200 bg-blue-50 px-3 py-2 text-sm text-blue-800" role="status">
                        {{ session('info') }}
                    </div>
                @endif

                <form action="{{ route('login.post') }}" method="post" class="space-y-3.5">
                    @csrf
                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                        <input
                            type="text"
                            name="username"
                            id="username"
                            value="{{ old('username') }}"
                            required
                            autofocus
                            autocomplete="username"
                            placeholder="Enter your username"
                            class="w-full px-3 py-2 text-sm text-gray-900 placeholder-gray-400 bg-white border border-gray-300 rounded-2xl focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 @error('username') border-danger-500 focus:ring-danger-500 @enderror"
                        >
                        @error('username')
                            <p class="text-xs text-danger-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                        <input
                            type="password"
                            name="password"
                            id="password"
                            required
                            autocomplete="current-password"
                            placeholder="Enter your password"
                            class="w-full px-3 py-2 text-sm text-gray-900 placeholder-gray-400 bg-white border border-gray-300 rounded-2xl focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 @error('password') border-danger-500 focus:ring-danger-500 @enderror"
                        >
                        @error('password')
                            <p class="text-xs text-danger-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center justify-between gap-3">
                        <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                            <input
                                type="checkbox"
                                name="remember"
                                value="1"
                                class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                            >
                            <span class="text-sm text-gray-600">Remember me</span>
                        </label>
                        <a href="{{ route('password.forgot') }}" class="text-sm font-medium text-primary-600 hover:text-primary-700">
                            Forgot password?
                        </a>
                    </div>

                    <button
                        type="submit"
                        class="w-full py-2 px-4 text-sm font-semibold rounded-2xl text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition-colors"
                    >
                        Sign in
                    </button>
                </form>
            </div>

            <div class="px-6 py-2.5 text-center">
                <a href="{{ route('student.landing') }}" class="text-xs text-gray-500 hover:text-primary-600 transition-colors">
                    Student portal
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
