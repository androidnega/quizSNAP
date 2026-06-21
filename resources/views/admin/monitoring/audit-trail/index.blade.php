@extends('admin.monitoring.layout')
@php($pageTitle = 'Audit Trail')
@section('monitoring_content')
@include('admin.monitoring.partials.log-table', ['rows' => $logs, 'columns' => [
    ['key' => 'occurred_at', 'label' => 'When'],
    ['key' => 'user_name', 'label' => 'Who'],
    ['key' => 'action', 'label' => 'What'],
    ['key' => 'subject_type', 'label' => 'Subject'],
    ['key' => 'ip_address', 'label' => 'IP'],
]])
@endsection
