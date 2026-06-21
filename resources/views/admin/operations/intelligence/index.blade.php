@extends('admin.operations.layout')
@php($pageTitle = 'Academic Intelligence')
@section('operations_content')
@include('admin.operations.partials.analytics-cards', ['data' => $data, 'sections' => [
    'most_active_courses' => 'Most Active Courses',
    'most_active_departments' => 'Most Active Departments',
    'course_participation' => 'Course Participation',
    'faculty_engagement' => 'Faculty Engagement',
]])
<div class="rounded-xl border bg-white p-4 shadow-sm mt-4">
    <h3 class="text-sm font-semibold mb-2">Student Engagement</h3>
    <dl class="grid grid-cols-3 gap-3 text-sm">
        @foreach($data['student_engagement'] ?? [] as $key => $value)
            <div><dt class="text-gray-500 capitalize">{{ str_replace('_', ' ', $key) }}</dt><dd class="font-semibold">{{ number_format($value) }}</dd></div>
        @endforeach
    </dl>
</div>
@endsection
