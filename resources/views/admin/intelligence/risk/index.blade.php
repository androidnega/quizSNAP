@extends('admin.intelligence.layout')
@php($pageTitle = 'Risk Analysis')
@section('intelligence_content')
<div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
    <div class="rounded-xl border bg-white p-4 shadow-sm"><p class="text-xs text-gray-500">Overall Risk</p><p class="text-2xl font-bold">{{ $data['overall_risk_score'] ?? 0 }}</p><p class="text-xs uppercase">{{ $data['overall_risk_level'] ?? '—' }}</p></div>
    <div class="rounded-xl border bg-white p-4 shadow-sm"><p class="text-xs text-gray-500">At-Risk Count</p><p class="text-2xl font-bold">{{ $data['at_risk_count'] ?? 0 }}</p></div>
</div>
<div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
    <div class="rounded-xl border bg-white p-4 shadow-sm"><h3 class="text-sm font-semibold mb-2">Risk Distribution</h3><ul class="text-sm space-y-1">@foreach($data['distribution'] ?? [] as $level => $count)<li class="flex justify-between"><span class="capitalize">{{ $level }}</span><strong>{{ $count }}</strong></li>@endforeach</ul></div>
    <div class="rounded-xl border bg-white p-4 shadow-sm"><h3 class="text-sm font-semibold mb-2">Interventions</h3><ul class="text-sm space-y-2">@forelse($data['interventions'] ?? [] as $item)<li class="rounded border px-3 py-2"><span class="text-xs uppercase text-red-600">{{ $item['priority'] ?? '' }}</span><p>{{ $item['action'] ?? '' }}</p></li>@empty<li class="text-gray-500">No interventions recommended.</li>@endforelse</ul></div>
</div>
@endsection
