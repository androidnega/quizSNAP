@extends('admin.monitoring.layout')
@php($pageTitle = 'Monitoring Settings')
@section('monitoring_content')
<div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
    <form method="post" action="{{ route('dashboard.monitoring.settings.update') }}" class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm max-w-xl">
        @csrf
        <h2 class="text-sm font-semibold text-gray-900 mb-4">Alert thresholds</h2>
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Slow query threshold (ms)</label>
                <input type="number" name="slow_query_threshold_ms" value="{{ old('slow_query_threshold_ms', $settings['slow_query_threshold_ms']) }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Log retention (days)</label>
                <input type="number" name="retention_days" value="{{ old('retention_days', $settings['retention_days']) }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">CPU alert threshold (%)</label>
                <input type="number" name="alert_cpu_threshold" value="{{ old('alert_cpu_threshold', $settings['alert_cpu_threshold']) }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Memory alert threshold (%)</label>
                <input type="number" name="alert_memory_threshold" value="{{ old('alert_memory_threshold', $settings['alert_memory_threshold']) }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            </div>
            <button type="submit" class="btn btn-primary">Save settings</button>
        </div>
    </form>

    <div class="rounded-xl border border-red-200 bg-red-50/40 p-4 shadow-sm">
        <h2 class="text-sm font-semibold text-red-900 mb-1">Clear monitoring data</h2>
        <p class="text-xs text-red-800 mb-4">Permanently delete stored logs and monitoring history. This cannot be undone.</p>
        <div class="space-y-2">
            @foreach($logCategories as $key => $label)
                <form method="post" action="{{ route('dashboard.monitoring.maintenance.clear-logs') }}" class="flex items-center justify-between gap-2 rounded-lg border border-red-100 bg-white px-3 py-2" onsubmit="return confirm('Clear all {{ strtolower($label) }}? This cannot be undone.');">
                    @csrf
                    <input type="hidden" name="category" value="{{ $key }}">
                    <input type="hidden" name="confirm" value="1">
                    <span class="text-sm text-gray-800">{{ $label }}</span>
                    <button type="submit" class="text-xs font-medium text-red-700 hover:underline">Clear</button>
                </form>
            @endforeach
            <form method="post" action="{{ route('dashboard.monitoring.maintenance.clear-logs') }}" class="mt-3 rounded-lg border border-red-300 bg-red-100 px-3 py-3" onsubmit="return confirm('Clear ALL monitoring data? This cannot be undone.');">
                @csrf
                <input type="hidden" name="category" value="all">
                <input type="hidden" name="confirm" value="1">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-sm font-semibold text-red-900">Clear everything monitored</span>
                    <button type="submit" class="btn btn-danger btn-sm">Clear all</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
