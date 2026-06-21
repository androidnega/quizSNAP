@extends('admin.intelligence.layout')
@php($pageTitle = 'Academic Intelligence')
@section('intelligence_content')
@include('admin.operations.partials.analytics-cards', ['data' => $data, 'sections' => [
    'most_active_courses' => 'Most Active Courses',
    'most_active_departments' => 'Most Active Departments',
    'course_participation' => 'Course Participation',
    'faculty_engagement' => 'Faculty Engagement',
]])
@endsection
