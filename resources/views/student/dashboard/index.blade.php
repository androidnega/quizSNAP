@extends('layouts.student-dashboard')

@section('title', 'Dashboard')
@php $dashboardTitle = 'Dashboard'; @endphp

@section('body_extra_class')
min-h-screen @if(($studentDashboardMobileLayout ?? \App\Models\Setting::getStudentDashboardMobileLayout()) === 'modern') sd-home-mobile-modern @endif
@endsection

@section('dashboard_content')
@php
    $studentDashboardMobileLayout = $studentDashboardMobileLayout ?? \App\Models\Setting::getStudentDashboardMobileLayout();
    $isModernMobile = $studentDashboardMobileLayout === 'modern';
@endphp

<div class="space-y-4 lg:space-y-5 xl:space-y-3 sd-home-compact">
    <header class="@if($isModernMobile) hidden lg:block @endif xl:mb-0">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0 flex-1">
                <h1 class="text-xl sm:text-2xl lg:text-[1.75rem] xl:text-[1.65rem] font-bold text-slate-900 tracking-tight">{{ $greeting ?? 'Hello' }}, {{ $displayName ?? $student?->first_name ?? 'User' }}</h1>
                <p class="text-sm lg:text-base xl:text-sm text-slate-600 mt-1 lg:mt-1.5 xl:mt-1">Your quiz history and quick actions.</p>
            </div>
            @if(! $isModernMobile && ($student ?? null))
            <div class="lg:hidden shrink-0 pt-0.5">
                @include('student.partials.dashboard-student-notifications')
            </div>
            @endif
        </div>
    </header>

    @include('student.partials.dashboard-pill-nav', ['class' => 'hidden lg:block xl:mb-0'])

    @if(! $isModernMobile)
        @include('student.partials.dashboard-pill-nav', ['class' => 'lg:hidden mb-3', 'compact' => true, 'mobile' => true])
    @endif

    @if($isModernMobile)
        @include('student.partials.dashboard-mobile-modern')
    @endif

    <div class="xl:grid xl:grid-cols-12 xl:gap-4 xl:items-start @if($isModernMobile) hidden lg:block @endif">
        <div class="xl:col-span-7 space-y-3 @if($isModernMobile) hidden lg:block @endif">
            <div class="@if($isModernMobile) hidden lg:block @endif">
                @include('student.partials.dashboard-hero-banner')
            </div>
        </div>
        <div class="xl:col-span-5 mt-4 xl:mt-0">
            @include('student.partials.dashboard-at-a-glance')
        </div>
    </div>
</div>

@include('student.partials.dashboard-glance-styles')
@include('student.partials.dashboard-countdown-script')
@endsection
