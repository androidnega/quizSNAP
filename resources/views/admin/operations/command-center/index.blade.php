@extends('admin.operations.layout')
@php($pageTitle = 'Live Exam Command Center')
@php($operationsPage = 'command-center')
@section('operations_content')
<div id="operations-command-center-root">
    <div class="grid grid-cols-2 md:grid-cols-4 xl:grid-cols-6 gap-3 mb-4">
        @foreach([
            ['key' => 'active_exams', 'label' => 'Active Exams', 'color' => 'text-indigo-600'],
            ['key' => 'students_writing', 'label' => 'Writing Now', 'color' => 'text-emerald-600'],
            ['key' => 'students_completed', 'label' => 'Completed', 'color' => 'text-blue-600'],
            ['key' => 'students_disconnected', 'label' => 'Disconnected', 'color' => 'text-amber-600'],
            ['key' => 'submissions_per_minute', 'label' => 'Submissions/min', 'color' => 'text-purple-600'],
            ['key' => 'suspicious_activities', 'label' => 'Suspicious', 'color' => 'text-red-600'],
        ] as $card)
            <div class="rounded-xl border bg-white p-4 shadow-sm">
                <p class="text-xs text-gray-500">{{ $card['label'] }}</p>
                <p class="mt-1 text-2xl font-bold tabular-nums {{ $card['color'] }}" data-operations-stat="{{ $card['key'] }}">{{ number_format($payload[$card['key']] ?? 0) }}</p>
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
        <div class="rounded-xl border bg-white p-4 shadow-sm xl:col-span-2">
            <h2 class="text-sm font-semibold mb-3">Realtime Activity Feed</h2>
            <div id="operations-activity-feed" class="space-y-2 max-h-96 overflow-y-auto">
                @forelse($payload['feed'] ?? [] as $item)
                    <div class="rounded-lg border px-3 py-2 text-sm">
                        <span class="text-xs uppercase text-gray-500">{{ $item['type'] ?? 'activity' }}</span>
                        <p class="text-gray-900">{{ $item['student'] ?? $item['exam'] ?? 'System' }} — {{ $item['status'] ?? ($item['label'] ?? '') }}</p>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">No recent activity.</p>
                @endforelse
            </div>
        </div>
        <div class="rounded-xl border bg-white p-4 shadow-sm">
            <h2 class="text-sm font-semibold mb-3">Operations Snapshot</h2>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-gray-500">Avg progress</dt><dd data-operations-stat="avg_progress">{{ $payload['avg_progress'] ?? 0 }}%</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Attendance rate</dt><dd data-operations-stat="attendance_rate">{{ $payload['attendance_rate'] ?? 0 }}%</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Open incidents</dt><dd data-operations-stat="open_incidents">{{ $payload['open_incidents'] ?? 0 }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Users online</dt><dd data-operations-stat="users_online">{{ $payload['users_online'] ?? 0 }}</dd></div>
            </dl>
        </div>
    </div>
</div>
@endsection
