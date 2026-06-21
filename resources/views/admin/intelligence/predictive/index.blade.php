@extends('admin.intelligence.layout')
@php($pageTitle = 'Predictive Analytics')
@section('intelligence_content')
<div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
    @foreach($data['institution'] ?? [] as $key => $value)
        <div class="rounded-xl border bg-white p-4 shadow-sm"><p class="text-xs text-gray-500 capitalize">{{ str_replace('_', ' ', $key) }}</p><p class="text-2xl font-bold">{{ $value }}%</p></div>
    @endforeach
</div>
<div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
    <div class="rounded-xl border bg-white p-4 shadow-sm"><h3 class="text-sm font-semibold mb-2">Course Risk</h3><ul class="text-sm">@forelse($data['course_risks'] ?? [] as $row)<li class="flex justify-between border-b py-1"><span>{{ $row['name'] }}</span><strong>{{ $row['risk_score'] }}</strong></li>@empty<li class="text-gray-500">No data</li>@endforelse</ul></div>
    <div class="rounded-xl border bg-white p-4 shadow-sm"><h3 class="text-sm font-semibold mb-2">Student Predictions</h3><ul class="text-sm max-h-80 overflow-y-auto">@forelse(array_slice($data['student_predictions'] ?? [], 0, 15) as $row)<li class="flex justify-between border-b py-1"><span>{{ $row['student_index'] }}</span><strong>Pass {{ $row['likely_pass'] }}%</strong></li>@empty<li class="text-gray-500">No data</li>@endforelse</ul></div>
</div>
@endsection
