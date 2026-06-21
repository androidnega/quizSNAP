@extends('admin.operations.layout')
@php($pageTitle = 'Exam Incidents')
@section('operations_content')
<form method="post" action="{{ route('dashboard.operations.incidents.store') }}" class="rounded-xl border bg-white p-4 shadow-sm mb-4">
    @csrf
    <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
        <input type="text" name="title" placeholder="Incident title" class="rounded-lg border px-3 py-2 text-sm md:col-span-2" required>
        <select name="severity" class="rounded-lg border px-3 py-2 text-sm">
            @foreach(['critical','high','medium','low'] as $sev)<option value="{{ $sev }}">{{ ucfirst($sev) }}</option>@endforeach
        </select>
        <input type="text" name="incident_type" placeholder="Type (optional)" class="rounded-lg border px-3 py-2 text-sm">
        <textarea name="description" rows="2" placeholder="Description" class="rounded-lg border px-3 py-2 text-sm md:col-span-4"></textarea>
        <button class="btn btn-primary btn-sm md:col-span-4">Create Incident</button>
    </div>
</form>
<div class="space-y-3">
    @forelse($open as $incident)
        <div class="rounded-xl border bg-white p-4 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-2">
                <div>
                    <span class="rounded bg-red-100 px-2 py-0.5 text-xs font-semibold uppercase text-red-700">{{ $incident->severity }}</span>
                    <h3 class="mt-1 font-semibold">{{ $incident->title }}</h3>
                    <p class="text-xs text-gray-500">{{ $incident->status }} · {{ $incident->started_at }} · {{ $incident->assigned_to_name ?? 'Unassigned' }}</p>
                </div>
                <form method="post" action="{{ route('dashboard.operations.incidents.resolve', $incident) }}">@csrf<button class="btn btn-secondary btn-sm">Resolve</button></form>
            </div>
        </div>
    @empty
        <p class="text-sm text-gray-500">No open incidents.</p>
    @endforelse
</div>
@endsection
