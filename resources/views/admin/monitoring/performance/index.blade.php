@extends('admin.monitoring.layout')
@php($pageTitle = 'Performance Monitor')
@section('monitoring_content')
<div class="grid grid-cols-2 gap-3 mb-4">
    <div class="rounded-xl border bg-white p-4 shadow-sm"><p class="text-xs text-gray-500">Avg request duration (24h)</p><p class="text-xl font-bold">{{ $avgDuration ?? 0 }}ms</p></div>
    <div class="rounded-xl border bg-white p-4 shadow-sm"><p class="text-xs text-gray-500">Avg memory (24h)</p><p class="text-xl font-bold">{{ number_format($avgMemory ?? 0) }} KB</p></div>
</div>
@include('admin.monitoring.partials.log-table', ['rows' => $logs, 'columns' => [
    ['key' => 'occurred_at', 'label' => 'When'],
    ['key' => 'route', 'label' => 'Route'],
    ['key' => 'request_duration_ms', 'label' => 'Duration (ms)'],
    ['key' => 'memory_usage_kb', 'label' => 'Memory (KB)'],
    ['key' => 'query_time_ms', 'label' => 'Query (ms)'],
]])
@endsection
