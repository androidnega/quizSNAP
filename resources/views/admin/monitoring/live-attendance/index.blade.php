@extends('admin.monitoring.layout')
@php($pageTitle = 'Live Attendance Monitor')
@section('monitoring_content')
<div id="live-attendance-monitor-root" class="space-y-4">
    <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
        @foreach([
            'active_sessions' => 'Active Sessions',
            'current_checkins' => 'Current Check-ins',
            'students_present' => 'Students Present',
            'students_absent' => 'Students Absent',
            'checkins_per_minute' => 'Check-ins / Min',
        ] as $key => $label)
            <div class="rounded-xl border bg-white p-4 shadow-sm">
                <p class="text-xs text-gray-500">{{ $label }}</p>
                <p class="text-2xl font-bold tabular-nums" data-live-attendance="{{ $key }}">{{ $snapshot[$key] ?? 0 }}</p>
            </div>
        @endforeach
    </div>
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
        <div class="rounded-xl border bg-white p-4 shadow-sm">
            <h3 class="text-sm font-semibold mb-2">By Course</h3>
            <ul class="text-sm space-y-1">
                @forelse($snapshot['by_course'] ?? [] as $row)
                    <li class="flex justify-between"><span>{{ $row['name'] }}</span><strong>{{ $row['checkins'] }}</strong></li>
                @empty
                    <li class="text-gray-500">No recent activity</li>
                @endforelse
            </ul>
        </div>
        <div class="rounded-xl border bg-white p-4 shadow-sm">
            <h3 class="text-sm font-semibold mb-2">By Department</h3>
            <ul class="text-sm space-y-1">
                @forelse($snapshot['by_department'] ?? [] as $row)
                    <li class="flex justify-between"><span>{{ $row['name'] }}</span><strong>{{ $row['checkins'] }}</strong></li>
                @empty
                    <li class="text-gray-500">No recent activity</li>
                @endforelse
            </ul>
        </div>
    </div>
    <div class="rounded-xl border bg-white p-4 shadow-sm">
        <h3 class="text-sm font-semibold mb-2">Activity Feed</h3>
        <div class="space-y-2 max-h-80 overflow-y-auto">
            @forelse($snapshot['activity_feed'] ?? [] as $item)
                <div class="rounded border border-gray-100 px-3 py-2 text-sm">{{ $item['message'] ?? '' }} <span class="text-xs text-gray-500">{{ $item['time'] ?? '' }}</span></div>
            @empty
                <p class="text-sm text-gray-500">No attendance activity yet.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
