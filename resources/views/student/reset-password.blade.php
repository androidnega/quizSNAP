@extends('layouts.app')

@section('title', 'Reset password')

@section('content')
<div class="min-h-[100dvh] flex items-center justify-center px-4 py-8">
    <div class="max-w-md w-full">
        <div class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm">
            <h1 class="text-2xl font-bold text-gray-800 mb-2">Choose a new password</h1>
            <p class="text-gray-600 text-sm mb-6">Your new password must be at least {{ \App\Models\Student::PASSWORD_MIN_LENGTH }} characters.</p>
            @if(!empty($expiresMinutes))
                <p class="text-amber-800 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2 text-xs mb-6">This link expires {{ $expiresMinutes }} minutes after it was sent. If it has expired, request a new one from the forgot password page.</p>
            @endif

            <form action="{{ route('student.password.reset') }}" method="post" class="space-y-4">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">New password</label>
                    <input type="password" name="password" id="password" required autocomplete="new-password"
                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg bg-white text-gray-900 focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                </div>
                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Confirm password</label>
                    <input type="password" name="password_confirmation" id="password_confirmation" required autocomplete="new-password"
                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg bg-white text-gray-900 focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                </div>
                @if ($errors->any())
                    <div class="bg-red-50 border border-red-200 rounded-lg p-3 text-sm text-red-800">
                        {{ $errors->first() }}
                    </div>
                @endif
                <button type="submit" class="w-full py-2.5 px-4 text-sm font-semibold rounded-lg text-white bg-primary-600 hover:bg-primary-700">Save password</button>
            </form>
        </div>
    </div>
</div>
@endsection
