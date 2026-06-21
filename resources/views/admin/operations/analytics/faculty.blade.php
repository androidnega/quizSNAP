@extends('admin.operations.layout')
@php($pageTitle = 'Faculty Analytics')
@section('operations_content')
@include('admin.operations.partials.analytics-cards', ['data' => $data, 'sections' => [
    'exams_created' => 'Exams Created',
    'attendance_sessions' => 'Attendance Uploads',
    'student_engagement' => 'Student Sessions',
    'course_activity' => 'Course Activity',
]])
<div class="rounded-xl border bg-white p-4 shadow-sm mt-4">
    <h3 class="text-sm font-semibold mb-2">Usage Statistics</h3>
    <dl class="grid grid-cols-3 gap-3 text-sm">
        @foreach($data['usage_statistics'] ?? [] as $key => $value)
            <div><dt class="text-gray-500 capitalize">{{ str_replace('_', ' ', $key) }}</dt><dd class="font-semibold">{{ is_numeric($value) ? number_format($value) : $value }}</dd></div>
        @endforeach
    </dl>
</div>
@endsection
