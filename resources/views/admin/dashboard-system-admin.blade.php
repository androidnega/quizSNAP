@extends('layouts.dashboard')

@section('title', 'System Monitor Dashboard')
@section('dashboard_heading', 'System Monitor Dashboard')

@section('dashboard_content')
<div class="w-full min-w-0 space-y-6">
    <div class="min-w-0">
        <p class="text-sm sm:text-base text-gray-600">Monitor platform health, live exams, and academic intelligence from the enterprise centers below.</p>
    </div>

    @if(session('error'))
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
    @endif

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 min-w-0">
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm min-w-0">
            <p class="text-xs font-medium text-gray-500">Live on site</p>
            <p class="mt-0.5 text-2xl font-bold tabular-nums text-gray-900">{{ (int) ($stats['live_visitors'] ?? 0) }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm min-w-0">
            <p class="text-xs font-medium text-gray-500">Writing quiz now</p>
            <p class="mt-0.5 text-2xl font-bold tabular-nums text-gray-900">{{ (int) ($stats['live_quiz_takers'] ?? 0) }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm min-w-0">
            <p class="text-xs font-medium text-gray-500">Errors today</p>
            <p class="mt-0.5 text-2xl font-bold tabular-nums text-gray-900">{{ (int) ($stats['errors_today'] ?? 0) }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm min-w-0">
            <p class="text-xs font-medium text-gray-500">Critical open errors</p>
            <p class="mt-0.5 text-2xl font-bold tabular-nums text-gray-900">{{ (int) ($stats['critical_errors'] ?? 0) }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 min-w-0">
        <a href="{{ route('dashboard.monitoring.overview') }}" class="group rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:border-primary-300 hover:shadow-md min-w-0">
            <div class="flex items-start gap-3">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-sky-50 text-sky-700">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                </span>
                <div class="min-w-0">
                    <h2 class="text-base font-semibold text-gray-900 group-hover:text-primary-700">Monitoring Center</h2>
                    <p class="mt-1 text-sm text-gray-600">Errors, activity logs, queue health, server metrics, and security events.</p>
                </div>
            </div>
        </a>

        <a href="{{ route('dashboard.operations.index') }}" class="group rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:border-primary-300 hover:shadow-md min-w-0">
            <div class="flex items-start gap-3">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-violet-50 text-violet-700">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                </span>
                <div class="min-w-0">
                    <h2 class="text-base font-semibold text-gray-900 group-hover:text-primary-700">Operations Center</h2>
                    <p class="mt-1 text-sm text-gray-600">Live exams, proctoring, attendance, incidents, and exam analytics.</p>
                </div>
            </div>
        </a>

        <a href="{{ route('dashboard.intelligence.index') }}" class="group rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:border-primary-300 hover:shadow-md min-w-0">
            <div class="flex items-start gap-3">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-amber-50 text-amber-700">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                </span>
                <div class="min-w-0">
                    <h2 class="text-base font-semibold text-gray-900 group-hover:text-primary-700">Intelligence Center</h2>
                    <p class="mt-1 text-sm text-gray-600">Academic analytics, risk signals, predictive insights, and reports.</p>
                </div>
            </div>
        </a>
    </div>
</div>
@endsection
