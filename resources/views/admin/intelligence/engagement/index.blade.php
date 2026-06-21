@extends('admin.intelligence.layout')
@php($pageTitle = 'Engagement Analytics')
@php($intelligencePage = 'engagement')
@section('intelligence_content')
@include('admin.intelligence.partials.stat-cards', ['columns' => 'grid-cols-2 md:grid-cols-3', 'cards' => [
    ['label' => 'Exam Participation', 'value' => number_format($data['exam_participation'] ?? 0), 'icon' => 'fa-file-alt'],
    ['label' => 'Attendance Participation', 'value' => number_format($data['attendance_participation'] ?? 0), 'icon' => 'fa-calendar-check'],
]])
@include('admin.intelligence.partials.section-card', ['title' => 'Engagement Rankings', 'items' => collect($data['rankings'] ?? [])->map(fn ($r) => ['name' => $r['student_index'] ?? '—', 'count' => $r['score'] ?? 0])->all()])
@endsection
