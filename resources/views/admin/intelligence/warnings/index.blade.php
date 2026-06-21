@extends('admin.intelligence.layout')
@php($pageTitle = 'Early Warning System')
@section('intelligence_content')
<div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
    <div class="rounded-xl border bg-white p-4 shadow-sm"><h3 class="text-sm font-semibold mb-2">Intervention Queue</h3><div class="space-y-2">@forelse($warnings as $w)<div class="rounded border px-3 py-2 text-sm"><span class="text-xs uppercase text-red-600">{{ $w->severity }}</span><p class="font-medium">{{ $w->title }}</p><p class="text-gray-600">{{ $w->message }}</p></div>@empty<p class="text-gray-500">No open warnings.</p>@endforelse</div></div>
    <div class="rounded-xl border bg-white p-4 shadow-sm"><h3 class="text-sm font-semibold mb-2">Detected Anomalies</h3><div class="space-y-2">@forelse($anomalies as $a)<div class="rounded border px-3 py-2 text-sm"><span class="text-xs uppercase text-amber-600">{{ $a->severity }}</span><p class="font-medium">{{ $a->title }}</p><p class="text-gray-600">{{ $a->description }}</p></div>@empty<p class="text-gray-500">No anomalies detected.</p>@endforelse</div></div>
</div>
@endsection
