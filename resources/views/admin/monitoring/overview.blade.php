@extends('admin.monitoring.layout')

@php($pageTitle = 'Monitoring Overview')
@php($monitoringPage = 'overview')

@section('monitoring_content')
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3">
    @foreach([
        ['label' => 'Errors Today', 'value' => $stats['errors_today'] ?? 0, 'color' => 'text-red-600'],
        ['label' => 'Critical Errors', 'value' => $stats['critical_errors'] ?? 0, 'color' => 'text-orange-600'],
        ['label' => 'Failed Jobs', 'value' => $stats['failed_jobs'] ?? 0, 'color' => 'text-amber-600'],
        ['label' => 'Active Users', 'value' => $stats['active_users'] ?? 0, 'color' => 'text-emerald-600'],
        ['label' => 'Security Alerts', 'value' => $stats['security_alerts'] ?? 0, 'color' => 'text-purple-600'],
        ['label' => 'API Requests', 'value' => $stats['api_requests_today'] ?? 0, 'color' => 'text-blue-600'],
        ['label' => 'Live Quiz Takers', 'value' => $stats['live_quiz_takers'] ?? 0, 'color' => 'text-primary-600'],
        ['label' => 'Queue Pending', 'value' => $stats['queue']['pending'] ?? 0, 'color' => 'text-gray-700'],
    ] as $card)
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm" data-monitoring-stat="{{ Str::slug($card['label']) }}">
            <p class="text-xs font-medium text-gray-500">{{ $card['label'] }}</p>
            <p class="mt-1 text-2xl font-bold tabular-nums {{ $card['color'] }}">{{ number_format($card['value']) }}</p>
        </div>
    @endforeach
</div>

<div id="monitoring-charts-root" class="mt-4 space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <h2 class="text-sm font-semibold text-gray-900">Executive Charts</h2>
        <select id="monitoring-chart-period" class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm">
            <option value="24h">Last 24 Hours</option>
            <option value="7d">Last 7 Days</option>
            <option value="30d">Last 30 Days</option>
            <option value="90d">Last 90 Days</option>
        </select>
    </div>
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
        @foreach(['errorsChart'=>'Errors by Hour','requestsChart'=>'Requests by Hour','securityChart'=>'Security Events','slowQueriesChart'=>'Slow Queries','queueChart'=>'Queue Jobs','memoryChart'=>'Memory Usage','cpuChart'=>'CPU Usage','storageChart'=>'Storage Growth','attendanceChart'=>'Attendance Activity','quizChart'=>'Quiz Activity'] as $id => $title)
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $title }}</h3>
                <div class="h-48"><canvas id="{{ $id }}"></canvas></div>
            </div>
        @endforeach
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-2 gap-4 mt-4">
    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
        <h2 class="text-sm font-semibold text-gray-900 mb-3">Server Health</h2>
        @php $health = $stats['server_health'] ?? null; @endphp
        @if($health)
            <div class="grid grid-cols-2 gap-3 text-sm">
                <div><span class="text-gray-500">Status</span><p class="font-semibold capitalize">{{ $health->status }}</p></div>
                <div><span class="text-gray-500">CPU</span><p class="font-semibold">{{ $health->cpu_usage ?? '—' }}%</p></div>
                <div><span class="text-gray-500">RAM</span><p class="font-semibold">{{ $health->ram_usage ?? '—' }}%</p></div>
                <div><span class="text-gray-500">Disk</span><p class="font-semibold">{{ $health->disk_usage ?? '—' }}%</p></div>
            </div>
        @else
            <p class="text-sm text-gray-500">No health snapshot yet.</p>
        @endif
    </div>
    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
        <h2 class="text-sm font-semibold text-gray-900 mb-3">Recent Errors</h2>
        <div id="monitoring-recent-errors" class="space-y-2">
            @forelse($recentErrors as $error)
                <a href="{{ route('dashboard.monitoring.errors.show', $error) }}" class="block rounded-lg border border-gray-100 px-3 py-2 hover:bg-gray-50">
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-xs font-semibold uppercase text-red-600">{{ $error->severity }}</span>
                        <span class="text-xs text-gray-500">{{ $error->last_seen_at?->diffForHumans() }}</span>
                    </div>
                    <p class="text-sm text-gray-900 truncate">{{ $error->message }}</p>
                </a>
            @empty
                <p class="text-sm text-gray-500">No errors recorded yet.</p>
            @endforelse
        </div>
    </div>
</div>

<div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm mt-4">
    <h2 class="text-sm font-semibold text-gray-900 mb-3">Live Activity Feed</h2>
    <div id="monitoring-activity-feed" class="space-y-2 max-h-80 overflow-y-auto">
        @forelse($recentActivity as $entry)
            <div class="flex items-start gap-3 rounded-lg border border-gray-100 px-3 py-2">
                <span class="mt-0.5 h-2 w-2 rounded-full bg-primary-500"></span>
                <div class="min-w-0">
                    <p class="text-sm text-gray-900"><strong>{{ $entry->user_name ?? 'System' }}</strong> — {{ $entry->action }}</p>
                    <p class="text-xs text-gray-500">{{ $entry->occurred_at?->diffForHumans() }}</p>
                </div>
            </div>
        @empty
            <p class="text-sm text-gray-500">No activity logged yet.</p>
        @endforelse
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
@endpush
