@extends('admin.monitoring.layout')
@php($pageTitle = 'API Monitor')
@section('monitoring_content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">
    @foreach(['Top Endpoints' => $topEndpoints, 'Slow Endpoints' => $slowEndpoints, 'Failing Endpoints' => $failingEndpoints] as $title => $items)
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <h3 class="text-sm font-semibold mb-2">{{ $title }}</h3>
            <ul class="space-y-1 text-sm text-gray-700">
                @forelse($items as $item)
                    <li class="truncate">{{ $item->endpoint ?? $item['endpoint'] ?? '—' }}</li>
                @empty
                    <li class="text-gray-500">No data yet</li>
                @endforelse
            </ul>
        </div>
    @endforeach
</div>
@include('admin.monitoring.partials.log-table', ['rows' => $logs, 'columns' => [
    ['key' => 'occurred_at', 'label' => 'When'],
    ['key' => 'method', 'label' => 'Method'],
    ['key' => 'endpoint', 'label' => 'Endpoint'],
    ['key' => 'status_code', 'label' => 'Status'],
    ['key' => 'response_time_ms', 'label' => 'Time (ms)'],
]])
@endsection
