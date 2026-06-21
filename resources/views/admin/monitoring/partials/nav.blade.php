@php
    $monitoringNav = [
        ['route' => 'dashboard.monitoring.overview', 'label' => 'Overview', 'icon' => 'fa-chart-line'],
        ['route' => 'dashboard.monitoring.live-quiz.index', 'label' => 'Live Quiz', 'icon' => 'fa-play-circle'],
        ['route' => 'dashboard.monitoring.live-attendance.index', 'label' => 'Live Attendance', 'icon' => 'fa-user-check'],
        ['route' => 'dashboard.monitoring.errors.index', 'label' => 'Error Logs', 'icon' => 'fa-bug'],
        ['route' => 'dashboard.monitoring.student-activities.index', 'label' => 'Student Activities', 'icon' => 'fa-user-graduate'],
        ['route' => 'dashboard.monitoring.failed-jobs.index', 'label' => 'Failed Jobs', 'icon' => 'fa-times-circle'],
        ['route' => 'dashboard.monitoring.server-health.index', 'label' => 'Server Health', 'icon' => 'fa-server'],
        ['route' => 'dashboard.monitoring.settings.index', 'label' => 'Settings', 'icon' => 'fa-cog'],
    ];
@endphp

<div class="mb-4 rounded-xl border border-slate-700 bg-slate-900/90 p-3 shadow-sm monitoring-nav-dark">
    <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">System Monitoring</p>
            <p class="text-sm text-slate-300">Enterprise operations center</p>
        </div>
        <span id="monitoring-live-indicator" class="inline-flex items-center gap-1.5 rounded-full bg-emerald-950/60 px-2.5 py-1 text-xs font-medium text-emerald-300">
            <span class="monitoring-breathe-dot monitoring-breathe-dot--xs" aria-hidden="true"></span> Live
        </span>
    </div>
    <div class="flex flex-wrap gap-2">
        @foreach($monitoringNav as $item)
            @php
                $active = request()->routeIs($item['route']) || request()->routeIs(str_replace('.index', '.*', $item['route']));
            @endphp
            <a href="{{ route($item['route']) }}"
               class="inline-flex items-center gap-1.5 rounded-lg border px-2.5 py-1.5 text-xs font-medium transition-colors {{ $active
                    ? 'border-cyan-500/50 bg-cyan-950/50 text-cyan-200'
                    : 'border-slate-700 bg-slate-800/60 text-slate-300 hover:bg-slate-800' }}">
                <i class="fas {{ $item['icon'] }} text-[10px]"></i>
                {{ $item['label'] }}
            </a>
        @endforeach
    </div>
</div>
