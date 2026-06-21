@extends('admin.operations.layout')
@php($pageTitle = 'Attendance Analytics')
@section('operations_content')
<div class="grid grid-cols-2 md:grid-cols-3 gap-3 mb-4">
    <div class="rounded-xl border bg-white p-4 shadow-sm"><p class="text-xs text-gray-500">Attendance Rate</p><p class="text-2xl font-bold">{{ $data['attendance_rate'] ?? 0 }}%</p></div>
    <div class="rounded-xl border bg-white p-4 shadow-sm"><p class="text-xs text-gray-500">Absenteeism</p><p class="text-2xl font-bold">{{ $data['absenteeism_rate'] ?? 0 }}%</p></div>
    <div class="rounded-xl border bg-white p-4 shadow-sm"><p class="text-xs text-gray-500">Late Arrivals</p><p class="text-2xl font-bold">{{ $data['late_arrivals'] ?? 0 }}</p></div>
</div>
@include('admin.operations.partials.analytics-cards', ['data' => $data, 'sections' => [
    'department_attendance' => 'Department Attendance',
    'course_attendance' => 'Course Attendance',
    'student_history' => 'Student Activity',
]])
@endsection
