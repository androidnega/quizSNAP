@extends('layouts.dashboard')

@section('title', 'Dashboard')
@section('dashboard_heading', 'Dashboard')

@section('dashboard_content')
<div class="w-full space-y-8 min-w-0 overflow-x-hidden">
    @php
        $examiner = auth()->user();
        if (! ($examiner instanceof \App\Models\User) && session('admin_user_id')) {
            $examiner = \App\Models\User::find(session('admin_user_id'));
        }
    @endphp

    @if(isset($needsFacultyDepartment) && $needsFacultyDepartment)
    <div id="faculty-department-notice" class="rounded-2xl border border-orange-200 bg-orange-50 p-4 flex items-start gap-3" role="alert">
        <div class="flex-1 min-w-0">
            <p class="text-sm font-semibold text-orange-900">Complete your profile</p>
            <p class="mt-1 text-sm text-orange-800">Select your faculty and department. <a href="{{ route('dashboard.users.edit', ['user' => $examiner, 'complete_profile' => 1]) }}" class="font-semibold underline">Update profile</a></p>
        </div>
        <button type="button" onclick="dismissFacultyDepartmentNotice()" class="text-orange-600" aria-label="Dismiss">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>
    @endif

    <div class="qs-reveal">
        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Examiner workspace</p>
        <h2 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">Your activity</h2>
        <p class="mt-1 text-sm text-slate-500">Charts show only your quizzes — never another examiner’s data.</p>
    </div>

    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <a href="{{ route('dashboard.quizzes.index') }}" class="qs-reveal group flex items-start gap-3 rounded-2xl border border-slate-200 bg-white p-4 no-underline transition hover:border-indigo-200 hover:bg-indigo-50/40 sm:p-5">
            <span class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-xl bg-indigo-100 text-indigo-600">
                <i class="fas fa-clipboard-list text-lg" aria-hidden="true"></i>
            </span>
            <div class="min-w-0">
                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Quizzes</p>
                <p class="mt-1 text-2xl font-semibold tabular-nums tracking-tight text-slate-900 sm:text-3xl">{{ $stats['quizzes'] }}</p>
            </div>
        </a>
        <a href="{{ route('dashboard.class-groups.index') }}" class="qs-reveal group flex items-start gap-3 rounded-2xl border border-slate-200 bg-white p-4 no-underline transition hover:border-teal-200 hover:bg-teal-50/40 sm:p-5">
            <span class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-xl bg-teal-100 text-teal-600">
                <i class="fas fa-users text-lg" aria-hidden="true"></i>
            </span>
            <div class="min-w-0">
                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Class groups</p>
                <p class="mt-1 text-2xl font-semibold tabular-nums tracking-tight text-slate-900 sm:text-3xl">{{ $classGroupsCount }}</p>
            </div>
        </a>
        <div class="qs-reveal flex items-start gap-3 rounded-2xl border border-slate-200 bg-white p-4 sm:p-5">
            <span class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-xl bg-sky-100 text-sky-600">
                <i class="fas fa-play-circle text-lg" aria-hidden="true"></i>
            </span>
            <div class="min-w-0">
                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Sessions</p>
                <p class="mt-1 text-2xl font-semibold tabular-nums tracking-tight text-slate-900 sm:text-3xl">{{ $stats['sessions'] }}</p>
            </div>
        </div>
        <div class="qs-reveal flex items-start gap-3 rounded-2xl border border-slate-200 bg-white p-4 sm:p-5">
            <span class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-600">
                <i class="fas fa-chart-line text-lg" aria-hidden="true"></i>
            </span>
            <div class="min-w-0">
                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Results</p>
                <p class="mt-1 text-2xl font-semibold tabular-nums tracking-tight text-slate-900 sm:text-3xl">{{ $stats['results'] }}</p>
            </div>
        </div>
    </div>

    <section class="qs-reveal rounded-2xl border border-slate-200 bg-white p-4 sm:p-5" id="dashboard-trends-section">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-sm font-semibold text-slate-900">Your activity charts</h2>
                <p class="mt-0.5 text-xs text-slate-500">Isolated to your examiner account</p>
            </div>
            <label class="inline-flex items-center gap-2 text-xs text-slate-600">
                <span>Period</span>
                <select id="dashboard-chart-period" class="rounded-lg border border-slate-200 bg-slate-50 px-2 py-1.5 text-xs font-medium">
                    <option value="7d">Last 7 days</option>
                    <option value="30d" selected>Last 30 days</option>
                    <option value="90d">Last 90 days</option>
                </select>
            </label>
        </div>
        <ul id="dashboard-insights-list" class="mb-4 list-disc space-y-1.5 pl-4 text-xs text-slate-700"></ul>
        <div class="grid min-w-0 grid-cols-1 gap-4 lg:grid-cols-2">
            <div class="min-w-0 rounded-xl border border-slate-100 bg-slate-50/80 p-3">
                <h3 class="mb-2 text-xs font-semibold text-slate-700">Quiz sessions started</h3>
                <div class="h-48"><canvas id="chart-quiz-activity"></canvas></div>
            </div>
            <div class="min-w-0 rounded-xl border border-slate-100 bg-slate-50/80 p-3">
                <h3 class="mb-2 text-xs font-semibold text-slate-700">Exam submissions</h3>
                <div class="h-48"><canvas id="chart-exam-submissions"></canvas></div>
            </div>
            <div class="min-w-0 rounded-xl border border-slate-100 bg-slate-50/80 p-3">
                <h3 class="mb-2 text-xs font-semibold text-slate-700">Average exam scores</h3>
                <div class="h-48"><canvas id="chart-avg-scores"></canvas></div>
            </div>
            <div class="min-w-0 rounded-xl border border-slate-100 bg-slate-50/80 p-3">
                <h3 class="mb-2 text-xs font-semibold text-slate-700">Pass vs below 50%</h3>
                <div class="h-48"><canvas id="chart-quiz-outcomes"></canvas></div>
            </div>
        </div>
    </section>
</div>

@push('styles')
<style>
.qs-reveal { opacity: 0; transform: translateY(14px); transition: opacity .6s cubic-bezier(.22,1,.36,1), transform .6s cubic-bezier(.22,1,.36,1); }
.qs-reveal.is-visible { opacity: 1; transform: none; }
</style>
@endpush
@push('scripts')
<script>
(function() {
    const FACULTY_NOTICE_KEY = 'faculty_department_notice_dismissed';
    function dismissFacultyDepartmentNotice() {
        const notice = document.getElementById('faculty-department-notice');
        if (notice) {
            notice.style.display = 'none';
            localStorage.setItem(FACULTY_NOTICE_KEY, (Date.now() + 24 * 60 * 60 * 1000).toString());
        }
    }
    const facultyNotice = document.getElementById('faculty-department-notice');
    if (facultyNotice) {
        const dismissed = localStorage.getItem(FACULTY_NOTICE_KEY);
        if (dismissed && Date.now() <= parseInt(dismissed, 10)) facultyNotice.style.display = 'none';
    }
    @if(!$needsFacultyDepartment)
        localStorage.removeItem(FACULTY_NOTICE_KEY);
    @endif
    window.dismissFacultyDepartmentNotice = dismissFacultyDepartmentNotice;

    var nodes = document.querySelectorAll('.qs-reveal');
    if (nodes.length) {
        if (!('IntersectionObserver' in window)) {
            nodes.forEach(function (n) { n.classList.add('is-visible'); });
        } else {
            var io = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-visible');
                        io.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.1, rootMargin: '0px 0px -24px 0px' });
            nodes.forEach(function (n, i) {
                n.style.transitionDelay = (Math.min(i, 8) * 0.04) + 's';
                io.observe(n);
            });
        }
    }
})();
</script>
<script>window.AdminDashboardChartsConfig = { url: @json(route('dashboard.charts')), mode: 'examiner' };</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js" defer></script>
<script src="{{ asset('js/admin-dashboard-charts.js') }}?v={{ filemtime(public_path('js/admin-dashboard-charts.js')) }}" defer></script>
@endpush
@endsection
