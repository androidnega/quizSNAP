@extends('layouts.app')

@section('title', $dashboardTitle ?? 'My Dashboard')
@section('body_class', 'theme-bg')
@section('body_extra_class', 'min-h-screen')

@push('styles')
<style>
@include('partials.support-fab-styles')
@media (max-width: 1023px) {
    #student-dashboard-support-fab { display: none !important; }
}
</style>
@endpush

@section('content')
@php
    use App\Support\SupportContact;

    $studentNavHome = request()->routeIs('dashboard') && !request()->routeIs('dashboard.my-*') && !request()->routeIs('dashboard.course-materials') && !request()->routeIs('dashboard.calendar');
    $breadcrumbLabel = 'Dashboard';
    if (request()->routeIs('dashboard.my-quizzes*')) { $breadcrumbLabel = 'Quizzes'; }
    elseif (request()->routeIs('dashboard.course-materials')) { $breadcrumbLabel = 'Materials'; }
    elseif (request()->routeIs('dashboard.calendar')) { $breadcrumbLabel = 'Calendar'; }
    elseif (request()->routeIs('dashboard.my-profile')) { $breadcrumbLabel = 'Profile'; }
    $appName = trim((string) \App\Models\Setting::getValue(\App\Models\Setting::KEY_APP_NAME, 'QuizSnap'));
    if ($appName === '') {
        $appName = 'QuizSnap';
    }

    $supportContext = [];
    if (isset($student) && $student) {
        $supportContext = array_filter([
            'name' => $student->display_name ?? null,
            'index_number' => $student->index_number ?? null,
        ]);
    }
@endphp
<div class="min-h-screen flex flex-col theme-bg" id="student-dashboard-wrap">
    <header class="hidden lg:block sticky top-0 z-30 theme-header">
        <div class="mx-auto flex h-14 lg:h-16 w-full max-w-none lg:max-w-4xl xl:max-w-6xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
            <div class="flex lg:hidden items-center gap-2.5 min-w-0 flex-1">
                <button type="button" id="student-mobile-menu-btn" class="shrink-0 flex h-10 w-10 items-center justify-center rounded-xl theme-header-text theme-header-hover focus:outline-none focus:ring-2 focus:ring-slate-700 focus:ring-offset-2 focus:ring-offset-[var(--theme-brand)]" aria-label="Open menu" aria-expanded="false" aria-controls="student-mobile-sidebar">
                    <i class="fas fa-bars text-base"></i>
                </button>
                @include('partials.brand-logo', [
                    'appName' => $appName,
                    'href' => route('dashboard'),
                    'size' => 'sm',
                    'variant' => 'on-brand',
                    'class' => 'shrink-0',
                ])
            </div>
            @include('partials.brand-logo', [
                'appName' => $appName,
                'href' => route('dashboard'),
                'size' => 'lg',
                'variant' => 'on-brand',
                'class' => 'hidden lg:inline-flex shrink-0',
            ])

            <nav class="hidden lg:flex items-center gap-1 flex-1 justify-center min-w-0" aria-label="Dashboard navigation">
                <a href="{{ route('dashboard') }}" class="px-3 xl:px-4 py-2 rounded-xl text-sm font-semibold transition-colors theme-header-text {{ $studentNavHome ? 'theme-nav-active' : 'theme-nav-idle' }}"><i class="fas fa-home mr-1.5 xl:mr-2 text-xs"></i>Home</a>
                <a href="{{ route('dashboard.my-quizzes') }}" class="px-3 xl:px-4 py-2 rounded-xl text-sm font-semibold transition-colors theme-header-text {{ request()->routeIs('dashboard.my-quizzes*') ? 'theme-nav-active' : 'theme-nav-idle' }}"><i class="fas fa-clipboard-list mr-1.5 xl:mr-2 text-xs"></i>Quizzes</a>
                <a href="{{ route('dashboard.calendar') }}" class="px-3 xl:px-4 py-2 rounded-xl text-sm font-semibold transition-colors theme-header-text {{ request()->routeIs('dashboard.calendar') ? 'theme-nav-active' : 'theme-nav-idle' }}"><i class="fas fa-calendar-alt mr-1.5 xl:mr-2 text-xs"></i>Calendar</a>
                <a href="{{ route('dashboard.course-materials') }}" class="px-3 xl:px-4 py-2 rounded-xl text-sm font-semibold transition-colors theme-header-text {{ request()->routeIs('dashboard.course-materials') ? 'theme-nav-active' : 'theme-nav-idle' }}"><i class="fas fa-book mr-1.5 xl:mr-2 text-xs"></i>Materials</a>
                <a href="{{ route('dashboard.my-profile') }}" class="px-3 xl:px-4 py-2 rounded-xl text-sm font-semibold transition-colors theme-header-text {{ request()->routeIs('dashboard.my-profile') ? 'theme-nav-active' : 'theme-nav-idle' }}"><i class="fas fa-user mr-1.5 xl:mr-2 text-xs"></i>Profile</a>
            </nav>

            @if(isset($student) && $student)
            <div class="relative shrink-0" id="student-profile-menu">
                <button type="button" id="student-profile-btn" class="flex items-center gap-2 rounded-xl py-1.5 pl-1.5 pr-2 lg:py-2 lg:pl-2 lg:pr-4 theme-header-hover focus:outline-none focus:ring-2 focus:ring-slate-700 focus:ring-offset-2 focus:ring-offset-[var(--theme-brand)] transition-colors" aria-expanded="false" aria-haspopup="true" aria-controls="student-profile-dropdown">
                    <span class="flex h-9 w-9 items-center justify-center rounded-full bg-white/80 text-slate-800 font-medium text-sm"><i class="fas fa-user text-xs"></i></span>
                    <span class="block text-left max-w-[76px] sm:max-w-[100px] lg:max-w-[140px] truncate">
                        <span class="block text-xs lg:text-sm font-semibold text-slate-900 truncate">{{ $student->first_name }}</span>
                        <span class="block text-[10px] lg:text-xs text-slate-700 font-mono truncate">{{ $student->index_number }}</span>
                    </span>
                    <i class="fas fa-chevron-down text-slate-700 text-[10px] lg:text-xs"></i>
                </button>
                <div id="student-profile-dropdown" class="absolute right-0 mt-2 w-56 rounded-xl border border-slate-200 bg-white py-1 z-50 hidden shadow-lg" role="menu">
                    <div class="px-4 py-3 border-b border-slate-100">
                        <p class="text-sm font-medium text-slate-800 truncate">{{ $student->display_name }}</p>
                        <p class="text-xs text-slate-500 font-mono">{{ $student->index_number }}</p>
                    </div>
                    <a href="{{ route('dashboard.my-profile') }}" class="block px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50 transition-colors" role="menuitem"><i class="fas fa-user mr-2 text-slate-400 text-xs"></i>Profile</a>
                    <form action="{{ route('student.account.logout') }}" method="post" class="block">
                        @csrf
                        <button type="submit" class="w-full text-left px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50 transition-colors" role="menuitem"><i class="fas fa-sign-out-alt mr-2 text-slate-400 text-xs"></i>Log out</button>
                    </form>
                </div>
            </div>
            <script>
            (function(){var btn=document.getElementById('student-profile-btn');var drop=document.getElementById('student-profile-dropdown');if(!btn||!drop)return;function open(){drop.classList.remove('hidden');btn.setAttribute('aria-expanded','true');}function close(){drop.classList.add('hidden');btn.setAttribute('aria-expanded','false');}btn.addEventListener('click',function(e){e.stopPropagation();if(drop.classList.contains('hidden'))open();else close();});document.addEventListener('click',function(){close();});drop.addEventListener('click',function(e){e.stopPropagation();});})();
            </script>
            @elseif(isset($user) && $user)
            <div class="relative shrink-0" id="student-profile-menu">
                <button type="button" id="student-profile-btn" class="flex items-center gap-2 rounded-full lg:rounded-xl py-2 pl-2 pr-2 lg:pr-4 theme-header-hover focus:outline-none focus:ring-2 focus:ring-slate-700 focus:ring-offset-2 focus:ring-offset-[var(--theme-brand)] transition-colors" aria-expanded="false" aria-haspopup="true" aria-controls="student-profile-dropdown">
                    <span class="flex h-9 w-9 items-center justify-center rounded-full bg-white/80 text-slate-800 font-medium text-sm"><i class="fas fa-user text-xs"></i></span>
                    <span class="hidden lg:block text-left max-w-[140px] truncate text-sm font-semibold text-slate-900 truncate">{{ $user->name ?? $user->username }}</span>
                    <i class="fas fa-chevron-down text-slate-700 text-xs hidden lg:block"></i>
                </button>
                <div id="student-profile-dropdown" class="absolute right-0 mt-2 w-56 rounded-xl border border-slate-200 bg-white py-1 z-50 hidden shadow-lg" role="menu">
                    <div class="px-4 py-3 border-b border-slate-100">
                        <p class="text-sm font-medium text-slate-800 truncate">{{ $user->name ?? $user->username }}</p>
                    </div>
                    <form action="{{ route('logout') }}" method="post" class="block">
                        @csrf
                        <button type="submit" class="w-full text-left px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50 transition-colors" role="menuitem"><i class="fas fa-sign-out-alt mr-2 text-slate-400 text-xs"></i>Log out</button>
                    </form>
                </div>
            </div>
            <script>
            (function(){var btn=document.getElementById('student-profile-btn');var drop=document.getElementById('student-profile-dropdown');if(!btn||!drop)return;function open(){drop.classList.remove('hidden');btn.setAttribute('aria-expanded','true');}function close(){drop.classList.add('hidden');btn.setAttribute('aria-expanded','false');}btn.addEventListener('click',function(e){e.stopPropagation();if(drop.classList.contains('hidden'))open();else close();});document.addEventListener('click',function(){close();});drop.addEventListener('click',function(e){e.stopPropagation();});})();
            </script>
            @else
            <div class="flex items-center gap-2 shrink-0">
                <a href="{{ route('dashboard.my-profile') }}" class="px-3 py-2 rounded-lg text-sm font-semibold theme-header-text theme-header-hover transition-colors">Profile</a>
                <form action="{{ route('student.account.logout') }}" method="post" class="inline">@csrf<button type="submit" class="px-3 py-2 rounded-lg text-sm font-semibold theme-header-text theme-header-hover transition-colors">Log out</button></form>
            </div>
            @endif
        </div>
    </header>

    <aside id="student-mobile-sidebar" class="fixed top-0 left-0 z-40 h-full w-72 max-w-[85vw] bg-white border-r border-slate-200 shadow-xl transition-transform duration-200 ease-out lg:hidden" style="transform: translateX(-100%);" aria-label="Mobile menu" aria-hidden="true">
        <div class="flex items-center justify-between h-14 px-4 theme-header">
            <span class="text-sm font-bold theme-header-text">Menu</span>
            <button type="button" id="student-mobile-sidebar-close" class="p-2 rounded-lg theme-header-text theme-header-hover" aria-label="Close menu"><i class="fas fa-times"></i></button>
        </div>
        <nav class="p-4 space-y-1" aria-label="Dashboard navigation">
            @include('student.partials.dashboard-sidebar-nav')
        </nav>
    </aside>
    <div id="student-mobile-sidebar-overlay" class="fixed inset-0 z-30 bg-slate-900/40 lg:hidden cursor-pointer pointer-events-none" aria-hidden="true" role="button" tabindex="-1" aria-label="Close menu" style="visibility: hidden;"></div>

    <main class="flex-1 w-full min-w-0 overflow-x-hidden pb-24 lg:pb-10 xl:pb-8 pt-[max(1rem,env(safe-area-inset-top))] lg:pt-0">
        <div class="mx-auto w-full max-w-none lg:max-w-4xl xl:max-w-6xl min-w-0 px-4 py-4 sm:px-6 sm:py-6 lg:px-8 lg:py-6 xl:py-5">
            @if(isset($student) && $student && !request()->routeIs('dashboard'))
            <div class="lg:hidden flex justify-end mb-2">
                @include('student.partials.dashboard-student-notifications')
            </div>
            @endif

            @if(!request()->routeIs('dashboard'))
                <div class="hidden lg:flex items-center text-xs font-medium text-slate-500 gap-1 mb-6">
                    <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-1 px-2 py-1 rounded-full border border-slate-200 bg-white hover:bg-slate-50 hover:border-slate-300 text-slate-700 no-underline">
                        <i class="fas fa-arrow-left text-[10px]"></i>
                        <span>Back to dashboard</span>
                    </a>
                </div>
                @include('student.partials.dashboard-pill-nav', ['class' => 'lg:hidden mb-4', 'compact' => true, 'mobile' => true])
            @endif

            <div id="student-dashboard-live">
                @yield('dashboard_content')
            </div>
        </div>
    </main>

    @include('student.partials.dashboard-bottom-nav')

    <div id="student-dashboard-support-fab" class="hidden lg:block">
        @include('student.partials.support-fab', ['supportContext' => $supportContext, 'supportPage' => $breadcrumbLabel])
    </div>
</div>
<script>
(function(){
    function run() {
        var btn = document.getElementById('student-mobile-menu-btn');
        var sidebar = document.getElementById('student-mobile-sidebar');
        var overlay = document.getElementById('student-mobile-sidebar-overlay');
        var closeBtn = document.getElementById('student-mobile-sidebar-close');
        if (!btn || !sidebar || !overlay) return;

        function isOpen() {
            return sidebar.getAttribute('data-sidebar-open') === '1';
        }
        function setOpen(open) {
            sidebar.setAttribute('data-sidebar-open', open ? '1' : '0');
            sidebar.style.transform = open ? 'translateX(0)' : 'translateX(-100%)';
            sidebar.setAttribute('aria-hidden', open ? 'false' : 'true');
            overlay.style.visibility = open ? 'visible' : 'hidden';
            overlay.style.pointerEvents = open ? 'auto' : 'none';
            overlay.setAttribute('aria-hidden', open ? 'false' : 'true');
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
            document.body.style.overflow = open ? 'hidden' : '';
        }
        function closeSidebar() { setOpen(false); }
        function toggleSidebar(e) {
            if (e) { e.preventDefault(); e.stopPropagation(); }
            setOpen(!isOpen());
        }

        setOpen(false);
        btn.addEventListener('click', toggleSidebar);
        if (closeBtn) closeBtn.addEventListener('click', function(e) { e.preventDefault(); closeSidebar(); });
        overlay.addEventListener('click', function(e) { e.preventDefault(); closeSidebar(); });
        overlay.addEventListener('touchend', function(e) { e.preventDefault(); closeSidebar(); }, { passive: false });
        var navLinks = document.querySelectorAll('#student-mobile-sidebar nav a');
        for (var i = 0; i < navLinks.length; i++) { navLinks[i].addEventListener('click', closeSidebar); }
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && isOpen()) { e.preventDefault(); closeSidebar(); }
        });
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', run);
    } else {
        run();
    }
})();
</script>
@endsection
@push('scripts')
@include('student.partials.marketing-support-scripts')
<script>
(function () {
    'use strict';

    var liveRootId = 'student-dashboard-live';
    var refreshTimer = null;

    function shouldSoftRefresh(path) {
        if (!document.getElementById('student-dashboard-wrap')) {
            return false;
        }
        return /\/dashboard\/my-quizzes\/?$/.test(path);
    }

    function softRefreshContent() {
        var root = document.getElementById(liveRootId);
        if (!root) {
            return;
        }

        fetch(window.location.href, {
            credentials: 'same-origin',
            cache: 'no-store',
            headers: {
                Accept: 'text/html',
                'X-Requested-With': 'XMLHttpRequest',
                'Cache-Control': 'no-cache',
            },
        })
            .then(function (response) {
                return response.ok ? response.text() : null;
            })
            .then(function (html) {
                if (!html) {
                    return;
                }
                var doc = new DOMParser().parseFromString(html, 'text/html');
                var next = doc.getElementById(liveRootId);
                if (!next) {
                    return;
                }
                root.innerHTML = next.innerHTML;
            })
            .catch(function () {});
    }

    function scheduleSoftRefresh() {
        if (refreshTimer) {
            return;
        }
        refreshTimer = setTimeout(function () {
            refreshTimer = null;
            softRefreshContent();
        }, 500);
    }

    if (window.QuizSnapLive && typeof window.QuizSnapLive.registerRefresher === 'function') {
        window.QuizSnapLive.registerRefresher(function (type) {
            var path = String(window.location.pathname || '');
            if (!shouldSoftRefresh(path)) {
                return;
            }
            var eventType = String(type || '').toLowerCase();
            if (eventType === 'dashboard' || eventType === 'quizzes' || eventType === 'sessions') {
                scheduleSoftRefresh();
            }
        });
    }
})();
</script>
<script>
(function() {
    var vapidPublicKey = @json($vapidPublicKey);
    var subscribeUrl = @json(route('dashboard.push-subscribe'));
    var csrfToken = document.querySelector('meta[name="csrf-token"]') && document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    function urlBase64ToUint8Array(base64String) {
        var padLen = (4 - base64String.length % 4) % 4;
        var padding = '';
        for (var p = 0; p < padLen; p++) padding += '=';
        var base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
        var rawData = atob(base64);
        var outputArray = new Uint8Array(rawData.length);
        for (var i = 0; i < rawData.length; ++i) outputArray[i] = rawData.charCodeAt(i);
        return outputArray;
    }

    function subscribePush(registration) {
        if (!registration.pushManager || !vapidPublicKey) return Promise.resolve();
        return registration.pushManager.getSubscription().then(function(existing) {
            if (existing) return existing;
            return registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(vapidPublicKey)
            });
        }).then(function(subscription) {
            var payload = subscription.toJSON();
            if (!payload.endpoint || !payload.keys) return;
            var body = JSON.stringify({ endpoint: payload.endpoint, keys: payload.keys });
            var xhr = new XMLHttpRequest();
            xhr.open('POST', subscribeUrl, true);
            xhr.setRequestHeader('Content-Type', 'application/json');
            xhr.setRequestHeader('Accept', 'application/json');
            xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken || '');
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.send(body);
        }).catch(function(err) { console.warn('Push subscribe:', err); });
    }

    if ('serviceWorker' in navigator && 'PushManager' in window) {
        function initPush(registration) {
            if (Notification.permission === 'granted') {
                subscribePush(registration);
            } else if (Notification.permission === 'default') {
                Notification.requestPermission().then(function(perm) {
                    if (perm === 'granted') subscribePush(registration);
                });
            }
        }

        function ensurePushRegistration() {
            navigator.serviceWorker.register('{{ asset('sw.js') }}', { scope: '/' }).then(initPush).catch(function(err) { console.warn('SW register:', err); });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', ensurePushRegistration);
        } else {
            ensurePushRegistration();
        }
    }
})();
</script>
@endpush
