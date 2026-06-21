@extends('admin.intelligence.layout')
@php($pageTitle = 'Early Warning System')
@php($intelligencePage = 'warnings')
@section('intelligence_content')
<div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
        <h3 class="text-sm font-semibold text-gray-900 mb-3">Intervention Queue</h3>
        <div class="space-y-2 max-h-96 overflow-y-auto">
            @forelse($warnings as $w)
                <div class="rounded-lg border border-red-100 bg-red-50/40 px-3 py-2.5 text-sm">
                    <span class="text-xs font-semibold uppercase text-red-600">{{ $w->severity }}</span>
                    <p class="mt-1 font-medium text-gray-900">{{ $w->title }}</p>
                    <p class="text-gray-600 mt-0.5">{{ $w->message }}</p>
                </div>
            @empty
                <p class="text-gray-500 py-6 text-center">No open warnings.</p>
            @endforelse
        </div>
    </div>
    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
        <h3 class="text-sm font-semibold text-gray-900 mb-3">Detected Anomalies</h3>
        <div class="space-y-2 max-h-96 overflow-y-auto">
            @forelse($anomalies as $a)
                <div class="rounded-lg border border-amber-100 bg-amber-50/40 px-3 py-2.5 text-sm">
                    <span class="text-xs font-semibold uppercase text-amber-700">{{ $a->severity }}</span>
                    <p class="mt-1 font-medium text-gray-900">{{ $a->title }}</p>
                    <p class="text-gray-600 mt-0.5">{{ $a->description }}</p>
                </div>
            @empty
                <p class="text-gray-500 py-6 text-center">No anomalies detected.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
