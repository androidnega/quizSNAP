@extends('layouts.dashboard')

@section('title', 'Quizzes')
@section('dashboard_heading', 'Quizzes')

@section('dashboard_content')
@php
    $activeTab = ($tab ?? 'active') === 'ended' ? 'ended' : 'active';
    $search = $q ?? request('q', '');
@endphp
<div class="w-full space-y-5">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div class="min-w-0">
            <p class="text-sm text-gray-500">Create, publish, and review assessments.</p>
        </div>
        <a href="{{ route('dashboard.quizzes.create') }}" class="inline-flex items-center justify-center gap-2 rounded-full bg-gray-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-black focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-400 focus-visible:ring-offset-2 transition-colors shrink-0">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Create quiz
        </a>
    </div>

    <div class="flex items-center gap-6 border-b border-gray-200">
        <a href="{{ route('dashboard.quizzes.index', ['tab' => 'active']) }}" class="relative pb-3 text-sm font-medium transition-colors {{ $activeTab === 'active' ? 'text-gray-900' : 'text-gray-400 hover:text-gray-700' }}">
            Active
            @if($activeTab === 'active')
                <span class="absolute inset-x-0 -bottom-px h-0.5 bg-gray-900 rounded-full"></span>
            @endif
        </a>
        <a href="{{ route('dashboard.quizzes.index', ['tab' => 'ended']) }}" class="relative pb-3 text-sm font-medium transition-colors {{ $activeTab === 'ended' ? 'text-gray-900' : 'text-gray-400 hover:text-gray-700' }}">
            Ended
            @if($activeTab === 'ended')
                <span class="absolute inset-x-0 -bottom-px h-0.5 bg-gray-900 rounded-full"></span>
            @endif
        </a>
    </div>

    <div id="live-search-panel" class="hidden" data-live-search="1" data-param="q" aria-hidden="true"></div>
    <p id="live-search-meta" class="text-xs text-gray-400 tabular-nums {{ $search ? '' : 'hidden' }}">{{ $quizzes->total() }} quizzes</p>

    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden flex flex-col max-h-[min(36rem,65vh)]">
        <div class="dashboard-list-scroll flex-1 min-h-0" id="live-search-results">
            @include('admin.quizzes.partials.quiz-list-items', ['quizzes' => $quizzes, 'activeTab' => $activeTab, 'search' => $search])
        </div>

        <div id="live-search-pagination-wrap" class="{{ $quizzes->hasPages() ? '' : 'hidden' }} border-t border-gray-100 bg-gray-50/80 px-4 py-3 shrink-0">
            <div id="live-search-pagination">
                @if($quizzes->hasPages())
                    {{ $quizzes->links() }}
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
