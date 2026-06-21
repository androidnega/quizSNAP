@extends('admin.monitoring.layout')
@php($pageTitle = 'WebSocket Status')
@section('monitoring_content')
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">
    <div class="rounded-xl border bg-white p-4 shadow-sm">
        <h3 class="text-sm font-semibold mb-2">Connection Config</h3>
        <div class="grid grid-cols-2 gap-2 text-sm">
            <div>Enabled: <strong>{{ ($status['enabled'] ?? false) ? 'Yes' : 'No' }}</strong></div>
            <div>Host: <strong>{{ $status['host'] ?? '—' }}</strong></div>
            <div>Port: <strong>{{ $status['port'] ?? '—' }}</strong></div>
            <div>Channel: <strong>private-quizsnap-monitoring</strong></div>
        </div>
    </div>
    <div class="rounded-xl border bg-white p-4 shadow-sm">
        <h3 class="text-sm font-semibold mb-2">Live Analytics</h3>
        <div class="grid grid-cols-2 gap-2 text-sm">
            <div>Health score: <strong>{{ $analytics['health_score'] ?? '—' }}</strong></div>
            <div>Connected users: <strong>{{ $analytics['connected_users'] ?? 0 }}</strong></div>
            <div>Messages/min: <strong>{{ $analytics['messages_per_minute'] ?? 0 }}</strong></div>
            <div>Events/min: <strong>{{ $analytics['events_per_minute'] ?? 0 }}</strong></div>
            <div>Failed broadcasts: <strong>{{ $analytics['failed_broadcasts'] ?? 0 }}</strong></div>
            <div>Avg latency: <strong>{{ $analytics['average_latency_ms'] ?? '—' }} ms</strong></div>
        </div>
    </div>
</div>
@include('admin.monitoring.partials.log-table', ['rows' => $history, 'columns' => [
    ['key' => 'recorded_at', 'label' => 'Recorded'],
    ['key' => 'health_score', 'label' => 'Health'],
    ['key' => 'messages_per_minute', 'label' => 'Msg/min'],
    ['key' => 'failed_broadcasts', 'label' => 'Failures'],
    ['key' => 'average_latency_ms', 'label' => 'Latency ms'],
]])
@endsection
