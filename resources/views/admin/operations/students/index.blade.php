@extends('admin.operations.layout')
@php($pageTitle = 'Student Activity')
@php($operationsPage = 'students')
@section('operations_content')
<div id="operations-students-root">
    <div class="grid grid-cols-3 gap-3 mb-4">
        @foreach($snapshot['summary'] ?? [] as $key => $value)
            <div class="rounded-xl border bg-white p-3 shadow-sm"><span class="text-xs text-gray-500 capitalize">{{ str_replace('_', ' ', $key) }}</span><p class="text-xl font-bold" data-operations-students="{{ $key }}">{{ number_format($value) }}</p></div>
        @endforeach
    </div>
    <div class="rounded-xl border bg-white shadow-sm overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50"><tr>
                <th class="px-3 py-2 text-left">Student</th><th class="px-3 py-2 text-left">Exam</th><th class="px-3 py-2 text-left">Page</th><th class="px-3 py-2 text-left">Device</th><th class="px-3 py-2 text-left">Browser</th><th class="px-3 py-2 text-left">Duration</th><th class="px-3 py-2 text-left">Status</th><th class="px-3 py-2 text-left">Connection</th>
            </tr></thead>
            <tbody id="operations-students-table">
                @forelse($snapshot['students'] ?? [] as $student)
                    <tr class="border-t">
                        <td class="px-3 py-2">{{ $student['student_index'] }}</td>
                        <td class="px-3 py-2">{{ $student['exam'] ?? '—' }}</td>
                        <td class="px-3 py-2 truncate max-w-[120px]">{{ $student['current_page'] ?? '—' }}</td>
                        <td class="px-3 py-2">{{ $student['device'] ?? '—' }}</td>
                        <td class="px-3 py-2">{{ $student['browser'] ?? '—' }}</td>
                        <td class="px-3 py-2">{{ $student['session_duration_minutes'] ?? 0 }}m</td>
                        <td class="px-3 py-2 capitalize">{{ $student['online_status'] ?? '—' }}</td>
                        <td class="px-3 py-2 capitalize">{{ $student['connection_quality'] ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-4 py-8 text-center text-gray-500">No active student sessions.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
