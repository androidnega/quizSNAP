@extends('admin.intelligence.layout')
@php($pageTitle = 'Recommendations')
@php($intelligencePage = 'recommendations')
@section('intelligence_content')
<div class="space-y-3">
    @forelse($data['recommendations'] ?? [] as $rec)
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <span class="rounded-full bg-violet-100 px-2.5 py-0.5 text-xs font-semibold uppercase text-violet-700">{{ $rec->severity }}</span>
            <h3 class="mt-2 font-semibold text-gray-900">{{ $rec->title }}</h3>
            <p class="text-sm text-gray-600 mt-1">{{ $rec->message }}</p>
            <p class="text-xs text-gray-400 mt-2">{{ $rec->created_at?->format('M j, Y H:i') }} · {{ $rec->created_at?->diffForHumans() }}</p>
        </div>
    @empty
        <div class="rounded-xl border border-dashed border-gray-300 bg-gray-50 p-8 text-center text-sm text-gray-500">No recommendations generated yet.</div>
    @endforelse
</div>
@endsection
