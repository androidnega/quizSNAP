@extends('admin.monitoring.layout')
@php($pageTitle = 'Backup Monitor')
@section('monitoring_content')
<div class="flex justify-between mb-4">
    <p class="text-sm text-gray-600">Track backup status and retention.</p>
    <form method="post" action="{{ route('dashboard.monitoring.backups.scan') }}">@csrf<button class="btn btn-primary btn-sm">Scan Now</button></form>
</div>
@if($latest)
<div class="rounded-xl border bg-white p-4 shadow-sm mb-4">
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm">
        <div><span class="text-gray-500">Status</span><p class="font-semibold capitalize">{{ $latest->status }}</p></div>
        <div><span class="text-gray-500">Size</span><p class="font-semibold">{{ $latest->size_bytes ? number_format($latest->size_bytes / 1048576, 2).' MB' : '—' }}</p></div>
        <div><span class="text-gray-500">Last backup</span><p class="font-semibold">{{ $latest->backed_up_at }}</p></div>
        <div><span class="text-gray-500">Retention</span><p class="font-semibold">{{ $latest->retention_days ?? '—' }} days</p></div>
    </div>
    @if($latest->location)<p class="mt-2 text-xs text-gray-500 break-all">{{ $latest->location }}</p>@endif
</div>
@endif
@include('admin.monitoring.partials.log-table', ['rows' => $history, 'columns' => [
    ['key' => 'backed_up_at', 'label' => 'When'],
    ['key' => 'status', 'label' => 'Status'],
    ['key' => 'size_bytes', 'label' => 'Size (bytes)'],
    ['key' => 'location', 'label' => 'Location'],
]])
@endsection
