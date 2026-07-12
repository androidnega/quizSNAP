@extends('layouts.dashboard')

@section('title', 'Dashboard')
@section('dashboard_heading', 'Dashboard')

@section('dashboard_content')
<div class="w-full space-y-8 min-w-0 overflow-x-hidden">
    @php
        $coordinator = auth()->user();
        if (! ($coordinator instanceof \App\Models\User) && session('admin_user_id')) {
            $coordinator = \App\Models\User::find(session('admin_user_id'));
        }
        $showSmsForUser = $coordinator && $coordinator->isCoordinator();
        $smsRemaining = $showSmsForUser ? $coordinator->sms_remaining : 0;
        $showLowSmsWarning = $showSmsForUser && $smsRemaining < 100 && $smsRemaining > 0;
        $classGroups = $classGroups ?? collect();
    @endphp

    @if($showLowSmsWarning)
    <div id="low-sms-warning" class="rounded-2xl border border-rose-200 bg-rose-50 p-4 flex items-start gap-3" role="alert">
        <div class="flex-1 min-w-0">
            <p class="text-sm font-semibold text-rose-900">Low SMS balance</p>
            <p class="mt-1 text-sm text-rose-800">You have <strong>{{ $smsRemaining }}</strong> SMS remaining. Contact your administrator to reload credits.</p>
        </div>
        <button type="button" onclick="dismissLowSmsWarning()" class="text-rose-600 hover:text-rose-800" aria-label="Dismiss">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>
    @endif

    <div class="qs-reveal">
        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Faculty overview</p>
        <h2 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">Your academic workspace</h2>
        <p class="mt-1 text-sm text-slate-500">Class groups, courses, and students stay inside your faculty only.</p>
    </div>

    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <a href="{{ route('dashboard.class-groups.index') }}" class="qs-reveal rounded-2xl border border-sky-200 bg-sky-50 p-5 no-underline transition hover:-translate-y-0.5 hover:border-sky-300">
            <p class="text-xs font-semibold uppercase tracking-wide text-sky-700">Class groups</p>
            <p class="mt-2 text-3xl font-semibold tabular-nums text-sky-950">{{ $stats['class_groups'] }}</p>
        </a>
        <a href="{{ route('dashboard.courses.index') }}" class="qs-reveal rounded-2xl border border-teal-200 bg-teal-50 p-5 no-underline transition hover:-translate-y-0.5 hover:border-teal-300">
            <p class="text-xs font-semibold uppercase tracking-wide text-teal-700">Courses</p>
            <p class="mt-2 text-3xl font-semibold tabular-nums text-teal-950">{{ $stats['courses'] }}</p>
        </a>
        <a href="{{ route('dashboard.exam-calendar.index') }}" class="qs-reveal rounded-2xl border border-violet-200 bg-violet-50 p-5 no-underline transition hover:-translate-y-0.5 hover:border-violet-300">
            <p class="text-xs font-semibold uppercase tracking-wide text-violet-700">Exam calendar</p>
            <p class="mt-2 text-3xl font-semibold tabular-nums text-violet-950">{{ $stats['exam_calendar'] }}</p>
        </a>
        <a href="{{ route('dashboard.students.index') }}" class="qs-reveal rounded-2xl border border-amber-200 bg-amber-50 p-5 no-underline transition hover:-translate-y-0.5 hover:border-amber-300">
            <p class="text-xs font-semibold uppercase tracking-wide text-amber-800">Students</p>
            <p class="mt-2 text-3xl font-semibold tabular-nums text-amber-950">{{ $stats['students'] }}</p>
        </a>
    </div>

    <section class="space-y-3">
        <div class="qs-reveal flex flex-wrap items-end justify-between gap-3">
            <div>
                <h2 class="text-sm font-semibold text-slate-900">Class groups</h2>
                <p class="text-xs text-slate-500">Scroll to reveal — no pagination</p>
            </div>
            @can('create', \App\Models\ClassGroup::class)
            <a href="{{ route('dashboard.class-groups.create') }}" class="inline-flex items-center rounded-xl bg-slate-900 px-3.5 py-2 text-xs font-semibold text-white hover:bg-slate-800">New class group</a>
            @endcan
        </div>
        @if($classGroups->isNotEmpty())
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                @foreach($classGroups as $g)
                    @include('admin.class-groups.partials.group-card', ['g' => $g])
                @endforeach
            </div>
        @else
            <div class="qs-reveal rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-500">
                No class groups in your faculty yet.
            </div>
        @endif
    </section>

    <section class="qs-reveal rounded-2xl border border-slate-200 bg-white p-4">
        <h2 class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-3">Academic structure</h2>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('dashboard.coordinators.academic-years.index') }}" class="rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-100">Academic years</a>
            <a href="{{ route('dashboard.coordinators.quiz-categories.index') }}" class="rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-100">Quiz categories</a>
            <a href="{{ route('dashboard.coordinators.semesters.index') }}" class="rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-100">Semesters</a>
            <a href="{{ route('dashboard.coordinators.academic-classes.index') }}" class="rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-100">Academic classes</a>
            <a href="{{ route('dashboard.coordinators.student-levels.index') }}" class="rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-100">Student levels</a>
        </div>
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
    const WARNING_KEY = 'low_sms_warning_dismissed';
    function dismissLowSmsWarning() {
        const warning = document.getElementById('low-sms-warning');
        if (warning) {
            warning.style.display = 'none';
            localStorage.setItem(WARNING_KEY, (Date.now() + 24 * 60 * 60 * 1000).toString());
        }
    }
    const warning = document.getElementById('low-sms-warning');
    if (warning) {
        const dismissed = localStorage.getItem(WARNING_KEY);
        if (dismissed && Date.now() <= parseInt(dismissed, 10)) warning.style.display = 'none';
    }
    window.dismissLowSmsWarning = dismissLowSmsWarning;

    var nodes = document.querySelectorAll('.qs-reveal');
    if (!nodes.length) return;
    if (!('IntersectionObserver' in window)) {
        nodes.forEach(function (n) { n.classList.add('is-visible'); });
        return;
    }
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
})();
</script>
@endpush
@endsection
