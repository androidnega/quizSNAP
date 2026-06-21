@extends('admin.monitoring.layout')
@php($pageTitle = 'Failed Jobs')
@section('monitoring_content')
<div class="flex flex-wrap gap-2 mb-4">
    <form method="post" action="{{ route('dashboard.monitoring.queue.retry-all') }}">@csrf<button class="btn btn-primary btn-sm">Retry All</button></form>
    <form method="post" action="{{ route('dashboard.monitoring.queue.delete-all') }}" onsubmit="return confirm('Delete all failed jobs?')">@csrf<button class="btn btn-danger btn-sm">Delete All</button></form>
</div>
@include('admin.monitoring.partials.failed-jobs-table', ['failedJobs' => $failedJobs])
@endsection
