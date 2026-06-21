@extends('admin.operations.layout')
@php($pageTitle = 'Live Exams')
@php($operationsPage = 'live-exams')
@section('operations_content')
<div id="operations-live-exams-root">
    <div class="grid grid-cols-2 md:grid-cols-3 gap-3 mb-4">
        @foreach($snapshot['summary'] ?? [] as $key => $value)
            @if(!in_array($key, ['timestamp']))
                <div class="rounded-xl border bg-white p-3 shadow-sm text-sm">
                    <span class="text-gray-500 capitalize">{{ str_replace('_', ' ', $key) }}</span>
                    <p class="text-xl font-bold" data-operations-live-exam="{{ $key }}">{{ is_numeric($value) ? number_format($value) : $value }}</p>
                </div>
            @endif
        @endforeach
    </div>
    <div class="rounded-xl border bg-white shadow-sm overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50"><tr>
                <th class="px-4 py-3 text-left">Exam</th>
                <th class="px-4 py-3 text-left">Course</th>
                <th class="px-4 py-3 text-left">Examiner</th>
                <th class="px-4 py-3 text-left">Active</th>
                <th class="px-4 py-3 text-left">Submitted</th>
                <th class="px-4 py-3 text-left">Completion</th>
                <th class="px-4 py-3 text-left">Risk</th>
                <th class="px-4 py-3 text-left">Actions</th>
            </tr></thead>
            <tbody id="operations-live-exams-table">
                @forelse($snapshot['exams'] ?? [] as $exam)
                    <tr class="border-t hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium">{{ $exam['title'] }}</td>
                        <td class="px-4 py-3">{{ $exam['course'] ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $exam['examiner'] ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $exam['students_active'] ?? 0 }}</td>
                        <td class="px-4 py-3">{{ $exam['students_submitted'] ?? 0 }}</td>
                        <td class="px-4 py-3">{{ $exam['completion_percentage'] ?? 0 }}%</td>
                        <td class="px-4 py-3"><span class="rounded px-2 py-0.5 text-xs font-semibold uppercase bg-gray-100">{{ $exam['risk_level'] ?? 'low' }}</span></td>
                        <td class="px-4 py-3">
                            <a href="{{ route('dashboard.operations.live-exams.show', $exam['id']) }}" class="text-indigo-600 hover:underline text-xs">Details</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-4 py-8 text-center text-gray-500">No active exams.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
