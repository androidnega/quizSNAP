@extends('admin.monitoring.layout')
@php($pageTitle = 'Activity Logs')
@section('monitoring_content')
@include('admin.monitoring.partials.log-table', ['rows' => $logs, 'columns' => [
    ['key' => 'occurred_at', 'label' => 'When'],
    ['key' => 'user_name', 'label' => 'User'],
    ['key' => 'action', 'label' => 'Action'],
    ['key' => 'subject_type', 'label' => 'Subject'],
]])
@endsection
