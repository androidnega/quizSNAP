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
        $classGroups = $classGroups ?? collect();
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
        <h2 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">Your quizzes &amp; classes</h2>
        <p class="mt-1 text-sm text-slate-500">Charts and groups below show only your own data.</p>
    </div>

    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <a href="{{ route('dashboard.quizzes.index') }}" class="qs-reveal rounded-2xl border border-indigo-200 bg-indigo-50 p-5 no-underline transition hover:-translate-y-0.5">
            <p class="text-xs font-semibold uppercase tracking-wide text-indigo-700">Quizzes</p>
            <p class="mt-2 text-3xl font-semibold tabular-nums text-indigo-950">{{ $stats['quizzes'] }}</p>
        </a>
        <a href="{{ route('dashboard.class-groups.index') }}" class="qs-reveal rounded-2xl border border-teal-200 bg-teal-50 p-5 no-underline transition hover:-translate-y-0.5">
            <p class="text-xs font-semibold uppercase tracking-wide text-teal-700">Class groups</p>
            <p class="mt-2 text-3xl font-semibold tabular-nums text-teal-950">{{ $classGroupsCount }}</p>
        </a>
        <div class="qs-reveal rounded-2xl border border-sky-200 bg-sky-50 p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-sky-700">Sessions</p>
            <p class="mt-2 text-3xl font-semibold tabular-nums text-sky-950">{{ $stats['sessions'] }}</p>
        </div>
        <div class="qs-reveal rounded-2xl border border-amber-200 bg-amber-50 p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-amber-800">Results</p>
            <p class="mt-2 text-3xl font-semibold tabular-nums text-amber-950">{{ $stats['results'] }}</p>
        </div>
    </div>

    <section class="qs-reveal rounded-2xl border border-slate-200 bg-white p-4 sm:p-5" id="dashboard-trends-section">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
            <div>
                <h2 class="text-sm font-semibold text-slate-900">Your activity charts</h2>
                <p class="text-xs text-slate-500 mt-0.5">Only your quizzes — not shared with other examiners</p>
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
        <ul id="dashboard-insights-list" class="mb-4 space-y-1.5 text-xs text-slate-700 list-disc pl-4"></ul>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 min-w-0">
            <div class="rounded-xl border border-slate-100 bg-slate-50 p-3 min-w-0">
                <h3 class="text-xs font-semibold text-slate-700 mb-2">Quiz sessions started</h3>
                <div class="h-44"><canvas id="chart-quiz-activity"></canvas></div>
            </div>
            <div class="rounded-xl border border-slate-100 bg-slate-50 p-3 min-w-0">
                <h3 class="text-xs font-semibold text-slate-700 mb-2">Exam submissions</h3>
                <div class="h-44"><canvas id="chart-exam-submissions"></canvas></div>
            </div>
            <div class="rounded-xl border border-slate-100 bg-slate-50 p-3 min-w-0">
                <h3 class="text-xs font-semibold text-slate-700 mb-2">Average exam scores</h3>
                <div class="h-44"><canvas id="chart-avg-scores"></canvas></div>
            </div>
            <div class="rounded-xl border border-slate-100 bg-slate-50 p-3 min-w-0">
                <h3 class="text-xs font-semibold text-slate-700 mb-2">Pass vs below 50%</h3>
                <div class="h-44"><canvas id="chart-quiz-outcomes"></canvas></div>
            </div>
        </div>
    </section>

    <section class="space-y-3">
        <div class="qs-reveal flex flex-wrap items-end justify-between gap-3">
            <div>
                <h2 class="text-sm font-semibold text-slate-900">Your class groups</h2>
                <p class="text-xs text-slate-500">Assigned courses only</p>
            </div>
            <a href="{{ route('dashboard.quizzes.create') }}" class="inline-flex items-center rounded-xl bg-indigo-600 px-3.5 py-2 text-xs font-semibold text-white hover:bg-indigo-700">Create quiz</a>
        </div>
        @if($classGroups->isNotEmpty())
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                @foreach($classGroups as $g)
                    @include('admin.class-groups.partials.group-card', ['g' => $g])
                @endforeach
            </div>
        @else
            <div class="qs-reveal rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-500">
                No class groups assigned to you yet.
            </div>
        @endif
    </section>
</div>

@push('styles')
<style>
.qs-reveal { opacity: 0; transform: translateY(18px); transition: opacity .55s ease, transform .55s ease; }
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
            }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });
            nodes.forEach(function (n, i) {
                n.style.transitionDelay = (Math.min(i, 10) * 0.04) + 's';
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
