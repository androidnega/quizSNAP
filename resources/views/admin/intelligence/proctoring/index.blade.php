@extends('admin.intelligence.layout')
@php($pageTitle = 'AI Proctoring Analytics')
@php($intelligencePage = 'proctoring')
@section('intelligence_content')
@include('admin.intelligence.partials.stat-cards', ['columns' => 'grid-cols-2 md:grid-cols-4', 'cards' => [
    ['label' => 'Integrity Score', 'value' => number_format($data['integrity_score'] ?? 0), 'icon' => 'fa-shield-alt'],
    ['label' => 'Risk Score', 'value' => number_format($data['risk_score'] ?? 0), 'icon' => 'fa-exclamation-circle'],
    ['label' => 'Flagged Students', 'value' => number_format($data['summary']['flagged_students'] ?? 0), 'icon' => 'fa-user-slash'],
    ['label' => 'Total Violations', 'value' => number_format($data['summary']['total_violations'] ?? 0), 'icon' => 'fa-video'],
]])
@include('admin.intelligence.partials.stat-cards', ['columns' => 'grid-cols-2 md:grid-cols-3 xl:grid-cols-6', 'cards' => collect([
    'face_verification_failures' => 'Face Failures',
    'multiple_faces' => 'Multiple Faces',
    'phone_detected' => 'Phone',
    'tab_switching' => 'Tab Switch',
    'copy_paste' => 'Copy/Paste',
    'window_blur' => 'Window Blur',
])->map(fn ($label, $key) => ['label' => $label, 'value' => number_format($data['summary'][$key] ?? 0)])->values()->all()])
@include('admin.intelligence.partials.section-card', ['title' => 'Repeat Offenders', 'items' => collect($data['repeat_offenders'] ?? [])->map(fn ($r) => ['name' => $r['student_index'] ?? '—', 'count' => $r['violations'] ?? 0])->all()])
@endsection
