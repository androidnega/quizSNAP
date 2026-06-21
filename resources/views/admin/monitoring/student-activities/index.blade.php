@extends('admin.monitoring.layout')
@php($pageTitle = 'Student Activities')
@php($monitoringPage = 'student-activities')
@section('monitoring_content')
<form method="get" class="flex flex-wrap gap-2 mb-4">
    <input type="search" name="search" value="{{ request('search') }}" placeholder="Student name or index..." class="rounded-lg border border-gray-300 px-3 py-2 text-sm min-w-[14rem]">
    <select name="type" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
        <option value="">All activity types</option>
        @foreach($types as $type)
            <option value="{{ $type }}" @selected(request('type') === $type)>{{ \App\Models\QuizViolation::labelForType($type) }}</option>
        @endforeach
    </select>
    <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
</form>
<div class="rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Time</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Student</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Quiz</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Activity</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Severity</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($activities as $activity)
                    @php
                        $student = $activity->quizSession?->student;
                        $quiz = $activity->quizSession?->quiz;
                        $isTabSwitch = in_array($activity->type, ['tab_switch', 'blur'], true);
                    @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 whitespace-nowrap text-gray-700">{{ $activity->occurred_at?->format('M j, Y H:i:s') ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <p class="font-medium text-gray-900">{{ $student?->name ?? 'Unknown' }}</p>
                            <p class="text-xs text-gray-500">{{ $student?->index_number ?? '' }}</p>
                        </td>
                        <td class="px-4 py-3 text-gray-700">{{ $quiz?->title ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium {{ $isTabSwitch ? 'bg-amber-100 text-amber-800' : ($activity->severity === 'critical' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800') }}">
                                @if($isTabSwitch)
                                    <i class="fas fa-external-link-alt text-[10px]" aria-hidden="true"></i>
                                @endif
                                {{ \App\Models\QuizViolation::labelForType($activity->type) }}
                            </span>
                            @if($activity->out_of_frame_duration)
                                <p class="text-xs text-gray-500 mt-1">Duration: {{ $activity->out_of_frame_duration }}s</p>
                            @endif
                        </td>
                        <td class="px-4 py-3 capitalize">{{ $activity->severity ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">No student activities recorded yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-4">{{ $activities->links() }}</div>
@endsection
