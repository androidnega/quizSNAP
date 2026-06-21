@extends('admin.monitoring.layout')
@php($pageTitle = 'Command Center')
@section('monitoring_content')
<div id="command-center-root" class="space-y-4 bg-gray-900 text-white rounded-2xl p-4 md:p-6 min-h-[70vh]">
    <div class="grid grid-cols-2 md:grid-cols-4 xl:grid-cols-6 gap-3">
        @foreach([
            'critical_errors' => 'Critical Errors',
            'errors_today' => 'Errors Today',
            'active_users' => 'Live Users',
            'live_quiz_takers' => 'Quiz Takers',
            'security_alerts' => 'Security Alerts',
            'cpu' => 'CPU %',
            'ram' => 'RAM %',
            'disk' => 'Disk %',
        ] as $key => $label)
            <div class="rounded-xl border border-gray-700 bg-gray-800 p-4">
                <p class="text-xs uppercase tracking-wide text-gray-400">{{ $label }}</p>
                <p class="mt-1 text-3xl font-bold tabular-nums" data-command-center="{{ $key }}">{{ $payload[$key] ?? '—' }}</p>
            </div>
        @endforeach
    </div>
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-4 text-sm">
        <div class="rounded-xl border border-gray-700 bg-gray-800 p-4">
            <h3 class="font-semibold mb-2">Queue</h3>
            <p>Pending: {{ $payload['queue']['pending'] ?? 0 }}</p>
            <p>Failed: {{ $payload['queue']['failed'] ?? 0 }}</p>
        </div>
        <div class="rounded-xl border border-gray-700 bg-gray-800 p-4">
            <h3 class="font-semibold mb-2">WebSocket</h3>
            <p>Health: {{ $payload['websocket']['health_score'] ?? '—' }}</p>
            <p>Messages/min: {{ $payload['websocket']['messages_per_minute'] ?? 0 }}</p>
        </div>
        <div class="rounded-xl border border-gray-700 bg-gray-800 p-4">
            <h3 class="font-semibold mb-2">Attendance</h3>
            <p>Active sessions: {{ $payload['attendance']['active_sessions'] ?? 0 }}</p>
            <p>Check-ins/min: {{ $payload['attendance']['checkins_per_minute'] ?? 0 }}</p>
        </div>
    </div>
</div>
@endsection
