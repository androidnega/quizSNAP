@extends('layouts.student-dashboard')

@section('title', 'Dashboard')
@php $dashboardTitle = 'Dashboard'; @endphp

@section('dashboard_content')
@php
    $studentDashboardMobileLayout = $studentDashboardMobileLayout ?? \App\Models\Setting::getStudentDashboardMobileLayout();
    $isModernMobile = $studentDashboardMobileLayout === 'modern';
@endphp

@include('student.partials.dashboard-overview-quiz-state')

<div class="space-y-5 lg:space-y-10">
    <header class="@if($isModernMobile) hidden lg:block @endif">
        <h1 class="text-xl sm:text-2xl lg:text-[1.75rem] xl:text-3xl font-bold text-slate-900 tracking-tight">{{ $greeting ?? 'Hello' }}, {{ $displayName ?? $student?->first_name ?? 'User' }}</h1>
        <p class="text-sm lg:text-base text-slate-600 mt-1.5 lg:mt-2">Your quiz history and quick actions.</p>
    </header>

    <div class="@if($isModernMobile) hidden lg:block @endif">
        @include('student.partials.dashboard-hero-banner')
    </div>

    @include('student.partials.dashboard-pill-nav', ['class' => 'hidden lg:block'])

    @if(! $isModernMobile)
        @include('student.partials.dashboard-pill-nav', ['class' => 'lg:hidden mb-3', 'compact' => true, 'mobile' => true])
    @endif

    @if($isModernMobile)
        @include('student.partials.dashboard-mobile-modern')
    @endif

    <div class="@if($isModernMobile) hidden lg:block @endif">
        @include('student.partials.dashboard-at-a-glance')
    </div>
</div>

@include('student.partials.dashboard-glance-styles')
@include('student.partials.dashboard-countdown-script')
@endsection
