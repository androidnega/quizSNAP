@extends('admin.intelligence.layout')
@php($pageTitle = 'Risk Analysis')
@php($intelligencePage = 'risk')
@section('intelligence_content')
@include('admin.intelligence.partials.stat-cards', ['columns' => 'grid-cols-2 md:grid-cols-4', 'cards' => [
    ['label' => 'Overall Risk', 'value' => number_format($data['overall_risk_score'] ?? 0), 'hint' => strtoupper($data['overall_risk_level'] ?? '—'), 'icon' => 'fa-exclamation-triangle'],
    ['label' => 'At-Risk Students', 'value' => number_format($data['at_risk_count'] ?? 0), 'icon' => 'fa-user-clock'],
]])
<div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
    @include('admin.intelligence.partials.section-card', ['title' => 'Risk Distribution', 'items' => collect($data['distribution'] ?? [])->map(fn ($count, $level) => ['name' => ucfirst($level), 'count' => $count])->values()->all()])
    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
        <h3 class="text-sm font-semibold text-gray-900 mb-3">Recommended Interventions</h3>
        <ul class="space-y-2 text-sm max-h-72 overflow-y-auto">
            @forelse($data['interventions'] ?? [] as $item)
                <li class="rounded-lg border border-gray-100 px-3 py-2">
                    <span class="text-xs font-semibold uppercase text-red-600">{{ $item['priority'] ?? '' }}</span>
                    <p class="mt-1 text-gray-800">{{ $item['action'] ?? '' }}</p>
                </li>
            @empty
                <li class="text-gray-500 py-4 text-center">No interventions recommended.</li>
            @endforelse
        </ul>
    </div>
</div>
@endsection
