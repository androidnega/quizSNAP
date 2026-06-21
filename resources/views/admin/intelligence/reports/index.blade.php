@extends('admin.intelligence.layout')
@php($pageTitle = 'Executive Reports')
@section('intelligence_content')
<div class="flex flex-wrap gap-2 mb-4">
    <a href="{{ route('dashboard.intelligence.reports.export.pdf') }}" class="btn btn-primary btn-sm">Export PDF</a>
    <a href="{{ route('dashboard.intelligence.reports.export.excel') }}" class="btn btn-secondary btn-sm">Export Excel</a>
    <a href="{{ route('dashboard.intelligence.reports.export.csv') }}" class="btn btn-secondary btn-sm">Export CSV</a>
    <a href="{{ route('dashboard.intelligence.reports.export.json') }}" class="btn btn-secondary btn-sm" target="_blank">Export JSON</a>
</div>
<div class="rounded-xl border bg-white p-4 shadow-sm"><pre class="text-xs overflow-x-auto whitespace-pre-wrap">{{ json_encode($summary, JSON_PRETTY_PRINT) }}</pre></div>
@endsection
