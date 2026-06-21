@extends('admin.intelligence.layout')
@php($pageTitle = 'Executive Dashboard')
@php($intelligencePage = 'executive')
@section('intelligence_content')
<div id="intelligence-executive-root">
    @include('admin.intelligence.partials.stat-cards', ['cards' => [
        ['label' => 'Institution Health', 'value' => number_format($payload['institution_health_score'] ?? 0), 'stat' => 'institution_health_score', 'icon' => 'fa-university'],
        ['label' => 'Academic Health', 'value' => number_format($payload['academic_health_score'] ?? 0), 'stat' => 'academic_health_score', 'icon' => 'fa-graduation-cap'],
        ['label' => 'Student Success', 'value' => number_format($payload['student_success_score'] ?? 0), 'stat' => 'student_success_score', 'icon' => 'fa-user-graduate'],
        ['label' => 'Attendance', 'value' => number_format($payload['attendance_score'] ?? 0), 'stat' => 'attendance_score', 'icon' => 'fa-calendar-check'],
        ['label' => 'Integrity', 'value' => number_format($payload['integrity_score'] ?? 0), 'stat' => 'integrity_score', 'icon' => 'fa-shield-alt'],
        ['label' => 'Risk Score', 'value' => number_format($payload['risk_score'] ?? 0), 'stat' => 'risk_score', 'icon' => 'fa-exclamation-triangle'],
    ]])
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
        @include('admin.intelligence.partials.section-card', ['title' => 'Course Rankings', 'items' => collect($payload['course_rankings'] ?? [])->map(fn ($r) => ['name' => $r['name'] ?? '—', 'count' => $r['avg_score'] ?? 0])->all()])
        @include('admin.intelligence.partials.section-card', ['title' => 'Faculty Rankings', 'items' => collect($payload['faculty_rankings'] ?? [])->map(fn ($r) => ['name' => $r['name'] ?? '—', 'count' => $r['effectiveness_score'] ?? 0])->all()])
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <h3 class="text-sm font-semibold text-gray-900 mb-3">Risk Overview</h3>
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between rounded-lg bg-gray-50 px-3 py-2"><dt class="text-gray-500">Risk level</dt><dd class="font-semibold capitalize" data-intelligence-stat="risk_level">{{ $payload['risk_level'] ?? '—' }}</dd></div>
                <div class="flex justify-between rounded-lg bg-gray-50 px-3 py-2"><dt class="text-gray-500">At-risk students</dt><dd class="font-semibold tabular-nums" data-intelligence-stat="at_risk_count">{{ $payload['at_risk_count'] ?? 0 }}</dd></div>
                <div class="flex justify-between rounded-lg bg-gray-50 px-3 py-2"><dt class="text-gray-500">Open warnings</dt><dd class="font-semibold tabular-nums" data-intelligence-stat="open_warnings">{{ $payload['open_warnings'] ?? 0 }}</dd></div>
            </dl>
        </div>
    </div>
</div>
@endsection
