@extends('admin.monitoring.layout')
@php($pageTitle = 'Incidents')
@section('monitoring_content')
<form method="post" action="{{ route('dashboard.monitoring.incidents.store') }}" class="card qs-form mb-4">
    @csrf
    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
        <input type="text" name="title" placeholder="Incident title" class="rounded-lg border border-gray-300 px-3 py-2 text-sm" required>
        <select name="severity" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
            @foreach(['P1','P2','P3','P4'] as $sev)<option value="{{ $sev }}">{{ $sev }}</option>@endforeach
        </select>
        <input type="text" name="affected_services" placeholder="Affected services (comma separated)" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
        <button type="submit" class="btn btn-primary btn-sm md:col-span-3">Create Incident</button>
    </div>
</form>
<div class="space-y-3">
    @forelse($incidents as $incident)
        <div class="rounded-xl border bg-white p-4 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-2">
                <div>
                    <span class="rounded bg-red-100 px-2 py-0.5 text-xs font-semibold text-red-700">{{ $incident->severity }}</span>
                    <h3 class="mt-1 font-semibold">{{ $incident->title }}</h3>
                    <p class="text-xs text-gray-500">{{ $incident->status }} · {{ $incident->started_at }}</p>
                </div>
                @if($incident->status !== 'resolved')
                <form method="post" action="{{ route('dashboard.monitoring.incidents.resolve', $incident) }}">@csrf<button class="btn btn-secondary btn-sm">Resolve</button></form>
                @endif
            </div>
        </div>
    @empty
        <p class="text-sm text-gray-500">No open incidents.</p>
    @endforelse
</div>
@endsection
