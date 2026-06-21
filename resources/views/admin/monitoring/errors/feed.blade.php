@extends('admin.monitoring.layout')
@php($pageTitle = 'Live Error Feed')
@php($monitoringPage = 'errors')
@section('monitoring_content')
<div id="monitoring-error-feed" class="space-y-2">
    @foreach($occurrences as $occ)
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <div class="flex justify-between gap-2">
                <span class="text-xs font-semibold uppercase text-red-600">{{ $occ->systemError?->severity }}</span>
                <span class="text-xs text-gray-500">{{ $occ->occurred_at?->diffForHumans() }}</span>
            </div>
            <p class="mt-1 text-sm font-medium text-gray-900">{{ $occ->systemError?->message }}</p>
            <p class="text-xs text-gray-500">{{ basename($occ->systemError?->file ?? '') }}:{{ $occ->systemError?->line }} — {{ $occ->user_name ?? 'Guest' }}</p>
        </div>
    @endforeach
</div>
@endsection
