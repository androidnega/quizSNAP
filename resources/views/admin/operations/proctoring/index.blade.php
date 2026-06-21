@extends('admin.operations.layout')
@php($pageTitle = 'Proctoring Center')
@php($operationsPage = 'proctoring')
@section('operations_content')
<div id="operations-proctoring-root">
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
        @foreach([
            'face_verification_failures' => 'Face Failures',
            'multiple_faces' => 'Multiple Faces',
            'phone_detected' => 'Phone Detected',
            'tab_switching' => 'Tab Switches',
            'copy_paste' => 'Copy/Paste',
            'window_blur' => 'Window Blur',
            'flagged_students' => 'Flagged Students',
            'total_violations' => 'Total Violations',
        ] as $key => $label)
            <div class="rounded-xl border bg-white p-3 shadow-sm">
                <p class="text-xs text-gray-500">{{ $label }}</p>
                <p class="text-xl font-bold text-red-600" data-operations-proctoring="{{ $key }}">{{ number_format($snapshot['summary'][$key] ?? 0) }}</p>
            </div>
        @endforeach
    </div>
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
        <div class="rounded-xl border bg-white p-4 shadow-sm">
            <h3 class="text-sm font-semibold mb-3">Flagged Students</h3>
            <div class="space-y-2 max-h-80 overflow-y-auto">
                @forelse($snapshot['flagged_students'] ?? [] as $student)
                    <div class="flex justify-between rounded border px-3 py-2 text-sm">
                        <span>{{ $student['student_index'] }}</span>
                        <span class="font-semibold text-red-600">Risk {{ $student['risk_score'] ?? 0 }}</span>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">No flagged students.</p>
                @endforelse
            </div>
        </div>
        <div class="rounded-xl border bg-white p-4 shadow-sm">
            <h3 class="text-sm font-semibold mb-3">Violation Feed</h3>
            <div id="operations-proctoring-feed" class="space-y-2 max-h-80 overflow-y-auto">
                @forelse($snapshot['feed'] ?? [] as $item)
                    <div class="rounded border px-3 py-2 text-sm">
                        <span class="text-xs uppercase text-red-600">{{ $item['severity'] ?? 'warning' }}</span>
                        <p>{{ $item['student'] ?? '—' }} — {{ $item['label'] ?? $item['violation_type'] }}</p>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">No recent violations.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
