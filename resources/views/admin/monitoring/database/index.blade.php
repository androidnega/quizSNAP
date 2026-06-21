@extends('admin.monitoring.layout')
@php($pageTitle = 'Database Monitor')
@section('monitoring_content')
<div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
    <div class="rounded-xl border bg-white p-4 shadow-sm"><p class="text-xs text-gray-500">Avg query time</p><p class="text-xl font-bold">{{ $stats['avg_time'] ?? 0 }}ms</p></div>
    <div class="rounded-xl border bg-white p-4 shadow-sm"><p class="text-xs text-gray-500">Peak query time</p><p class="text-xl font-bold">{{ $stats['peak_time'] ?? 0 }}ms</p></div>
    <div class="rounded-xl border bg-white p-4 shadow-sm"><p class="text-xs text-gray-500">Slow queries (24h)</p><p class="text-xl font-bold">{{ $stats['slow_count'] ?? 0 }}</p></div>
    <div class="rounded-xl border bg-white p-4 shadow-sm"><p class="text-xs text-gray-500">Failed queries (24h)</p><p class="text-xl font-bold">{{ $stats['failed_count'] ?? 0 }}</p></div>
</div>
@include('admin.monitoring.partials.log-table', ['rows' => $logs, 'columns' => [
    ['key' => 'occurred_at', 'label' => 'When'],
    ['key' => 'status', 'label' => 'Status'],
    ['key' => 'execution_time_ms', 'label' => 'Time (ms)'],
    ['key' => 'route', 'label' => 'Route'],
]])
@endsection
