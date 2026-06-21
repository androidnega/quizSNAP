@extends('admin.monitoring.layout')
@php($pageTitle = 'Security Monitor')
@section('monitoring_content')
@include('admin.monitoring.partials.log-table', ['rows' => $events, 'columns' => [
    ['key' => 'occurred_at', 'label' => 'When'],
    ['key' => 'event_type', 'label' => 'Type'],
    ['key' => 'severity', 'label' => 'Severity'],
    ['key' => 'risk_score', 'label' => 'Risk'],
    ['key' => 'user_name', 'label' => 'User'],
    ['key' => 'description', 'label' => 'Description'],
]])
@endsection
