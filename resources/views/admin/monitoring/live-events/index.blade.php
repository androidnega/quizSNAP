@extends('admin.monitoring.layout')
@php($pageTitle = 'Live Events')
@section('monitoring_content')
<div class="grid grid-cols-2 gap-3 mb-4">
    <div class="rounded-xl border bg-white p-4 shadow-sm"><p class="text-xs text-gray-500">Active quiz takers</p><p class="text-2xl font-bold">{{ $liveQuiz['active_takers'] ?? 0 }}</p></div>
    <div class="rounded-xl border bg-white p-4 shadow-sm"><p class="text-xs text-gray-500">Active quizzes</p><p class="text-2xl font-bold">{{ $liveQuiz['active_quizzes'] ?? 0 }}</p></div>
</div>
<div id="monitoring-live-events" class="space-y-2">
    @foreach($recentActivity as $entry)
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-sm"><strong>{{ $entry->user_name ?? 'System' }}</strong> — {{ $entry->action }}</p>
            <p class="text-xs text-gray-500">{{ $entry->occurred_at }}</p>
        </div>
    @endforeach
</div>
@endsection
