@extends('layouts.dashboard')

@section('title', $pageTitle ?? 'Monitoring')
@section('dashboard_heading', $pageTitle ?? 'System Monitoring')

@section('dashboard_content')
<div class="w-full min-w-0 space-y-4" data-monitoring-page="{{ $monitoringPage ?? 'overview' }}">
    @include('admin.monitoring.partials.nav')

    @if(session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
    @endif

    @yield('monitoring_content')
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/quizsnap-monitoring.js') }}" defer></script>
@endpush
