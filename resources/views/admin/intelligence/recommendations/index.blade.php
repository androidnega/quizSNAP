@extends('admin.intelligence.layout')
@php($pageTitle = 'Recommendations')
@section('intelligence_content')
<div class="space-y-3">
    @forelse($data['recommendations'] ?? [] as $rec)
        <div class="rounded-xl border bg-white p-4 shadow-sm">
            <span class="rounded bg-violet-100 px-2 py-0.5 text-xs font-semibold uppercase text-violet-700">{{ $rec->severity }}</span>
            <h3 class="mt-1 font-semibold">{{ $rec->title }}</h3>
            <p class="text-sm text-gray-600">{{ $rec->message }}</p>
            <p class="text-xs text-gray-400 mt-1">{{ $rec->created_at?->diffForHumans() }}</p>
        </div>
    @empty
        <p class="text-sm text-gray-500">No recommendations generated yet. Run `php artisan intelligence:collect-metrics`.</p>
    @endforelse
</div>
@endsection
