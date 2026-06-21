@extends('admin.monitoring.layout')
@php($pageTitle = 'Database Capacity')
@section('monitoring_content')
@if($latest)
<div class="rounded-xl border bg-white p-4 shadow-sm mb-4 grid grid-cols-2 md:grid-cols-4 gap-3 text-sm">
    <div><span class="text-gray-500">Used</span><p class="font-semibold">{{ number_format(($latest->used_bytes ?? 0) / 1048576, 2) }} MB</p></div>
    <div><span class="text-gray-500">Daily growth</span><p class="font-semibold">{{ $latest->growth_rate_daily ?? 0 }}%</p></div>
    <div><span class="text-gray-500">30d forecast</span><p class="font-semibold">{{ isset($latest->forecast['30d']) ? number_format($latest->forecast['30d']/1048576,2).' MB' : '—' }}</p></div>
    <div><span class="text-gray-500">90d forecast</span><p class="font-semibold">{{ isset($latest->forecast['90d']) ? number_format($latest->forecast['90d']/1048576,2).' MB' : '—' }}</p></div>
</div>
<div class="rounded-xl border bg-white p-4 shadow-sm overflow-x-auto">
    <table class="min-w-full text-sm">
        <thead><tr><th class="text-left py-2">Table</th><th class="text-left py-2">Rows</th><th class="text-left py-2">Size</th></tr></thead>
        <tbody>
            @foreach(($latest->breakdown['tables'] ?? []) as $table)
                <tr class="border-t"><td class="py-2">{{ $table['name'] }}</td><td>{{ number_format($table['rows'] ?? 0) }}</td><td>{{ number_format(($table['size_bytes'] ?? 0)/1048576, 2) }} MB</td></tr>
            @endforeach
        </tbody>
    </table>
</div>
@else
<p class="text-sm text-gray-500">No capacity snapshot yet. Scheduled task will collect metrics.</p>
@endif
@endsection
