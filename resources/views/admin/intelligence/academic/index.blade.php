@extends('admin.intelligence.layout')
@php($pageTitle = 'Academic Intelligence')
@php($intelligencePage = 'academic')
@section('intelligence_content')
<div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
    @include('admin.intelligence.partials.section-card', ['title' => 'Most Active Courses', 'items' => $data['most_active_courses'] ?? []])
    @include('admin.intelligence.partials.section-card', ['title' => 'Most Active Departments', 'items' => $data['most_active_departments'] ?? []])
    @include('admin.intelligence.partials.section-card', ['title' => 'Course Participation', 'items' => $data['course_participation'] ?? []])
    @include('admin.intelligence.partials.section-card', ['title' => 'Faculty Engagement', 'items' => $data['faculty_engagement'] ?? []])
</div>
@endsection
