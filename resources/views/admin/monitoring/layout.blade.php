@extends('layouts.dashboard')

@section('body_class')
bg-slate-950 text-slate-100 h-screen overflow-hidden monitoring-theme
@endsection

@section('title', $pageTitle ?? 'Monitoring')
@section('dashboard_heading', $pageTitle ?? 'System Monitoring')

@section('dashboard_content')
<div class="w-full min-w-0 space-y-4 monitoring-dark-shell" data-monitoring-page="{{ $monitoringPage ?? 'overview' }}">
    @include('admin.monitoring.partials.nav')

    @if(session('success'))
        <div class="rounded-lg border border-emerald-500/40 bg-emerald-950/50 px-4 py-3 text-sm text-emerald-200">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-lg border border-rose-500/40 bg-rose-950/50 px-4 py-3 text-sm text-rose-200">{{ session('error') }}</div>
    @endif

    @yield('monitoring_content')
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/monitoring-theme.css') }}">
@endpush

@push('scripts')
<script src="{{ asset('js/quizsnap-monitoring.js') }}" defer></script>
@endpush
