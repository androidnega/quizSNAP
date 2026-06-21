@extends('admin.operations.layout')
@php($pageTitle = 'Operations Reports')
@section('operations_content')
<div class="flex justify-between mb-4">
    <p class="text-sm text-gray-600">Consolidated operations summary (last 30 days).</p>
    <a href="{{ route('dashboard.operations.reports.export') }}" class="btn btn-secondary btn-sm" target="_blank">Export JSON</a>
</div>
<div class="rounded-xl border bg-white p-4 shadow-sm">
    <pre class="text-xs overflow-x-auto whitespace-pre-wrap">{{ json_encode($summary, JSON_PRETTY_PRINT) }}</pre>
</div>
@endsection
