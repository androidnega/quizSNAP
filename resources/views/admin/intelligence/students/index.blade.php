@extends('admin.intelligence.layout')
@php($pageTitle = 'Student Intelligence')
@section('intelligence_content')
@include('admin.intelligence.partials.student-table', ['title' => 'Top Students', 'rows' => $data['top_students'] ?? []])
@include('admin.intelligence.partials.student-table', ['title' => 'At-Risk Students', 'rows' => $data['at_risk_students'] ?? []])
@include('admin.intelligence.partials.student-table', ['title' => 'Improving Students', 'rows' => $data['improving_students'] ?? []])
@include('admin.intelligence.partials.student-table', ['title' => 'Declining Students', 'rows' => $data['declining_students'] ?? []])
@endsection
