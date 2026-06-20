@extends('layouts.dashboard')

@section('title', 'Dashboard')
@section('dashboard_heading', 'Dashboard')

@section('dashboard_content')
<div class="w-full space-y-8 min-w-0 overflow-x-hidden">
    @php
        $coordinator = auth()->user();
        $showSmsForUser = $coordinator && $coordinator->isCoordinator();
        $smsRemaining = $showSmsForUser ? $coordinator->sms_remaining : 0;
        $showLowSmsWarning = $showSmsForUser && $smsRemaining < 100 && $smsRemaining > 0;
    @endphp

    @if($showLowSmsWarning)
    <div id="low-sms-warning" class="rounded-lg border border-red-300 bg-red-50 p-4 flex items-start gap-3" role="alert">
        <div class="flex-shrink-0 mt-0.5">
            <svg class="w-5 h-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
        </div>
        <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-red-900">Low SMS balance</p>
            <p class="mt-1 text-sm text-red-800">You have <strong>{{ $smsRemaining }}</strong> SMS remaining. Contact your administrator to reload credits for student login messages.</p>
        </div>
        <button type="button" onclick="dismissLowSmsWarning()" class="flex-shrink-0 text-red-600 hover:text-red-800 transition-colors" aria-label="Dismiss">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>
    @endif

    <div>
        <h2 class="text-xl font-semibold text-gray-900">Overview</h2>
        <p class="mt-1 text-sm text-gray-500">Manage class groups, courses, examiners in your faculty, and the exam calendar.</p>
    </div>

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <a href="{{ route('dashboard.class-groups.index') }}" class="flex flex-col rounded-lg border border-action-200 bg-action-100 p-5 hover:bg-action-200 sm:p-6">
            <div class="flex flex-1 items-start justify-between gap-3">
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-action-800">Class groups</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums text-action-900 sm:text-3xl">{{ $stats['class_groups'] }}</p>
                </div>
                <span class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-lg bg-action-200 text-action-700">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </span>
            </div>
            <p class="mt-3 text-xs font-medium text-action-700">Manage students & examiners</p>
        </a>

        <a href="{{ route('dashboard.courses.index') }}" class="flex flex-col rounded-lg border border-primary-200 bg-primary-100 p-5 hover:bg-primary-200 sm:p-6">
            <div class="flex flex-1 items-start justify-between gap-3">
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-primary-800">Courses</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums text-primary-900 sm:text-3xl">{{ $stats['courses'] }}</p>
                </div>
                <span class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-lg bg-primary-200 text-primary-700">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                </span>
            </div>
            <p class="mt-3 text-xs font-medium text-primary-700">Create & assign lecturers</p>
        </a>

        <a href="{{ route('dashboard.exam-calendar.index') }}" class="flex flex-col rounded-lg border border-violet-200 bg-violet-100 p-5 hover:bg-violet-200 sm:p-6">
            <div class="flex flex-1 items-start justify-between gap-3">
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-violet-800">Exam calendar</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums text-violet-900 sm:text-3xl">{{ $stats['exam_calendar'] }}</p>
                </div>
                <span class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-lg bg-violet-200 text-violet-700">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </span>
            </div>
            <p class="mt-3 text-xs font-medium text-violet-700">Schedule midsem & finals</p>
        </a>

        <div class="flex flex-col rounded-lg border border-gray-200 bg-gray-50 p-5 sm:p-6">
            <div class="flex flex-1 items-start justify-between gap-3">
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-gray-700">Students</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums text-gray-900 sm:text-3xl">{{ $stats['students'] }}</p>
                </div>
                <span class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-lg bg-gray-200 text-gray-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                </span>
            </div>
            <p class="mt-3 text-xs font-medium text-gray-600">{{ $stats['examiners'] }} examiner{{ $stats['examiners'] === 1 ? '' : 's' }} in your faculty</p>
        </div>
    </div>

    <section class="rounded-lg border border-gray-200 bg-white p-4">
        <h2 class="text-xs font-semibold text-gray-700 mb-3">Quick actions</h2>
        <div class="flex flex-wrap gap-2">
            @can('create', \App\Models\ClassGroup::class)
            <a href="{{ route('dashboard.class-groups.create') }}" class="inline-flex items-center gap-1.5 rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition-colors">New class group</a>
            @endcan
            <a href="{{ route('dashboard.class-groups.index') }}" class="inline-flex items-center px-2.5 py-1.5 text-xs font-medium text-gray-800 bg-action-100 rounded border border-action-200 hover:bg-action-200">Class groups</a>
            <a href="{{ route('dashboard.courses.index') }}" class="inline-flex items-center px-2.5 py-1.5 text-xs font-medium text-gray-800 bg-primary-50 rounded border border-primary-200 hover:bg-primary-100">Courses</a>
            <a href="{{ route('dashboard.exam-calendar.index') }}" class="inline-flex items-center px-2.5 py-1.5 text-xs font-medium text-gray-600 bg-gray-50 rounded border border-gray-200 hover:bg-gray-100">Exam calendar</a>
        </div>
    </section>

    <section class="rounded-lg border border-gray-200 bg-white p-4">
        <h2 class="text-xs font-semibold text-gray-700 mb-3 flex items-center gap-2"><i class="fas fa-sitemap text-gray-400"></i> Academic structure</h2>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('dashboard.coordinators.academic-years.index') }}" class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs font-medium text-gray-600 bg-gray-50 rounded border border-gray-200 hover:bg-gray-100"><i class="fas fa-calendar-alt text-gray-400"></i> Academic years</a>
            <a href="{{ route('dashboard.coordinators.quiz-categories.index') }}" class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs font-medium text-gray-600 bg-gray-50 rounded border border-gray-200 hover:bg-gray-100"><i class="fas fa-tags text-gray-400"></i> Quiz categories</a>
            <a href="{{ route('dashboard.coordinators.semesters.index') }}" class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs font-medium text-gray-600 bg-gray-50 rounded border border-gray-200 hover:bg-gray-100"><i class="fas fa-calendar-week text-gray-400"></i> Semesters</a>
            <a href="{{ route('dashboard.coordinators.academic-classes.index') }}" class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs font-medium text-gray-600 bg-gray-50 rounded border border-gray-200 hover:bg-gray-100"><i class="fas fa-chalkboard text-gray-400"></i> Academic classes</a>
            <a href="{{ route('dashboard.coordinators.student-levels.index') }}" class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs font-medium text-gray-600 bg-gray-50 rounded border border-gray-200 hover:bg-gray-100"><i class="fas fa-layer-group text-gray-400"></i> Student levels</a>
        </div>
    </section>
</div>

@push('scripts')
<script>
(function() {
    const WARNING_KEY = 'low_sms_warning_dismissed';
    const DISMISS_HOURS = 24;

    function dismissLowSmsWarning() {
        const warning = document.getElementById('low-sms-warning');
        if (warning) {
            warning.style.display = 'none';
            localStorage.setItem(WARNING_KEY, (Date.now() + DISMISS_HOURS * 60 * 60 * 1000).toString());
        }
    }

    const warning = document.getElementById('low-sms-warning');
    if (warning) {
        const dismissed = localStorage.getItem(WARNING_KEY);
        if (dismissed && Date.now() <= parseInt(dismissed, 10)) {
            warning.style.display = 'none';
        }
    }

    window.dismissLowSmsWarning = dismissLowSmsWarning;
})();
</script>
@endpush
@endsection
