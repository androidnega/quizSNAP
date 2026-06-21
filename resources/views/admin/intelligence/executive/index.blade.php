@extends('admin.intelligence.layout')
@php($pageTitle = 'Executive Dashboard')
@php($intelligencePage = 'executive')
@section('intelligence_content')
<div id="intelligence-executive-root">
    <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-3 mb-4">
        @foreach([
            'institution_health_score' => 'Institution Health',
            'academic_health_score' => 'Academic Health',
            'student_success_score' => 'Student Success',
            'attendance_score' => 'Attendance',
            'integrity_score' => 'Integrity',
            'risk_score' => 'Risk Score',
        ] as $key => $label)
            <div class="rounded-xl border bg-white p-4 shadow-sm">
                <p class="text-xs text-gray-500">{{ $label }}</p>
                <p class="text-2xl font-bold tabular-nums text-violet-700" data-intelligence-stat="{{ $key }}">{{ number_format($payload[$key] ?? 0) }}</p>
            </div>
        @endforeach
    </div>
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
        <div class="rounded-xl border bg-white p-4 shadow-sm">
            <h3 class="text-sm font-semibold mb-2">Course Rankings</h3>
            <ul class="space-y-1 text-sm">@forelse($payload['course_rankings'] ?? [] as $row)<li class="flex justify-between border-b py-1"><span>{{ $row['name'] ?? '—' }}</span><strong>{{ $row['avg_score'] ?? '—' }}</strong></li>@empty<li class="text-gray-500">No data</li>@endforelse</ul>
        </div>
        <div class="rounded-xl border bg-white p-4 shadow-sm">
            <h3 class="text-sm font-semibold mb-2">Faculty Rankings</h3>
            <ul class="space-y-1 text-sm">@forelse($payload['faculty_rankings'] ?? [] as $row)<li class="flex justify-between border-b py-1"><span>{{ $row['name'] ?? '—' }}</span><strong>{{ $row['effectiveness_score'] ?? '—' }}</strong></li>@empty<li class="text-gray-500">No data</li>@endforelse</ul>
        </div>
        <div class="rounded-xl border bg-white p-4 shadow-sm">
            <h3 class="text-sm font-semibold mb-2">Risk Overview</h3>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-gray-500">Risk level</dt><dd data-intelligence-stat="risk_level">{{ $payload['risk_level'] ?? '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">At-risk students</dt><dd data-intelligence-stat="at_risk_count">{{ $payload['at_risk_count'] ?? 0 }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Open warnings</dt><dd data-intelligence-stat="open_warnings">{{ $payload['open_warnings'] ?? 0 }}</dd></div>
            </dl>
        </div>
    </div>
</div>
@endsection
