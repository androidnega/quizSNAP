@extends('admin.monitoring.layout')
@php($pageTitle = 'Server Health')
@section('monitoring_content')
@if($latest)
<div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm mb-4">
    <div class="flex items-center justify-between mb-3">
        <h2 class="text-sm font-semibold">Current status: <span class="capitalize">{{ $latest->status }}</span></h2>
        <span class="text-xs text-gray-500">{{ $latest->recorded_at }}</span>
    </div>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm">
        <div>CPU: <strong>{{ $latest->cpu_usage ?? '—' }}%</strong></div>
        <div>RAM: <strong>{{ $latest->ram_usage ?? '—' }}%</strong></div>
        <div>Disk: <strong>{{ $latest->disk_usage ?? '—' }}%</strong></div>
        <div>Load: <strong>{{ $latest->load_average ?? '—' }}</strong></div>
        <div>PHP: <strong>{{ $latest->php_version }}</strong></div>
        <div>Laravel: <strong>{{ $latest->laravel_version }}</strong></div>
        <div>MySQL: <strong>{{ $latest->mysql_version ?? '—' }}</strong></div>
        <div>Workers: <strong>{{ $latest->queue_workers ?? 0 }}</strong></div>
    </div>
</div>
@endif
@include('admin.monitoring.partials.log-table', ['rows' => $history, 'columns' => [
    ['key' => 'recorded_at', 'label' => 'Recorded'],
    ['key' => 'status', 'label' => 'Status'],
    ['key' => 'cpu_usage', 'label' => 'CPU %'],
    ['key' => 'ram_usage', 'label' => 'RAM %'],
    ['key' => 'disk_usage', 'label' => 'Disk %'],
]])
@endsection
