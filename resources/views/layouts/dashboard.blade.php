@extends('layouts.app')

@section('title', $dashboardTitle ?? 'Dashboard')
@section('robots', 'noindex,nofollow')
@section('body_class', 'bg-[#f4f5f7] h-screen overflow-hidden')

@php
    $layoutAdminUser = auth()->user();
    if (! ($layoutAdminUser instanceof \App\Models\User) && session('admin_user_id')) {
        $layoutAdminUser = \App\Models\User::with('institution')->find(session('admin_user_id'));
    }
    $sessionRole = (string) session('admin_role', '');
    $isSuperAdmin = ($layoutAdminUser && $layoutAdminUser->isSuperAdmin())
        || in_array($sessionRole, ['super_admin', 'admin'], true);
    $isSystemAdmin = $layoutAdminUser && $layoutAdminUser->isSystemAdministrator();
    $systemAdminHome = route('dashboard');
    $canAccessMonitoring = $layoutAdminUser && $layoutAdminUser->canAccessMonitoring();
    $canAccessOperations = $layoutAdminUser && $layoutAdminUser->canAccessOperations();
    $canAccessIntelligence = $layoutAdminUser && $layoutAdminUser->canAccessIntelligence();
    $isExaminer = $sessionRole === 'examiner'
        || ($layoutAdminUser && $layoutAdminUser->role === 'examiner' && ! $isSuperAdmin && $sessionRole !== 'coordinator');
    $isCoordinatorOnly = ! $isSuperAdmin && ! $isSystemAdmin && (
        $sessionRole === 'coordinator'
        || ($layoutAdminUser && $layoutAdminUser->role === 'coordinator')
    );
    $canManageStudents = $isSuperAdmin || $isCoordinatorOnly;
    $isQuizSnapStaff = $isSuperAdmin || $isExaminer;
    $isSupportAgent = $layoutAdminUser && $layoutAdminUser->isSupportAgent();
    $canRespondToSupport = $layoutAdminUser && \App\Support\LiveSupportAccess::canRespond($layoutAdminUser);
@endphp
@section('content')
<div class="examiner-wrap flex h-screen bg-[#f4f5f7] overflow-hidden">
    <div id="examiner-overlay" class="examiner-overlay fixed inset-0 z-30 bg-black/40 md:hidden hidden" aria-hidden="true"></div>

    <aside id="examiner-sidebar" class="examiner-sidebar flex h-full flex-col w-64 flex-shrink-0 bg-white" aria-label="Dashboard navigation" data-collapsed="false">
        <div class="examiner-sidebar-inner flex flex-col h-full">
            <div class="examiner-sidebar-header flex h-[4.25rem] flex-shrink-0 items-center justify-between gap-2 px-4">
                <a href="{{ $isSystemAdmin ? $systemAdminHome : route('dashboard') }}" class="examiner-sidebar-brand flex min-w-0 flex-shrink-0 items-center gap-2.5 overflow-hidden transition-opacity hover:opacity-80" aria-label="{{ trim((string) \App\Models\Setting::getValue(\App\Models\Setting::KEY_APP_NAME, 'QuizSnap')) ?: 'QuizSnap' }} home">
                    @include('partials.brand-logo', [
                        'href' => null,
                        'size' => 'sm',
                        'variant' => 'plain',
                        'showWordmark' => true,
                        'customLogoUrl' => \App\Support\BrandAssets::logoUrl(),
                        'appName' => trim((string) \App\Models\Setting::getValue(\App\Models\Setting::KEY_APP_NAME, 'QuizSnap')) ?: 'QuizSnap',
                        'class' => 'min-w-0 examiner-sidebar-brand-logo',
                    ])
                </a>
                <button type="button" id="examiner-sidebar-toggle-inner" data-examiner-collapse class="examiner-sidebar-chevron md:hidden flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full text-gray-700 hover:bg-gray-100 transition-colors focus:outline-none focus:ring-2 focus:ring-gray-300" aria-label="Close sidebar" title="Close sidebar">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <nav class="examiner-sidebar-nav flex-1 overflow-y-auto px-2.5 py-2 space-y-0">
                <ul class="space-y-1.5" role="list">
                    @if($isSupportAgent && $canRespondToSupport)
                    <li>
                        <a href="{{ route('dashboard.support.index') }}" class="examiner-nav-link {{ request()->routeIs('dashboard.support.*') ? 'examiner-nav-link--active' : '' }} group flex items-center gap-2.5 rounded-lg py-1.5 px-2.5 text-sm font-medium min-w-0 transition-all" title="Live student support chat">
                            <svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                            <span class="examiner-nav-text truncate">Live Support</span>
                        </a>
                    </li>
                    @elseif($isSupportAgent)
                    <li>
                        <a href="{{ route('dashboard') }}" class="examiner-nav-link {{ request()->routeIs('dashboard') ? 'examiner-nav-link--active' : '' }} group flex items-center gap-2.5 rounded-lg py-1.5 px-2.5 text-sm font-medium min-w-0 transition-all">
                            <svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                            <span class="examiner-nav-text truncate">Dashboard</span>
                        </a>
                    </li>
                    @elseif($isCoordinatorOnly)
                    {{-- Coordinator sidebar: key pages; rest on Dashboard quick links --}}
                    <li>
                        <a href="{{ route('dashboard') }}" class="examiner-nav-link {{ request()->routeIs('dashboard') && !request()->is('dashboard/coordinators/*') && !request()->routeIs('dashboard.profile.*') ? 'examiner-nav-link--active' : '' }} group flex items-center gap-2.5 rounded-lg py-1.5 px-2.5 text-sm font-medium min-w-0 transition-all">
                            <svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                            <span class="examiner-nav-text truncate">Dashboard</span>
                        </a>
                    </li>
                    <li class="pt-2"></li>
                    <li><a href="{{ route('dashboard.class-groups.index') }}" class="examiner-nav-link {{ request()->routeIs('dashboard.class-groups.*') ? 'examiner-nav-link--active' : '' }} group flex items-center gap-2.5 rounded-lg py-1.5 px-2.5 text-sm font-medium min-w-0 transition-all" title="Manage academic class groups and assign examiners"><svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg><span class="examiner-nav-text truncate">Class Groups</span></a></li>
                    @if($canManageStudents)
                    <li><a href="{{ route('dashboard.students.index') }}" class="examiner-nav-link {{ request()->routeIs('dashboard.students.index') ? 'examiner-nav-link--active' : '' }} group flex items-center gap-2.5 rounded-lg py-1.5 px-2.5 text-sm font-medium min-w-0 transition-all" title="Search and manage students in your scope"><svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg><span class="examiner-nav-text truncate">Students</span></a></li>
                    @endif
                    <li><a href="{{ route('dashboard.exam-calendar.index') }}" class="examiner-nav-link {{ request()->routeIs('dashboard.exam-calendar.*') ? 'examiner-nav-link--active' : '' }} group flex items-center gap-2.5 rounded-lg py-1.5 px-2.5 text-sm font-medium min-w-0 transition-all" title="Midsem & end-of-semester exam calendar by class"><svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg><span class="examiner-nav-text truncate">Exam Calendar</span></a></li>
                    <li><a href="{{ route('dashboard.courses.index') }}" class="examiner-nav-link {{ request()->routeIs('dashboard.courses.*') ? 'examiner-nav-link--active' : '' }} group flex items-center gap-2.5 rounded-lg py-1.5 px-2.5 text-sm font-medium min-w-0 transition-all" title="Create courses and assign lecturers"><svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg><span class="examiner-nav-text truncate">Courses</span></a></li>
                    <li><a href="{{ route('dashboard.users.index') }}" class="examiner-nav-link {{ request()->routeIs('dashboard.users.*') ? 'examiner-nav-link--active' : '' }} group flex items-center gap-2.5 rounded-lg py-1.5 px-2.5 text-sm font-medium min-w-0 transition-all" title="Assign AI tokens to examiners"><svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg><span class="examiner-nav-text truncate">Examiners</span></a></li>
                    <li class="pt-3"><div class="px-3 mb-1.5 text-xs font-semibold text-gray-500 uppercase tracking-wider examiner-nav-text flex items-center gap-2"><i class="fas fa-sitemap text-[10px]"></i> Academic structure</div></li>
                    <li><a href="{{ route('dashboard.coordinators.academic-years.index') }}" class="examiner-nav-link {{ request()->routeIs('dashboard.coordinators.academic-years.*') ? 'examiner-nav-link--active' : '' }} group flex items-center gap-2.5 rounded-lg py-1.5 px-2.5 text-sm font-medium min-w-0 transition-all"><i class="fas fa-calendar-alt w-5 flex-shrink-0 text-center text-sm"></i><span class="examiner-nav-text truncate">Academic Years</span></a></li>
                    <li><a href="{{ route('dashboard.coordinators.quiz-categories.index') }}" class="examiner-nav-link {{ request()->routeIs('dashboard.coordinators.quiz-categories.*') ? 'examiner-nav-link--active' : '' }} group flex items-center gap-2.5 rounded-lg py-1.5 px-2.5 text-sm font-medium min-w-0 transition-all"><i class="fas fa-tags w-5 flex-shrink-0 text-center text-sm"></i><span class="examiner-nav-text truncate">Quiz Categories</span></a></li>
                    <li><a href="{{ route('dashboard.coordinators.semesters.index') }}" class="examiner-nav-link {{ request()->routeIs('dashboard.coordinators.semesters.*') ? 'examiner-nav-link--active' : '' }} group flex items-center gap-2.5 rounded-lg py-1.5 px-2.5 text-sm font-medium min-w-0 transition-all"><i class="fas fa-calendar-week w-5 flex-shrink-0 text-center text-sm"></i><span class="examiner-nav-text truncate">Semesters</span></a></li>
                    <li><a href="{{ route('dashboard.coordinators.academic-classes.index') }}" class="examiner-nav-link {{ request()->routeIs('dashboard.coordinators.academic-classes.*') ? 'examiner-nav-link--active' : '' }} group flex items-center gap-2.5 rounded-lg py-1.5 px-2.5 text-sm font-medium min-w-0 transition-all"><i class="fas fa-chalkboard w-5 flex-shrink-0 text-center text-sm"></i><span class="examiner-nav-text truncate">Academic Classes</span></a></li>
                    <li><a href="{{ route('dashboard.coordinators.student-levels.index') }}" class="examiner-nav-link {{ request()->routeIs('dashboard.coordinators.student-levels.*') ? 'examiner-nav-link--active' : '' }} group flex items-center gap-2.5 rounded-lg py-1.5 px-2.5 text-sm font-medium min-w-0 transition-all"><i class="fas fa-layer-group w-5 flex-shrink-0 text-center text-sm"></i><span class="examiner-nav-text truncate">Student Levels</span></a></li>
                    @if($canRespondToSupport)
                    <li><a href="{{ route('dashboard.support.index') }}" class="examiner-nav-link {{ request()->routeIs('dashboard.support.*') ? 'examiner-nav-link--active' : '' }} group flex items-center gap-2.5 rounded-lg py-1.5 px-2.5 text-sm font-medium min-w-0 transition-all" title="Live student support chat"><svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg><span class="examiner-nav-text truncate">Live Support</span></a></li>
                    @endif
                    @else
                    @if($isSystemAdmin)
                    <li>
                        <a href="{{ $systemAdminHome }}" class="examiner-nav-link {{ request()->routeIs('dashboard') && !request()->is('dashboard/*') ? 'examiner-nav-link--active' : '' }} group flex items-center gap-2.5 rounded-lg py-1.5 px-2.5 text-sm font-medium min-w-0 transition-all" title="System monitor overview">
                            <svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                            <span class="examiner-nav-text truncate">Dashboard</span>
                        </a>
                    </li>
                    <li class="pt-3"><div class="px-3 mb-1.5 text-xs font-semibold text-gray-500 uppercase tracking-wider examiner-nav-text">Enterprise Centers</div></li>
                    @include('admin.partials.enterprise-center-nav-links')
                    @else
                    <li>
                        <a href="{{ route('dashboard') }}" class="examiner-nav-link {{ request()->routeIs('dashboard') && !request()->is('dashboard/*') ? 'examiner-nav-link--active' : '' }} group flex items-center gap-2.5 rounded-lg py-1.5 px-2.5 text-sm font-medium min-w-0 transition-all" title="Overview and quick links">
                            <svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                            <span class="examiner-nav-text truncate">Dashboard</span>
                        </a>
                    </li>
                    {{-- QuizSnap: examiners see Class Groups (view/select), Quizzes, Courses --}}
                    @if($isExaminer)
                    <li>
                        <a href="{{ route('dashboard.class-groups.index') }}" class="examiner-nav-link {{ request()->routeIs('dashboard.class-groups.*') ? 'examiner-nav-link--active' : '' }} group flex items-center gap-2.5 rounded-lg py-1.5 px-2.5 text-sm font-medium min-w-0 transition-all" title="View class groups and select for quizzes">
                            <svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            <span class="examiner-nav-text truncate">Class Groups</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('dashboard.exam-calendar.index') }}" class="examiner-nav-link {{ request()->routeIs('dashboard.exam-calendar.*') ? 'examiner-nav-link--active' : '' }} group flex items-center gap-2.5 rounded-lg py-1.5 px-2.5 text-sm font-medium min-w-0 transition-all" title="Midsem & end-of-semester exam calendar">
                            <svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            <span class="examiner-nav-text truncate">Exam Calendar</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('dashboard.quizzes.index') }}" class="examiner-nav-link {{ request()->routeIs('dashboard.quizzes.*') ? 'examiner-nav-link--active' : '' }} group flex items-center gap-2.5 rounded-lg py-1.5 px-2.5 text-sm font-medium min-w-0 transition-all">
                            <svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            <span class="examiner-nav-text truncate">Quizzes</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('dashboard.courses.index') }}" class="examiner-nav-link {{ request()->routeIs('dashboard.courses.*') ? 'examiner-nav-link--active' : '' }} group flex items-center gap-2.5 rounded-lg py-1.5 px-2.5 text-sm font-medium min-w-0 transition-all" title="View your assigned courses (read-only)">
                            <svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                            <span class="examiner-nav-text truncate">Courses</span>
                        </a>
                    </li>
                    @if($canRespondToSupport)
                    <li>
                        <a href="{{ route('dashboard.support.index') }}" class="examiner-nav-link {{ request()->routeIs('dashboard.support.*') ? 'examiner-nav-link--active' : '' }} group flex items-center gap-2.5 rounded-lg py-1.5 px-2.5 text-sm font-medium min-w-0 transition-all" title="Live student support chat">
                            <svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                            <span class="examiner-nav-text truncate">Live Support</span>
                        </a>
                    </li>
                    @endif
                    @endif
                    @if($isSuperAdmin)
                    <li class="pt-2"></li>
                    <li>
                        <a href="{{ route('dashboard.class-groups.index') }}" class="examiner-nav-link {{ request()->routeIs('dashboard.class-groups.*') ? 'examiner-nav-link--active' : '' }} group flex items-center gap-2.5 rounded-lg py-1.5 px-2.5 text-sm font-medium min-w-0 transition-all" title="View all class groups across institutions">
                            <svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            <span class="examiner-nav-text truncate">Class Groups</span>
                        </a>
                    </li>
                    @if($canManageStudents)
                    <li>
                        <a href="{{ route('dashboard.students.index') }}" class="examiner-nav-link {{ request()->routeIs('dashboard.students.index') ? 'examiner-nav-link--active' : '' }} group flex items-center gap-2.5 rounded-lg py-1.5 px-2.5 text-sm font-medium min-w-0 transition-all" title="Search and manage all students">
                            <svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                            <span class="examiner-nav-text truncate">Students</span>
                        </a>
                    </li>
                    @endif
                    @if($canRespondToSupport)
                    <li>
                        <a href="{{ route('dashboard.support.index') }}" class="examiner-nav-link {{ request()->routeIs('dashboard.support.*') ? 'examiner-nav-link--active' : '' }} group flex items-center gap-2.5 rounded-lg py-1.5 px-2.5 text-sm font-medium min-w-0 transition-all" title="Live student support chat">
                            <svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                            <span class="examiner-nav-text truncate">Live Support</span>
                        </a>
                    </li>
                    @endif
                    <li class="pt-3"><div class="px-3 mb-1.5 text-xs font-semibold text-gray-500 uppercase tracking-wider examiner-nav-text">Administration</div></li>
                    <li>
                        <a href="{{ route('dashboard.institutions.index') }}" class="examiner-nav-link {{ request()->routeIs('dashboard.institutions.*') ? 'examiner-nav-link--active' : '' }} group flex items-center gap-2.5 rounded-lg py-1.5 px-2.5 text-sm font-medium min-w-0 transition-all" title="Manage institutions and assign examiners">
                            <svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                            <span class="examiner-nav-text truncate">Institutions</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('dashboard.users.index') }}" class="examiner-nav-link {{ request()->routeIs('dashboard.users.*') ? 'examiner-nav-link--active' : '' }} group flex items-center gap-2.5 rounded-lg py-1.5 px-2.5 text-sm font-medium min-w-0 transition-all" title="Manage staff, admins, and system monitors">
                            <svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                            <span class="examiner-nav-text truncate">Users</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('dashboard.student-levels.index') }}" class="examiner-nav-link {{ request()->routeIs('dashboard.student-levels.*') ? 'examiner-nav-link--active' : '' }} group flex items-center gap-2.5 rounded-lg py-1.5 px-2.5 text-sm font-medium min-w-0 transition-all" title="Student levels">
                            <svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                            <span class="examiner-nav-text truncate">Student Levels</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('dashboard.settings.index') }}" class="examiner-nav-link {{ request()->routeIs('dashboard.settings.*') ? 'examiner-nav-link--active' : '' }} group flex items-center gap-2.5 rounded-lg py-1.5 px-2.5 text-sm font-medium min-w-0 transition-all" title="Configure app, mail, AI, and storage">
                            <svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            <span class="examiner-nav-text truncate">Settings</span>
                        </a>
                    </li>
                    <li>
                        @php $isResetPage = request()->routeIs('dashboard.system.reset.*') || request()->routeIs('system.reset.*') || request()->is('dashboard/system/reset*'); @endphp
                        <a href="{{ route('dashboard.system.reset.index') }}" class="examiner-nav-link {{ $isResetPage ? 'examiner-nav-link--active' : '' }} group flex items-center gap-2.5 rounded-lg py-1.5 px-2.5 text-sm font-medium min-w-0 transition-all" title="Clear data or full system reset (use with caution)">
                            <svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            <span class="examiner-nav-text truncate">Reset</span>
                        </a>
                    </li>
                    @endif
                    @if($isSuperAdmin && ($canAccessMonitoring || $canAccessOperations || $canAccessIntelligence))
                    <li class="pt-3"><div class="px-3 mb-1.5 text-xs font-semibold text-gray-500 uppercase tracking-wider examiner-nav-text">Enterprise Centers</div></li>
                    @include('admin.partials.enterprise-center-nav-links')
                    @endif
                    @endif
                    @endif
                </ul>
            </nav>
        </div>
    </aside>

    <div class="examiner-main flex flex-col flex-1 min-w-0 min-h-0" data-quizsnap-skip-live-reload>
        @php
            $headerUser = auth()->user() ?: $layoutAdminUser;
            $headerName = trim((string) ($headerUser->name ?? $headerUser->username ?? 'Staff'));
            $headerFirst = trim((string) Str::of($headerName)->before(' '));
            if ($headerFirst === '') {
                $headerFirst = $headerName !== '' ? $headerName : 'there';
            }
            $headerInitials = Str::upper(Str::substr(preg_replace('/\s+/', '', $headerName) ?: 'U', 0, 2));
            if (str_contains($headerName, ' ')) {
                $parts = preg_split('/\s+/', $headerName) ?: [];
                $headerInitials = Str::upper(Str::substr($parts[0] ?? 'U', 0, 1) . Str::substr($parts[1] ?? '', 0, 1));
            }
            $headerRoleLabel = $isSuperAdmin ? 'Admin'
                : ($isSystemAdmin ? 'System Admin'
                : ($isCoordinatorOnly ? 'Coordinator'
                : ($isExaminer ? 'Examiner'
                : ($isSupportAgent ? 'Support' : 'Staff'))));
            $showSmsInHeader = $headerUser && method_exists($headerUser, 'isCoordinator') && $headerUser->isCoordinator();
            if ($showSmsInHeader) {
                $headerUser->refresh();
            }
            $smsRemaining = $showSmsInHeader ? $headerUser->sms_remaining : 0;
            $smsColorClass = $smsRemaining >= 100 ? 'text-emerald-700' : 'text-rose-700';

            // Header search: route-aware live search, or grayed out when unused on this page.
            $headerSearch = [
                'enabled' => false,
                'mode' => 'server',
                'action' => url()->current(),
                'param' => 'q',
                'value' => '',
                'placeholder' => 'Search unavailable',
                'proxyByTab' => [],
            ];
            if (request()->routeIs('dashboard.quizzes.index')) {
                $headerSearch = [
                    'enabled' => true,
                    'mode' => 'server',
                    'action' => route('dashboard.quizzes.index'),
                    'param' => 'q',
                    'value' => (string) request('q', ''),
                    'placeholder' => 'Search quizzes…',
                    'proxyByTab' => [],
                ];
            } elseif (request()->routeIs('dashboard.quizzes.show')) {
                $proxyTabs = [
                    'overview' => 'questions-search',
                    'sessions' => 'sessions-search-index',
                    'gallery' => 'gallery-search-index',
                ];
                $quizTab = (string) request('tab', 'overview');
                $headerSearch = [
                    'enabled' => true,
                    'mode' => 'proxy',
                    'action' => url()->current(),
                    'param' => 'q',
                    'value' => '',
                    'placeholder' => 'Search this page…',
                    'proxyByTab' => $proxyTabs,
                    'activeTab' => $quizTab,
                ];
            } elseif (request()->routeIs('dashboard.class-groups.students.index')) {
                $headerSearch = [
                    'enabled' => true,
                    'mode' => 'server',
                    'action' => url()->current(),
                    'param' => 'search',
                    'value' => (string) request('search', ''),
                    'placeholder' => 'Search index, name, phone…',
                    'proxyByTab' => [],
                ];
            } elseif (request()->routeIs('dashboard.students.index')) {
                $headerSearch = [
                    'enabled' => true,
                    'mode' => 'server',
                    'action' => route('dashboard.students.index'),
                    'param' => 'search',
                    'value' => (string) request('search', ''),
                    'placeholder' => 'Search index, name, phone…',
                    'proxyByTab' => [],
                ];
            } elseif (request()->routeIs('dashboard.users.index')) {
                $headerSearch = [
                    'enabled' => true,
                    'mode' => 'server',
                    'action' => route('dashboard.users.index'),
                    'param' => 'search',
                    'value' => (string) request('search', ''),
                    'placeholder' => 'Search username or name…',
                    'proxyByTab' => [],
                ];
            } elseif (request()->routeIs('dashboard.monitoring.errors.index')) {
                $headerSearch = [
                    'enabled' => true,
                    'mode' => 'server',
                    'action' => route('dashboard.monitoring.errors.index'),
                    'param' => 'search',
                    'value' => (string) request('search', ''),
                    'placeholder' => 'Search errors…',
                    'proxyByTab' => [],
                ];
            } elseif (request()->routeIs('dashboard.monitoring.student-activities.index')) {
                $headerSearch = [
                    'enabled' => true,
                    'mode' => 'server',
                    'action' => route('dashboard.monitoring.student-activities.index'),
                    'param' => 'search',
                    'value' => (string) request('search', ''),
                    'placeholder' => 'Search student or index…',
                    'proxyByTab' => [],
                ];
            } elseif (request()->routeIs('dashboard.monitoring.activity.index')) {
                $headerSearch = [
                    'enabled' => true,
                    'mode' => 'server',
                    'action' => route('dashboard.monitoring.activity.index'),
                    'param' => 'search',
                    'value' => (string) request('search', ''),
                    'placeholder' => 'Search activity…',
                    'proxyByTab' => [],
                ];
            } elseif (request()->routeIs('dashboard.monitoring.security.index')) {
                $headerSearch = [
                    'enabled' => true,
                    'mode' => 'server',
                    'action' => route('dashboard.monitoring.security.index'),
                    'param' => 'search',
                    'value' => (string) request('search', ''),
                    'placeholder' => 'Search security events…',
                    'proxyByTab' => [],
                ];
            }
        @endphp
        <div class="w-full shrink-0 px-3 pt-3 sm:px-4 md:px-6">
        <header class="dashboard-chrome-header relative flex flex-shrink-0 items-center z-30 min-w-0 overflow-visible safe-area-header">
            <div class="flex flex-1 flex-wrap items-center gap-2.5 sm:gap-3 w-full min-w-0 px-3 py-2 sm:px-4 overflow-visible">
                <div class="flex min-w-0 flex-1 items-center gap-2.5 sm:gap-3">
                    <button type="button" id="examiner-sidebar-menu-btn" class="dashboard-chrome-toggle flex flex-shrink-0 items-center justify-center focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-400 touch-manipulation" aria-label="Toggle sidebar" title="Toggle sidebar">
                        <i class="fas fa-bars text-sm"></i>
                    </button>
                    <div class="min-w-0">
                        <h1 class="truncate text-lg sm:text-xl font-bold tracking-tight text-gray-900 leading-tight">Hello, {{ $headerFirst }}!</h1>
                        <p class="truncate text-xs text-gray-400 mt-0.5 hidden sm:block">@hasSection('dashboard_subheading')@yield('dashboard_subheading')@elseExplore quizzes, sessions, and activity across your platform.@endif</p>
                    </div>
                </div>

                <div class="flex flex-shrink-0 items-center gap-2 sm:gap-2.5 ml-auto">
                    @if($headerUser && method_exists($headerUser, 'isExaminer') && ($headerUser->isExaminer() || $headerUser->isCoordinator()))
                        @if($headerUser->isExaminer())
                        @php
                            try {
                                $aiTokenStatus = app(\App\Services\AiQuizTokenService::class)->getStatus($headerUser);
                            } catch (\Throwable $e) {
                                report($e);
                                $aiTokenStatus = ['remaining' => 0];
                            }
                            $aiTokenColor = ($aiTokenStatus['remaining'] ?? 0) > 0 ? 'text-indigo-700' : 'text-rose-700';
                        @endphp
                        <div class="hidden lg:inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-gray-100 px-2.5 py-1 text-xs {{ $aiTokenColor }}" title="AI quiz generations remaining">
                            <span class="text-[10px] font-medium uppercase tracking-wide text-gray-500">AI</span>
                            <span class="font-semibold tabular-nums">{{ $aiTokenStatus['remaining'] }}</span>
                        </div>
                        @endif
                        @if($showSmsInHeader)
                        <div class="hidden md:inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-gray-100 px-2.5 py-1 text-xs {{ $smsColorClass }}" title="SMS balance">
                            <span class="text-[10px] font-medium uppercase tracking-wide text-gray-500">SMS</span>
                            <span class="font-semibold tabular-nums">{{ $smsRemaining }}</span>
                        </div>
                        @endif
                    @endif

                    <form
                        action="{{ $headerSearch['action'] }}"
                        method="get"
                        id="dashboard-global-search-form"
                        class="dashboard-chrome-search hidden sm:flex {{ empty($headerSearch['enabled']) ? 'is-disabled' : '' }}"
                        role="search"
                        data-search-enabled="{{ !empty($headerSearch['enabled']) ? '1' : '0' }}"
                        data-search-mode="{{ $headerSearch['mode'] }}"
                        data-search-param="{{ $headerSearch['param'] }}"
                        data-proxy-by-tab='@json($headerSearch['proxyByTab'])'
                    >
                        <label for="dashboard-global-search" class="sr-only">Search</label>
                        <input
                            id="dashboard-global-search"
                            type="search"
                            name="{{ $headerSearch['param'] }}"
                            value="{{ $headerSearch['value'] }}"
                            placeholder="{{ $headerSearch['placeholder'] }}"
                            autocomplete="off"
                            @if(empty($headerSearch['enabled'])) disabled aria-disabled="true" @endif
                        >
                        <button type="submit" aria-label="Search" @if(empty($headerSearch['enabled'])) disabled tabindex="-1" @endif><i class="fas fa-search text-xs"></i></button>
                    </form>

                    @if($canAccessMonitoring ?? false)
                        @include('admin.monitoring.partials.header-bell')
                    @else
                        <span class="dashboard-chrome-bell hidden sm:inline-flex items-center justify-center" aria-hidden="true">
                            <i class="fas fa-bell text-sm"></i>
                        </span>
                    @endif

                    <div class="relative flex flex-shrink-0 items-center" id="profile-menu-wrap">
                        <button type="button" class="dashboard-chrome-profile focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-300 touch-manipulation min-h-[44px]" aria-expanded="false" aria-haspopup="true" aria-controls="profile-menu-dropdown" id="profile-menu-btn" title="Profile">
                            <span class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg overflow-hidden {{ $headerUser && ($headerUser->avatar_url ?? null) ? '' : 'bg-rose-100 text-rose-700' }}">
                                @if($headerUser && ($headerUser->avatar_url ?? null))
                                    <img src="{{ $headerUser->avatar_url }}" alt="" class="h-full w-full object-cover" />
                                @else
                                    <span class="text-xs font-bold tracking-wide">{{ $headerInitials }}</span>
                                @endif
                            </span>
                            <span class="hidden sm:flex flex-col items-start leading-tight min-w-0 pr-0.5">
                                <span class="text-sm font-semibold text-gray-900 truncate max-w-[8rem]">{{ $headerName }}</span>
                                <span class="text-[11px] text-gray-400">{{ $headerRoleLabel }}</span>
                            </span>
                            <i class="fas fa-chevron-down text-[10px] text-gray-400 hidden sm:inline profile-chevron" aria-hidden="true"></i>
                        </button>
                        <div id="profile-menu-dropdown" class="profile-menu-panel" role="menu" aria-labelledby="profile-menu-btn" hidden>
                            <div class="profile-menu-head">
                                <span class="profile-menu-head-avatar {{ $headerUser && ($headerUser->avatar_url ?? null) ? 'bg-slate-100' : 'bg-rose-100 text-rose-700' }}">
                                    @if($headerUser && ($headerUser->avatar_url ?? null))
                                        <img src="{{ $headerUser->avatar_url }}" alt="" class="h-full w-full object-cover">
                                    @else
                                        {{ $headerInitials }}
                                    @endif
                                </span>
                                <span class="min-w-0">
                                    <span class="block text-sm font-semibold text-slate-900 truncate">{{ $headerName }}</span>
                                    <span class="block text-[11px] text-slate-500 truncate mt-0.5">{{ $headerRoleLabel }}</span>
                                </span>
                            </div>
                            <div class="profile-menu-list">
                                <a href="{{ route('dashboard.profile.show') }}" class="profile-menu-item" role="menuitem">
                                    <span class="profile-menu-item-icon" aria-hidden="true"><i class="fas fa-user"></i></span>
                                    Profile &amp; info
                                </a>
                                <a href="{{ route('dashboard.profile.password') }}" class="profile-menu-item" role="menuitem">
                                    <span class="profile-menu-item-icon" aria-hidden="true"><i class="fas fa-key"></i></span>
                                    Reset password
                                </a>
                            </div>
                            <div class="profile-menu-foot">
                                @include('partials.quizsnap-logout-form', ['action' => route('logout')])
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </header>
        </div>

        <main class="examiner-main-content flex-1 min-h-0 overflow-y-auto overflow-x-hidden bg-[#f4f5f7] overscroll-behavior-y-contain">
            @php
                $fullBleedPage = request()->routeIs('dashboard.profile.*') || request()->routeIs('dashboard.system.reset.*') || request()->routeIs('system.reset.*') || request()->is('dashboard/system/reset*');
                $fullWidthFormPage = request()->routeIs('dashboard.quizzes.create') || request()->routeIs('dashboard.quizzes.edit');
            @endphp
            <div class="examiner-page w-full min-h-full max-w-full {{ $fullBleedPage ? 'p-0' : 'px-3 py-4 sm:px-4 sm:py-6 md:px-6 md:py-8 safe-area-main' }}">
                <div class="examiner-dashboard-content w-full max-w-none overflow-x-hidden px-0">
                    @if($isCoordinatorOnly && (request()->routeIs('dashboard') || request()->routeIs('dashboard.coordinators.*') || request()->routeIs('dashboard.class-groups.*') || request()->routeIs('dashboard.courses.*') || request()->routeIs('dashboard.profile.*')))
                    <nav class="coordinator-breadcrumb flex items-center gap-2 text-sm text-gray-600 mb-4" aria-label="Breadcrumb">
                        <a href="{{ route('dashboard') }}" class="hover:text-primary-600">Dashboard</a>
                        @hasSection('breadcrumb_trail')
                            <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            @yield('breadcrumb_trail')
                        @else
                            @unless(request()->routeIs('dashboard') && !request()->is('dashboard/coordinators/*') && !request()->routeIs('dashboard.class-groups.*') && !request()->routeIs('dashboard.courses.*') && !request()->routeIs('dashboard.profile.*'))
                            <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            <span class="text-gray-900 font-medium">@yield('dashboard_heading', 'Page')</span>
                            @endunless
                        @endif
                    </nav>
                    @endif
                    @yield('dashboard_content')
                </div>
            </div>
        </main>
    </div>
    @if($canRespondToSupport && !request()->routeIs('dashboard.support.*'))
        @include('partials.support-staff-fab')
    @endif
    @include('partials.birthday-surprise-modal')
</div>
<script>
(function() {
    var KEY = 'dashboardSidebar';
    var sidebar = document.getElementById('examiner-sidebar');
    var overlay = document.getElementById('examiner-overlay');
    var menuBtn = document.getElementById('examiner-sidebar-menu-btn');
    var toggleInner = document.getElementById('examiner-sidebar-toggle-inner');
    if (!sidebar) return;
    var isDesktop = function() { return window.innerWidth >= 768; };
    var collapsed = localStorage.getItem(KEY) === 'collapsed';
    function updateMenuButton() {
        if (!menuBtn) return;
        menuBtn.style.setProperty('display', 'flex');
        menuBtn.setAttribute('aria-label', collapsed && isDesktop() ? 'Expand sidebar' : (isDesktop() ? 'Collapse sidebar' : 'Open menu'));
        menuBtn.setAttribute('title', collapsed && isDesktop() ? 'Expand sidebar' : (isDesktop() ? 'Collapse sidebar' : 'Open menu'));
    }
    function setCollapsed(c) {
        collapsed = c;
        localStorage.setItem(KEY, c ? 'collapsed' : 'expanded');
        sidebar.setAttribute('data-collapsed', c ? 'true' : 'false');
        sidebar.classList.toggle('examiner-sidebar--collapsed', c);
        // Width/transform are CSS-animated — avoid inline snaps.
        sidebar.style.width = '';
        sidebar.style.minWidth = '';
        sidebar.style.maxWidth = '';
        if (overlay) overlay.classList.toggle('hidden', c);
        if (toggleInner) { toggleInner.setAttribute('aria-label', c ? 'Expand sidebar' : 'Collapse sidebar'); toggleInner.setAttribute('title', c ? 'Expand sidebar' : 'Collapse sidebar'); }
        updateMenuButton();
    }
    function init() {
        if (isDesktop()) setCollapsed(collapsed); else setCollapsed(true);
        updateMenuButton();
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
    if (menuBtn) menuBtn.addEventListener('click', function(e) {
        e.preventDefault();
        if (isDesktop()) setCollapsed(!collapsed);
        else setCollapsed(false);
    });
    if (overlay) overlay.addEventListener('click', function() { setCollapsed(true); });
    document.addEventListener('click', function(e) {
        var collapseBtn = e.target && e.target.closest && e.target.closest('[data-examiner-collapse]');
        if (collapseBtn) { e.preventDefault(); e.stopPropagation(); if (isDesktop()) setCollapsed(!collapsed); else setCollapsed(true); }
    }, true);
    /* On mobile: close sidebar when any nav link is clicked */
    var nav = sidebar && sidebar.querySelector('.examiner-sidebar-nav');
    if (nav) nav.addEventListener('click', function(e) {
        var link = e.target && e.target.closest && e.target.closest('a[href]');
        if (link && link.getAttribute('href') && link.getAttribute('href') !== '#' && !isDesktop()) setCollapsed(true);
    });
    window.addEventListener('resize', function() {
        if (!isDesktop()) setCollapsed(true);
        updateMenuButton();
    });
    var profileBtn = document.getElementById('profile-menu-btn');
    var profileDropdown = document.getElementById('profile-menu-dropdown');
    var profileWrap = document.getElementById('profile-menu-wrap');
    if (profileBtn && profileDropdown) {
        var profileCloseTimer = null;
        function openProfileMenu() {
            if (profileCloseTimer) {
                clearTimeout(profileCloseTimer);
                profileCloseTimer = null;
            }
            profileDropdown.hidden = false;
            requestAnimationFrame(function () {
                profileDropdown.classList.add('is-open');
            });
            profileBtn.setAttribute('aria-expanded', 'true');
        }
        function closeProfileMenu() {
            profileDropdown.classList.remove('is-open');
            profileBtn.setAttribute('aria-expanded', 'false');
            profileCloseTimer = setTimeout(function () {
                if (!profileDropdown.classList.contains('is-open')) {
                    profileDropdown.hidden = true;
                }
                profileCloseTimer = null;
            }, 180);
        }
        function toggleProfileMenu(e) {
            if (e) { e.stopPropagation(); }
            if (profileDropdown.classList.contains('is-open')) closeProfileMenu();
            else openProfileMenu();
        }
        profileBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            toggleProfileMenu(e);
        });
        document.addEventListener('click', function (e) {
            if (profileWrap && profileWrap.contains(e.target)) return;
            if (profileDropdown.classList.contains('is-open')) closeProfileMenu();
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && profileDropdown.classList.contains('is-open')) {
                closeProfileMenu();
                profileBtn.focus();
            }
        });
    }
})();

(function() {
    var form = document.getElementById('dashboard-global-search-form');
    var input = document.getElementById('dashboard-global-search');
    if (!form || !input) return;

    var mode = form.getAttribute('data-search-mode') || 'server';
    var param = form.getAttribute('data-search-param') || 'q';
    var proxyByTab = {};
    try {
        proxyByTab = JSON.parse(form.getAttribute('data-proxy-by-tab') || '{}') || {};
    } catch (e) {
        proxyByTab = {};
    }
    var debounceTimer = null;
    var lastServerQuery = String(input.value || '');

    function currentTab() {
        return new URLSearchParams(window.location.search).get('tab') || 'overview';
    }

    function placeholderForTab(tab) {
        if (tab === 'sessions' || tab === 'gallery') return 'Search index…';
        if (tab === 'overview') return 'Filter questions…';
        return 'Search unavailable';
    }

    function setEnabled(enabled, placeholder) {
        form.classList.toggle('is-disabled', !enabled);
        form.setAttribute('data-search-enabled', enabled ? '1' : '0');
        input.disabled = !enabled;
        input.setAttribute('aria-disabled', enabled ? 'false' : 'true');
        if (placeholder) input.placeholder = placeholder;
        var btn = form.querySelector('button[type="submit"]');
        if (btn) {
            btn.disabled = !enabled;
            if (enabled) btn.removeAttribute('tabindex');
            else btn.setAttribute('tabindex', '-1');
        }
    }

    function resolveProxyTarget() {
        var tab = currentTab();
        var id = proxyByTab[tab] || null;
        if (!id) return null;
        var el = document.getElementById(id);
        if (el) return el;
        // Overview may only show pool search when no approved questions yet.
        if (id === 'questions-search') {
            return document.getElementById('pool-search');
        }
        return null;
    }

    function refreshProxyAvailability() {
        if (mode !== 'proxy') return;
        var tab = currentTab();
        var enabled = Object.prototype.hasOwnProperty.call(proxyByTab, tab);
        setEnabled(enabled, placeholderForTab(tab));
        if (!enabled) {
            input.value = '';
            return;
        }
        var target = resolveProxyTarget();
        if (target && document.activeElement !== input) {
            input.value = target.value || '';
        }
    }

    var searchAbort = null;

    function applyLivePayload(data, displayUrl) {
        if (typeof window.QuizsnapLiveSearchApply === 'function') {
            window.QuizsnapLiveSearchApply(data, displayUrl);
            return;
        }
        var results = document.getElementById('live-search-results');
        var pagination = document.getElementById('live-search-pagination');
        var paginationWrap = document.getElementById('live-search-pagination-wrap');
        var meta = document.getElementById('live-search-meta');
        if (results && typeof data.html === 'string') results.innerHTML = data.html;
        if (pagination) pagination.innerHTML = data.pagination || '';
        if (paginationWrap) paginationWrap.classList.toggle('hidden', !data.pagination);
        if (meta) {
            if (data.meta) {
                meta.textContent = data.meta;
                meta.classList.remove('hidden');
            } else {
                meta.classList.add('hidden');
            }
        }
        if (displayUrl) {
            try { history.replaceState(null, '', displayUrl); } catch (e) {}
        }
    }

    function runServerSearch() {
        var value = String(input.value || '').trim();
        if (value === lastServerQuery.trim()) return;
        lastServerQuery = value;

        var panel = document.getElementById('live-search-panel');
        if (panel || typeof window.QuizsnapLiveSearchRun === 'function') {
            if (typeof window.QuizsnapLiveSearchRun === 'function') {
                window.QuizsnapLiveSearchRun(value);
                return;
            }

            var action = form.getAttribute('action') || window.location.href;
            var url = new URL(action, window.location.origin);
            var current = new URL(window.location.href);
            if (url.pathname === current.pathname) {
                current.searchParams.forEach(function(v, k) {
                    if (k === param) return;
                    url.searchParams.set(k, v);
                });
            }
            if (value) url.searchParams.set(param, value);
            else url.searchParams.delete(param);
            url.searchParams.delete('page');

            var fetchUrl = new URL(url.toString());
            fetchUrl.searchParams.set('ajax', '1');
            if (searchAbort) searchAbort.abort();
            searchAbort = new AbortController();
            var results = document.getElementById('live-search-results');
            if (results) results.classList.add('opacity-60');
            fetch(fetchUrl.toString(), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
                signal: searchAbort.signal,
            })
                .then(function(r) { return r.json().then(function(data) { return { ok: r.ok, data: data }; }); })
                .then(function(payload) {
                    if (!payload.ok || !payload.data) return;
                    applyLivePayload(payload.data, url.pathname + url.search + url.hash);
                    var pageSearch = document.getElementById('student-search') || document.getElementById('student_search');
                    if (pageSearch && document.activeElement !== pageSearch) pageSearch.value = value;
                })
                .catch(function(err) {
                    if (err && err.name === 'AbortError') return;
                })
                .finally(function() {
                    if (results) results.classList.remove('opacity-60');
                });
            return;
        }

        var action = form.getAttribute('action') || window.location.href;
        var url = new URL(action, window.location.origin);
        var current = new URL(window.location.href);
        if (url.pathname === current.pathname) {
            current.searchParams.forEach(function(v, k) {
                if (k === param) return;
                url.searchParams.set(k, v);
            });
        }
        if (value) url.searchParams.set(param, value);
        else url.searchParams.delete(param);
        url.searchParams.delete('page');
        window.location.href = url.toString();
    }

    function runProxySearch() {
        refreshProxyAvailability();
        if (form.getAttribute('data-search-enabled') !== '1') return;
        var target = resolveProxyTarget();
        if (!target) return;
        target.value = input.value || '';
        target.dispatchEvent(new Event('input', { bubbles: true }));
    }

    function onLiveInput() {
        clearTimeout(debounceTimer);
        if (mode === 'proxy') {
            runProxySearch();
            return;
        }
        if (form.getAttribute('data-search-enabled') !== '1') return;
        debounceTimer = setTimeout(runServerSearch, 350);
    }

    form.addEventListener('submit', function(e) {
        if (form.getAttribute('data-search-enabled') !== '1') {
            e.preventDefault();
            return;
        }
        if (mode === 'proxy') {
            e.preventDefault();
            runProxySearch();
            return;
        }
        e.preventDefault();
        clearTimeout(debounceTimer);
        runServerSearch();
    });

    input.addEventListener('input', onLiveInput);

    if (mode === 'proxy') {
        refreshProxyAvailability();
        window.addEventListener('popstate', refreshProxyAvailability);
        ['pushState', 'replaceState'].forEach(function(method) {
            var original = history[method];
            if (typeof original !== 'function') return;
            history[method] = function() {
                var result = original.apply(this, arguments);
                setTimeout(refreshProxyAvailability, 0);
                return result;
            };
        });
        // After quiz-show AJAX tab swaps, re-bind availability.
        document.addEventListener('quizsnap:tab-loaded', refreshProxyAvailability);
    }
})();
</script>
@endsection

@if($canAccessMonitoring ?? false)
@push('scripts')
<script>window.MONITORING_ACCESS = true;</script>
<script src="{{ asset('js/quizsnap-monitoring.js') }}" defer></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var btn = document.getElementById('monitoring-notification-btn');
    var dropdown = document.getElementById('monitoring-notification-dropdown');
    var wrap = document.getElementById('monitoring-notification-wrap');
    if (!btn || !dropdown) return;
    btn.addEventListener('click', function (e) {
        e.stopPropagation();
        dropdown.classList.toggle('hidden');
    });
    document.addEventListener('click', function (e) {
        if (wrap && !wrap.contains(e.target)) dropdown.classList.add('hidden');
    });
});
</script>
@endpush
@endif

@if($canAccessOperations ?? false)
@push('scripts')
<script>window.OPERATIONS_ACCESS = true;</script>
<script src="{{ asset('js/quizsnap-operations.js') }}" defer></script>
@endpush
@endif

@if($canRespondToSupport ?? false)
@push('scripts-after-reverb')
<script>window.SUPPORT_ACCESS = true;</script>
<script>
window.QuizSnapLiveSupportAdmin = {
    baseUrl: @json(url('/dashboard/live-support')),
    staffId: @json(auth()->id()),
    prefix: 'staff-fab-',
    onWaitingCount: function(count) {
        var badge = document.getElementById('staff-support-fab-badge');
        if (!badge) return;
        if (count > 0) {
            badge.textContent = count > 9 ? '9+' : String(count);
            badge.hidden = false;
        } else {
            badge.hidden = true;
        }
    }
};
</script>
<script src="{{ asset('js/support-live-sounds.js') }}?v={{ filemtime(public_path('js/support-live-sounds.js')) }}"></script>
<script src="{{ asset('js/support-live-media.js') }}?v={{ filemtime(public_path('js/support-live-media.js')) }}"></script>
<script src="{{ asset('js/support-live-compose.js') }}?v={{ filemtime(public_path('js/support-live-compose.js')) }}"></script>
<script src="{{ asset('js/support-live-admin.js') }}?v={{ filemtime(public_path('js/support-live-admin.js')) }}"></script>
@endpush
@endif

@if($canAccessIntelligence ?? false)
@push('scripts')
<script>window.INTELLIGENCE_ACCESS = true;</script>
<script src="{{ asset('js/quizsnap-intelligence.js') }}" defer></script>
@endpush
@endif
