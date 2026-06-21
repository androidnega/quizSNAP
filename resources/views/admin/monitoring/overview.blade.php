@extends('admin.monitoring.layout')

@php($pageTitle = 'Monitoring Overview')
@php($monitoringPage = 'overview')

@section('monitoring_content')
<div class="monitoring-command-center -mx-4 sm:-mx-6 lg:-mx-8 px-4 sm:px-6 lg:px-8 py-5 rounded-2xl">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div>
            <div class="flex items-center gap-2">
                <span class="monitoring-breathe-dot" aria-hidden="true"></span>
                <h2 class="text-lg font-semibold text-slate-100 tracking-tight">Command Center</h2>
            </div>
            <p class="mt-1 text-sm text-slate-400">Live system metrics · auto-refreshes every 15s</p>
        </div>
        <div class="flex items-center gap-2 text-xs text-slate-400">
            <span class="monitoring-breathe-dot monitoring-breathe-dot--sm" aria-hidden="true"></span>
            <span id="monitoring-last-sync">Syncing…</span>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3">
        @foreach([
            ['label' => 'Errors Today', 'value' => $stats['errors_today'] ?? 0, 'accent' => 'rose', 'live' => true],
            ['label' => 'Critical Errors', 'value' => $stats['critical_errors'] ?? 0, 'accent' => 'orange', 'live' => true],
            ['label' => 'Failed Jobs', 'value' => $stats['failed_jobs'] ?? 0, 'accent' => 'amber', 'live' => true],
            ['label' => 'Active Users', 'value' => $stats['active_users'] ?? 0, 'accent' => 'emerald', 'live' => true],
            ['label' => 'Security Alerts', 'value' => $stats['security_alerts'] ?? 0, 'accent' => 'violet', 'live' => true],
            ['label' => 'API Requests', 'value' => $stats['api_requests_today'] ?? 0, 'accent' => 'sky', 'live' => true],
            ['label' => 'Live Quiz Takers', 'value' => $stats['live_quiz_takers'] ?? 0, 'accent' => 'cyan', 'live' => true],
            ['label' => 'Queue Pending', 'value' => $stats['queue']['pending'] ?? 0, 'accent' => 'slate', 'live' => true],
        ] as $card)
            <div class="monitoring-stat-card monitoring-stat-card--{{ $card['accent'] }}" data-monitoring-stat="{{ Str::slug($card['label']) }}">
                <div class="flex items-center justify-between gap-2">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-400">{{ $card['label'] }}</p>
                    @if($card['live'])
                        <span class="monitoring-breathe-dot monitoring-breathe-dot--xs monitoring-breathe-dot--{{ $card['accent'] }}" aria-hidden="true"></span>
                    @endif
                </div>
                <p class="mt-2 text-3xl font-bold tabular-nums text-slate-50" data-monitoring-stat-value>{{ number_format($card['value']) }}</p>
            </div>
        @endforeach
    </div>

    <div id="monitoring-charts-root" class="mt-6 space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h2 class="text-sm font-semibold text-slate-200">Executive Charts</h2>
            <select id="monitoring-chart-period" class="rounded-lg border border-slate-600 bg-slate-800 px-3 py-1.5 text-sm text-slate-200">
                <option value="24h">Last 24 Hours</option>
                <option value="7d">Last 7 Days</option>
                <option value="30d">Last 30 Days</option>
                <option value="90d">Last 90 Days</option>
            </select>
        </div>
        <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
            @foreach(['errorsChart'=>'Errors by Hour','requestsChart'=>'Requests by Hour','securityChart'=>'Security Events','slowQueriesChart'=>'Slow Queries','queueChart'=>'Queue Jobs','memoryChart'=>'Memory Usage','cpuChart'=>'CPU Usage','storageChart'=>'Storage Growth','attendanceChart'=>'Attendance Activity','quizChart'=>'Quiz Activity'] as $id => $title)
                <div class="monitoring-panel">
                    <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-400">{{ $title }}</h3>
                    <div class="h-48"><canvas id="{{ $id }}"></canvas></div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-4 mt-6">
        <div class="monitoring-panel">
            <div class="flex items-center gap-2 mb-4">
                <span class="monitoring-breathe-dot monitoring-breathe-dot--emerald" aria-hidden="true"></span>
                <h2 class="text-sm font-semibold text-slate-200">Server Health</h2>
            </div>
            @if($stats['server_health'] ?? null)
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="text-slate-400">Status</span>
                        <p class="font-semibold capitalize text-slate-100">{{ $stats['server_health']->status }}</p>
                    </div>
                    @foreach(['cpu_usage' => 'CPU', 'ram_usage' => 'RAM', 'disk_usage' => 'Disk'] as $key => $label)
                        <div>
                            <div class="flex justify-between text-slate-400 mb-1">
                                <span>{{ $label }}</span>
                                <span class="text-slate-200">{{ $stats['server_health']->{$key} ?? '—' }}%</span>
                            </div>
                            <div class="h-1.5 rounded-full bg-slate-700 overflow-hidden">
                                <div class="h-full rounded-full bg-gradient-to-r from-emerald-500 to-cyan-400 transition-all duration-700" style="width: {{ min(100, (float) ($stats['server_health']->{$key} ?? 0)) }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-slate-500">No health snapshot yet.</p>
            @endif
        </div>

        <div class="monitoring-panel">
            <div class="flex items-center justify-between gap-2 mb-4">
                <div class="flex items-center gap-2">
                    <span class="monitoring-breathe-dot monitoring-breathe-dot--rose" aria-hidden="true"></span>
                    <h2 class="text-sm font-semibold text-slate-200">Recent Errors</h2>
                </div>
                <a href="{{ route('dashboard.monitoring.errors.index') }}" class="text-xs text-cyan-400 hover:text-cyan-300">View all</a>
            </div>
            <div id="monitoring-recent-errors" class="space-y-2 max-h-64 overflow-y-auto monitoring-scroll">
                @forelse($recentErrors as $error)
                    <a href="{{ route('dashboard.monitoring.errors.show', $error) }}" class="block rounded-lg border border-slate-700/80 bg-slate-800/50 px-3 py-2 hover:border-rose-500/40 hover:bg-slate-800 transition-colors">
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-xs font-semibold uppercase text-rose-400">{{ $error->severity }}</span>
                            <span class="text-xs text-slate-500">{{ $error->last_seen_at?->diffForHumans() }}</span>
                        </div>
                        <p class="text-sm text-slate-200 truncate">{{ $error->message }}</p>
                    </a>
                @empty
                    <p class="text-sm text-slate-500">No errors recorded yet.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="monitoring-panel mt-6">
        <div class="flex items-center gap-2 mb-4">
            <span class="monitoring-breathe-dot monitoring-breathe-dot--cyan" aria-hidden="true"></span>
            <h2 class="text-sm font-semibold text-slate-200">Live Activity Feed</h2>
        </div>
        <div id="monitoring-activity-feed" class="space-y-2 max-h-80 overflow-y-auto monitoring-scroll">
            @forelse($recentActivity as $entry)
                <div class="flex items-start gap-3 rounded-lg border border-slate-700/60 bg-slate-800/40 px-3 py-2">
                    <span class="monitoring-breathe-dot monitoring-breathe-dot--xs monitoring-breathe-dot--cyan mt-1.5 shrink-0" aria-hidden="true"></span>
                    <div class="min-w-0">
                        <p class="text-sm text-slate-200"><strong class="text-slate-100">{{ $entry->user_name ?? 'System' }}</strong> — {{ $entry->action }}</p>
                        <p class="text-xs text-slate-500">{{ $entry->occurred_at?->diffForHumans() }}</p>
                    </div>
                </div>
            @empty
                <p class="text-sm text-slate-500">No activity logged yet.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
@endpush
