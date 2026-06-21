@extends('layouts.dashboard')

@section('title', $pageTitle ?? 'Monitoring')
@section('dashboard_heading', $pageTitle ?? 'System Monitoring')

@section('dashboard_content')
<div class="w-full min-w-0 space-y-4 {{ ($monitoringDark ?? false) ? 'monitoring-dark-shell' : '' }}" data-monitoring-page="{{ $monitoringPage ?? 'overview' }}">
    @include('admin.monitoring.partials.nav')

    @if(session('success'))
        <div class="rounded-lg border px-4 py-3 text-sm {{ ($monitoringDark ?? false) ? 'border-emerald-500/40 bg-emerald-950/50 text-emerald-200' : 'border-green-200 bg-green-50 text-green-800' }}">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-lg border px-4 py-3 text-sm {{ ($monitoringDark ?? false) ? 'border-rose-500/40 bg-rose-950/50 text-rose-200' : 'border-red-200 bg-red-50 text-red-800' }}">{{ session('error') }}</div>
    @endif

    @yield('monitoring_content')
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/quizsnap-monitoring.js') }}" defer></script>
@endpush

@push('styles')
<style>
.monitoring-breathe-dot {
    display: inline-block;
    width: 10px;
    height: 10px;
    border-radius: 9999px;
    background: #34d399;
    box-shadow: 0 0 0 0 rgba(52, 211, 153, 0.6);
    animation: monitoring-breathe 2s ease-in-out infinite;
}
.monitoring-breathe-dot--xs { width: 6px; height: 6px; }
.monitoring-breathe-dot--sm { width: 8px; height: 8px; }
@keyframes monitoring-breathe {
    0%, 100% { opacity: 1; transform: scale(1); box-shadow: 0 0 0 0 rgba(52, 211, 153, 0.5); }
    50% { opacity: 0.65; transform: scale(0.92); box-shadow: 0 0 0 6px rgba(52, 211, 153, 0); }
}
</style>
@endpush
