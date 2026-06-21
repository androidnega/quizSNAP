@extends('admin.intelligence.layout')
@php($pageTitle = 'Integrity Analytics')
@php($intelligencePage = 'integrity')
@section('intelligence_content')
@include('admin.intelligence.partials.performance-cards', ['data' => $data])
@include('admin.intelligence.partials.section-card', ['title' => 'Score Distribution', 'items' => collect($data['score_distribution'] ?? [])->map(fn ($count, $bucket) => ['name' => $bucket, 'count' => $count])->values()->all()])
@endsection
