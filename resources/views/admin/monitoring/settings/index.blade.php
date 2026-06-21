@extends('admin.monitoring.layout')
@php($pageTitle = 'Monitoring Settings')
@section('monitoring_content')
<form method="post" action="{{ route('dashboard.monitoring.settings.update') }}" class="card qs-form max-w-xl">
    @csrf
    <div class="qs-section space-y-4">
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
@endsection
