@extends('admin.operations.layout')
@php($pageTitle = 'Exam Analytics')
@section('operations_content')
<div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
    @foreach(['average_score'=>'Avg Score','pass_rate'=>'Pass Rate %','failure_rate'=>'Failure Rate %','total_submissions'=>'Submissions'] as $key => $label)
        <div class="rounded-xl border bg-white p-4 shadow-sm"><p class="text-xs text-gray-500">{{ $label }}</p><p class="text-2xl font-bold">{{ $data[$key] ?? 0 }}</p></div>
    @endforeach
</div>
@include('admin.operations.partials.analytics-cards', ['data' => $data, 'sections' => [
    'course_comparison' => 'Course Comparison',
    'department_comparison' => 'Department Comparison',
    'performance_trends' => 'Performance Trends',
]])
@endsection
