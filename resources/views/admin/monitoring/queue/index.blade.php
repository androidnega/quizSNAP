@extends('admin.monitoring.layout')
@php($pageTitle = 'Queue Monitor')
@section('monitoring_content')
<div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
    @foreach(['pending' => 'Pending', 'failed' => 'Failed', 'recent_failed' => 'Failed (1h)', 'workers' => 'Workers'] as $key => $label)
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-xs text-gray-500">{{ $label }}</p>
            <p class="text-2xl font-bold tabular-nums">{{ $stats[$key] ?? 0 }}</p>
        </div>
    @endforeach
</div>
@include('admin.monitoring.partials.failed-jobs-table', ['failedJobs' => $failedJobs])
@endsection
