@extends('layouts.dashboard')

@section('title', 'Dashboard')
@section('dashboard_heading', 'Dashboard')

@section('dashboard_content')
<div class="coord-dash w-full min-w-0 space-y-10 overflow-x-hidden">
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
    <div id="low-sms-warning" class="flex items-start gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3" role="alert">
        <div class="min-w-0 flex-1">
            <p class="text-sm font-medium text-slate-900">Low SMS balance</p>
            <p class="mt-0.5 text-sm text-slate-600">You have <strong class="font-semibold text-slate-900">{{ $smsRemaining }}</strong> SMS remaining. Contact your administrator to reload credits.</p>
        </div>
        <button type="button" onclick="dismissLowSmsWarning()" class="text-slate-400 hover:text-slate-700" aria-label="Dismiss">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>
    @endif

    <header class="coord-reveal">
        <p class="text-[11px] font-medium uppercase tracking-[0.16em] text-slate-400">Overview</p>
        <h2 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">Faculty workspace</h2>
        <p class="mt-1 max-w-xl text-sm text-slate-500">Class groups, courses, and students stay in your faculty. Academic years remain shared across the institution.</p>
    </header>

    <div class="grid grid-cols-2 gap-px overflow-hidden rounded-xl border border-slate-200 bg-slate-200 lg:grid-cols-4">
        <a href="{{ route('dashboard.class-groups.index') }}" class="coord-reveal bg-white px-5 py-5 no-underline transition hover:bg-slate-50">
            <p class="text-[11px] font-medium uppercase tracking-[0.12em] text-slate-400">Class groups</p>
            <p class="mt-2 text-3xl font-semibold tabular-nums tracking-tight text-slate-900">{{ $stats['class_groups'] }}</p>
        </a>
        <a href="{{ route('dashboard.courses.index') }}" class="coord-reveal bg-white px-5 py-5 no-underline transition hover:bg-slate-50">
            <p class="text-[11px] font-medium uppercase tracking-[0.12em] text-slate-400">Courses</p>
            <p class="mt-2 text-3xl font-semibold tabular-nums tracking-tight text-slate-900">{{ $stats['courses'] }}</p>
        </a>
        <a href="{{ route('dashboard.exam-calendar.index') }}" class="coord-reveal bg-white px-5 py-5 no-underline transition hover:bg-slate-50">
            <p class="text-[11px] font-medium uppercase tracking-[0.12em] text-slate-400">Exam calendar</p>
            <p class="mt-2 text-3xl font-semibold tabular-nums tracking-tight text-slate-900">{{ $stats['exam_calendar'] }}</p>
        </a>
        <a href="{{ route('dashboard.students.index') }}" class="coord-reveal bg-white px-5 py-5 no-underline transition hover:bg-slate-50">
            <p class="text-[11px] font-medium uppercase tracking-[0.12em] text-slate-400">Students</p>
            <p class="mt-2 text-3xl font-semibold tabular-nums tracking-tight text-slate-900">{{ $stats['students'] }}</p>
        </a>
    </div>

    <section class="space-y-4">
        <div class="coord-reveal flex flex-wrap items-end justify-between gap-3">
            <div>
                <h2 class="text-sm font-semibold tracking-tight text-slate-900">Class groups</h2>
                <p class="mt-0.5 text-xs text-slate-500">{{ $classGroups->count() }} group{{ $classGroups->count() === 1 ? '' : 's' }} · scroll to reveal</p>
            </div>
            @can('create', \App\Models\ClassGroup::class)
            <a href="{{ route('dashboard.class-groups.create') }}" class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-800 transition hover:border-slate-300 hover:bg-slate-50">New class group</a>
            @endcan
        </div>

        @if($classGroups->isNotEmpty())
            <div class="coord-groups overflow-visible">
                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach($classGroups as $g)
                        @include('admin.class-groups.partials.group-card', ['g' => $g, 'variant' => 'coordinator'])
                    @endforeach
                </div>
            </div>
        @else
            <div class="coord-reveal rounded-xl border border-dashed border-slate-200 bg-white px-6 py-10 text-center text-sm text-slate-500">
                No class groups in your faculty yet.
            </div>
        @endif
    </section>

    <section class="coord-reveal border-t border-slate-200 pt-6">
        <h2 class="text-[11px] font-medium uppercase tracking-[0.14em] text-slate-400">Academic structure</h2>
        <div class="mt-3 flex flex-wrap gap-2">
            <a href="{{ route('dashboard.coordinators.academic-years.index') }}" class="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-700 transition hover:bg-slate-50">Academic years</a>
            <a href="{{ route('dashboard.coordinators.quiz-categories.index') }}" class="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-700 transition hover:bg-slate-50">Quiz categories</a>
            <a href="{{ route('dashboard.coordinators.semesters.index') }}" class="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-700 transition hover:bg-slate-50">Semesters</a>
            <a href="{{ route('dashboard.coordinators.academic-classes.index') }}" class="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-700 transition hover:bg-slate-50">Academic classes</a>
            <a href="{{ route('dashboard.coordinators.student-levels.index') }}" class="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-700 transition hover:bg-slate-50">Student levels</a>
        </div>
    </section>
</div>

@push('styles')
<style>
.coord-dash .coord-groups {
    overflow: visible;
    max-height: none;
}
.coord-reveal {
    opacity: 0;
    transform: translateY(12px);
    transition: opacity .7s cubic-bezier(.22, 1, .36, 1), transform .7s cubic-bezier(.22, 1, .36, 1);
    will-change: opacity, transform;
}
.coord-reveal.is-visible {
    opacity: 1;
    transform: none;
}
@media (prefers-reduced-motion: reduce) {
    .coord-reveal { opacity: 1; transform: none; transition: none; }
}
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

    var nodes = document.querySelectorAll('.coord-reveal');
    if (!nodes.length) return;
    if (!('IntersectionObserver' in window) || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        nodes.forEach(function (n) { n.classList.add('is-visible'); });
        return;
    }
    var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (!entry.isIntersecting) return;
            entry.target.classList.add('is-visible');
            io.unobserve(entry.target);
        });
    }, { threshold: 0.08, rootMargin: '0px 0px -8% 0px' });
    nodes.forEach(function (n, i) {
        n.style.transitionDelay = (Math.min(i, 14) * 0.045) + 's';
        io.observe(n);
    });
})();
</script>
@endpush
@endsection
