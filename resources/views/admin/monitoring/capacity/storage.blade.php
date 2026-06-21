@extends('admin.monitoring.layout')
@php($pageTitle = 'Storage Capacity')
@section('monitoring_content')
@if($latest)
<div class="rounded-xl border bg-white p-4 shadow-sm mb-4 grid grid-cols-2 md:grid-cols-4 gap-3 text-sm">
    <div><span class="text-gray-500">App storage used</span><p class="font-semibold">{{ number_format(($latest->used_bytes ?? 0) / 1048576, 2) }} MB</p></div>
    <div><span class="text-gray-500">Disk free</span><p class="font-semibold">{{ $latest->free_bytes ? number_format($latest->free_bytes / 1073741824, 2).' GB' : '—' }}</p></div>
    <div><span class="text-gray-500">Daily growth</span><p class="font-semibold">{{ $latest->growth_rate_daily ?? 0 }}%</p></div>
    <div><span class="text-gray-500">Exhaustion forecast</span><p class="font-semibold">{{ $latest->breakdown['exhaustion_forecast'] ?? '—' }}</p></div>
</div>
<ul class="space-y-2 text-sm">
    @foreach(($latest->breakdown['directories'] ?? []) as $dir)
        <li class="flex justify-between rounded border px-3 py-2"><span>{{ $dir['name'] }}</span><strong>{{ number_format(($dir['size_bytes'] ?? 0)/1048576, 2) }} MB</strong></li>
    @endforeach
</ul>
@else
<p class="text-sm text-gray-500">No storage snapshot yet.</p>
@endif
@endsection
