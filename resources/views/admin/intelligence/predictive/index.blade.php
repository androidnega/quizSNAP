@extends('admin.intelligence.layout')
@php($pageTitle = 'Predictive Analytics')
@php($intelligencePage = 'predictive')
@section('intelligence_content')
@include('admin.intelligence.partials.stat-cards', ['columns' => 'grid-cols-2 md:grid-cols-4', 'cards' => collect($data['institution'] ?? [])->map(fn ($value, $key) => ['label' => ucwords(str_replace('_', ' ', $key)), 'value' => $value.'%', 'icon' => 'fa-chart-line'])->values()->all()])
<div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
    @include('admin.intelligence.partials.section-card', ['title' => 'Course Risk', 'items' => collect($data['course_risks'] ?? [])->map(fn ($r) => ['name' => $r['name'] ?? '—', 'count' => $r['risk_score'] ?? 0])->all()])
    @include('admin.intelligence.partials.section-card', ['title' => 'Student Predictions', 'items' => collect(array_slice($data['student_predictions'] ?? [], 0, 15))->map(fn ($r) => ['name' => $r['student_index'] ?? '—', 'count' => ($r['likely_pass'] ?? 0).'% pass'])->all()])
</div>
@endsection
