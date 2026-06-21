@extends('admin.monitoring.layout')
@php($pageTitle = 'Live Quiz Monitor')
@section('monitoring_content')
<div id="live-quiz-monitor-root" class="space-y-4">
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        @foreach([
            'active_quizzes' => 'Active Quizzes',
            'active_takers' => 'Students Taking Quiz',
            'completed_attempts' => 'Completed Attempts',
            'abandoned_attempts' => 'Abandoned Attempts',
            'submissions_per_minute' => 'Submissions / Min',
            'avg_completion_seconds' => 'Avg Completion (s)',
            'success_rate' => 'Success Rate %',
            'current_participants' => 'Current Participants',
        ] as $key => $label)
            <div class="rounded-xl border bg-white p-4 shadow-sm">
                <p class="text-xs text-gray-500">{{ $label }}</p>
                <p class="text-2xl font-bold tabular-nums" data-live-quiz="{{ $key }}">{{ $snapshot[$key] ?? 0 }}</p>
            </div>
        @endforeach
    </div>
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
        <div class="rounded-xl border bg-white p-4 shadow-sm">
            <h3 class="text-sm font-semibold mb-2">Active Quiz Leaderboard</h3>
            <ul class="space-y-2 text-sm">
                @forelse($snapshot['leaderboard'] ?? [] as $item)
                    <li class="flex justify-between border-b border-gray-100 pb-1"><span>{{ $item['title'] }}</span><strong>{{ $item['participants'] }}</strong></li>
                @empty
                    <li class="text-gray-500">No active quizzes</li>
                @endforelse
            </ul>
        </div>
        <div class="rounded-xl border bg-white p-4 shadow-sm">
            <h3 class="text-sm font-semibold mb-2">Participation Feed</h3>
            <div id="live-quiz-feed" class="space-y-2 max-h-80 overflow-y-auto">
                @foreach($snapshot['feed'] ?? [] as $item)
                    <div class="rounded border border-gray-100 px-3 py-2 text-sm">
                        <strong>{{ $item['student'] ?? 'Student' }}</strong> — {{ $item['quiz'] ?? 'Quiz' }}
                        <span class="text-xs text-gray-500">({{ $item['status'] ?? '' }})</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection
