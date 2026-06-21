@extends('admin.operations.layout')
@php($pageTitle = 'Live Attendance Operations')
@php($operationsPage = 'attendance')
@section('operations_content')
<div id="operations-attendance-root">
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
        @foreach([
            'active_sessions' => 'Active Sessions',
            'current_checkins' => 'Check-ins',
            'students_present' => 'Present',
            'students_absent' => 'Absent',
            'checkins_per_minute' => 'Check-ins/min',
            'attendance_rate' => 'Rate %',
            'late_arrivals' => 'Late Arrivals',
        ] as $key => $label)
            <div class="rounded-xl border bg-white p-3 shadow-sm">
                <p class="text-xs text-gray-500">{{ $label }}</p>
                <p class="text-xl font-bold" data-operations-attendance="{{ $key }}">{{ is_numeric($snapshot[$key] ?? 0) ? number_format($snapshot[$key]) : ($snapshot[$key] ?? 0) }}</p>
            </div>
        @endforeach
    </div>
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
        <div class="rounded-xl border bg-white p-4 shadow-sm">
            <h3 class="text-sm font-semibold mb-2">Activity Feed</h3>
            <div id="operations-attendance-feed" class="space-y-2 max-h-80 overflow-y-auto">
                @forelse($snapshot['activity_feed'] ?? [] as $item)
                    <div class="rounded border px-3 py-2 text-sm">{{ $item['action'] ?? 'Activity' }} — {{ $item['time'] ?? '' }}</div>
                @empty
                    <p class="text-sm text-gray-500">No recent attendance activity.</p>
                @endforelse
            </div>
        </div>
        <div class="rounded-xl border bg-white p-4 shadow-sm">
            <h3 class="text-sm font-semibold mb-2">By Course</h3>
            <ul class="space-y-1 text-sm">
                @forelse($snapshot['course_breakdown'] ?? [] as $row)
                    <li class="flex justify-between border-b py-1"><span>{{ $row['name'] ?? '—' }}</span><strong>{{ $row['total'] ?? 0 }}</strong></li>
                @empty
                    <li class="text-gray-500">No course data.</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>
@endsection
