@extends('layouts.app')

@section('title', 'Forgot password')

@section('content')
<div class="min-h-[100dvh] flex items-center justify-center px-4 py-8">
    <div class="max-w-md w-full">
        <div class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm">
            <h1 class="text-2xl font-bold text-gray-800 mb-2">Forgot password</h1>
            <p class="text-gray-600 text-sm mb-6">Enter your index number and the email saved on your account. We will send a reset link if they match. The link expires in 60 minutes.</p>
            <p class="text-amber-800 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2 text-xs mb-6">For security, you can reset your password at most 3 times in any 7-day period.</p>

            <form action="{{ route('student.password.forgot.send') }}" method="post" class="space-y-4">
                @csrf
                <div>
                    <label for="index_number" class="block text-sm font-medium text-gray-700 mb-1">Index number</label>
                    <input type="text" name="index_number" id="index_number" value="{{ old('index_number') }}" required
                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg bg-white text-gray-900 focus:ring-2 focus:ring-primary-500 focus:border-primary-500" style="text-transform: uppercase;" autocomplete="username">
                </div>
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email address</label>
                    <input type="email" name="email" id="email" value="{{ old('email') }}" required
                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg bg-white text-gray-900 focus:ring-2 focus:ring-primary-500 focus:border-primary-500" autocomplete="email">
                </div>
                <button type="submit" class="w-full py-2.5 px-4 text-sm font-semibold rounded-lg text-white bg-primary-600 hover:bg-primary-700">Send reset link</button>
            </form>
            <p class="mt-4 text-center text-sm">
                <a href="{{ route('student.account.login.form') }}" class="text-primary-600 hover:underline">Back to student login</a>
            </p>
        </div>
    </div>
</div>
@endsection
