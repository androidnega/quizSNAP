@extends('layouts.app')

@section('title', $dashboardTitle ?? 'My Dashboard')
@section('robots', 'noindex,nofollow')
@section('body_class', 'theme-bg')
@section('body_extra_class', 'min-h-screen')

@push('styles')
<style>
@include('partials.support-fab-styles')
@media (max-width: 1023px) {
    #student-dashboard-support-fab { display: none !important; }
}
.sd-overflow-menu {
    position: absolute;
    top: calc(100% + 0.35rem);
    right: 0;
    z-index: 50;
    min-width: 11.5rem;
    padding: 0.35rem;
    border-radius: 0.85rem;
    background: #fff;
    border: 1px solid rgba(15, 23, 42, 0.08);
    box-shadow: 0 12px 32px rgba(15, 23, 42, 0.14);
    opacity: 0;
    transform: translateY(-6px) scale(0.98);
    transform-origin: top right;
    transition: opacity 0.16s ease, transform 0.16s ease;
    pointer-events: none;
}
.sd-overflow-menu.is-open {
    opacity: 1;
    transform: translateY(0) scale(1);
    pointer-events: auto;
}
.sd-overflow-item {
    display: flex;
    width: 100%;
    align-items: center;
    gap: 0.75rem;
    padding: 0.7rem 0.8rem;
    border: 0;
    border-radius: 0.65rem;
    background: transparent;
    color: #1e293b;
    font-size: 0.9375rem;
    font-weight: 500;
    text-align: left;
    text-decoration: none;
    cursor: pointer;
    font-family: inherit;
}
.sd-overflow-item i {
    width: 1rem;
    text-align: center;
    color: #64748b;
    font-size: 0.85rem;
}
.sd-overflow-item:hover,
.sd-overflow-item:focus {
    background: #f8fafc;
    outline: none;
}
.sd-overflow-item--danger {
    color: #b91c1c;
}
.sd-overflow-item--danger i {
    color: #dc2626;
}
.sd-overflow-item--danger:hover,
.sd-overflow-item--danger:focus {
    background: #fef2f2;
}
@media (prefers-reduced-motion: reduce) {
    .sd-overflow-menu { transition: none; }
}
</style>
@endpush

@section('content')
@php
    use App\Support\SupportContact;

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
            'phone' => $student->phone_contact ?? null,
            'email' => $student->email ?? null,
        ]);
    }
@endphp
<div class="min-h-screen flex flex-col theme-bg" id="student-dashboard-wrap">
    {{-- Mobile top bar: menu + brand + WhatsApp-style overflow --}}
    <div class="lg:hidden sticky top-0 z-30 flex h-14 items-center justify-between gap-3 px-4 theme-header safe-area-t" id="student-mobile-topbar">
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
        <div class="relative shrink-0" id="student-mobile-overflow">
            <button type="button" id="student-mobile-overflow-btn" class="flex h-10 w-10 items-center justify-center rounded-xl theme-header-text theme-header-hover focus:outline-none focus:ring-2 focus:ring-slate-700 focus:ring-offset-2 focus:ring-offset-[var(--theme-brand)]" aria-label="More options" aria-expanded="false" aria-haspopup="true" aria-controls="student-mobile-overflow-menu">
                <i class="fas fa-ellipsis-v text-base" aria-hidden="true"></i>
            </button>
            <div id="student-mobile-overflow-menu" class="sd-overflow-menu" role="menu" aria-labelledby="student-mobile-overflow-btn" hidden>
                @if(isset($student) && $student)
                <a href="{{ route('dashboard.my-profile') }}" class="sd-overflow-item" role="menuitem">
                    <i class="fas fa-user" aria-hidden="true"></i>
                    <span>Profile</span>
                </a>
                @endif
                <form action="{{ isset($student) && $student ? route('student.account.logout') : route('logout') }}" method="post">
                    @csrf
                    <button type="submit" class="sd-overflow-item sd-overflow-item--danger" role="menuitem">
                        <i class="fas fa-sign-out-alt" aria-hidden="true"></i>
                        <span>Log out</span>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <header class="hidden lg:block sticky top-0 z-30 theme-header">
        <div class="mx-auto flex h-14 lg:h-16 w-full max-w-none lg:max-w-4xl xl:max-w-6xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
            @include('partials.brand-logo', [
                'appName' => $appName,
                'href' => route('dashboard'),
                'size' => 'lg',
                'variant' => 'on-brand',
                'class' => 'inline-flex shrink-0',
            ])

            @if(isset($student) && $student)
            <div class="relative shrink-0" id="student-profile-menu">
                <button type="button" id="student-profile-btn" class="dashboard-chrome-profile !bg-white/75 !border-white/60 focus:outline-none focus:ring-2 focus:ring-slate-700 focus:ring-offset-2 focus:ring-offset-[var(--theme-brand)]" aria-expanded="false" aria-haspopup="true" aria-controls="student-profile-dropdown" title="Profile">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-900/10 text-slate-800 font-medium text-sm"><i class="fas fa-user text-xs" aria-hidden="true"></i></span>
                    <span class="hidden sm:flex flex-col items-start leading-tight min-w-0 pr-0.5">
                        <span class="text-sm font-semibold text-slate-900 truncate max-w-[8rem]">{{ $student->first_name }}</span>
                        <span class="text-[11px] text-slate-600 font-mono truncate max-w-[8rem]">{{ $student->index_number }}</span>
                    </span>
                    <i class="fas fa-chevron-down text-[10px] text-slate-600 hidden sm:inline profile-chevron" aria-hidden="true"></i>
                </button>
                <div id="student-profile-dropdown" class="profile-menu-panel" role="menu" aria-labelledby="student-profile-btn" hidden>
                    <div class="profile-menu-head">
                        <span class="profile-menu-head-avatar bg-slate-100 text-slate-700"><i class="fas fa-user text-xs" aria-hidden="true"></i></span>
                        <span class="min-w-0">
                            <span class="block text-sm font-semibold text-slate-900 truncate">{{ $student->display_name }}</span>
                            <span class="block text-[11px] text-slate-500 font-mono truncate mt-0.5">{{ $student->index_number }}</span>
                        </span>
                    </div>
                    <div class="profile-menu-list">
                        <a href="{{ route('dashboard.my-profile') }}" class="profile-menu-item" role="menuitem">
                            <span class="profile-menu-item-icon" aria-hidden="true"><i class="fas fa-user"></i></span>
                            Profile
                        </a>
                    </div>
                    <div class="profile-menu-foot">
                        <form action="{{ route('student.account.logout') }}" method="post">
                            @csrf
                            <button type="submit" class="profile-menu-item profile-menu-item--danger" role="menuitem">
                                <span class="profile-menu-item-icon" aria-hidden="true"><i class="fas fa-sign-out-alt"></i></span>
                                Log out
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <script>
            (function(){
                var btn=document.getElementById('student-profile-btn');
                var drop=document.getElementById('student-profile-dropdown');
                var wrap=document.getElementById('student-profile-menu');
                if(!btn||!drop)return;
                var t=null;
                function open(){ if(t){clearTimeout(t);t=null;} drop.hidden=false; requestAnimationFrame(function(){ drop.classList.add('is-open'); }); btn.setAttribute('aria-expanded','true'); }
                function close(){ drop.classList.remove('is-open'); btn.setAttribute('aria-expanded','false'); t=setTimeout(function(){ if(!drop.classList.contains('is-open')) drop.hidden=true; t=null; },180); }
                btn.addEventListener('click',function(e){ e.stopPropagation(); drop.classList.contains('is-open')?close():open(); });
                document.addEventListener('click',function(e){ if(wrap&&wrap.contains(e.target))return; if(drop.classList.contains('is-open'))close(); });
                document.addEventListener('keydown',function(e){ if(e.key==='Escape'&&drop.classList.contains('is-open')){ close(); btn.focus(); } });
            })();
            </script>
            @elseif(isset($user) && $user)
            <div class="relative shrink-0" id="student-profile-menu">
                <button type="button" id="student-profile-btn" class="dashboard-chrome-profile !bg-white/75 !border-white/60 focus:outline-none focus:ring-2 focus:ring-slate-700 focus:ring-offset-2 focus:ring-offset-[var(--theme-brand)]" aria-expanded="false" aria-haspopup="true" aria-controls="student-profile-dropdown" title="Profile">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-900/10 text-slate-800 font-medium text-sm"><i class="fas fa-user text-xs" aria-hidden="true"></i></span>
                    <span class="hidden lg:flex flex-col items-start leading-tight min-w-0 pr-0.5">
                        <span class="text-sm font-semibold text-slate-900 truncate max-w-[8rem]">{{ $user->name ?? $user->username }}</span>
                    </span>
                    <i class="fas fa-chevron-down text-[10px] text-slate-600 hidden lg:inline profile-chevron" aria-hidden="true"></i>
                </button>
                <div id="student-profile-dropdown" class="profile-menu-panel" role="menu" aria-labelledby="student-profile-btn" hidden>
                    <div class="profile-menu-head">
                        <span class="profile-menu-head-avatar bg-slate-100 text-slate-700"><i class="fas fa-user text-xs" aria-hidden="true"></i></span>
                        <span class="min-w-0">
                            <span class="block text-sm font-semibold text-slate-900 truncate">{{ $user->name ?? $user->username }}</span>
                        </span>
                    </div>
                    <div class="profile-menu-foot">
                        <form action="{{ route('logout') }}" method="post">
                            @csrf
                            <button type="submit" class="profile-menu-item profile-menu-item--danger" role="menuitem">
                                <span class="profile-menu-item-icon" aria-hidden="true"><i class="fas fa-sign-out-alt"></i></span>
                                Log out
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <script>
            (function(){
                var btn=document.getElementById('student-profile-btn');
                var drop=document.getElementById('student-profile-dropdown');
                var wrap=document.getElementById('student-profile-menu');
                if(!btn||!drop)return;
                var t=null;
                function open(){ if(t){clearTimeout(t);t=null;} drop.hidden=false; requestAnimationFrame(function(){ drop.classList.add('is-open'); }); btn.setAttribute('aria-expanded','true'); }
                function close(){ drop.classList.remove('is-open'); btn.setAttribute('aria-expanded','false'); t=setTimeout(function(){ if(!drop.classList.contains('is-open')) drop.hidden=true; t=null; },180); }
                btn.addEventListener('click',function(e){ e.stopPropagation(); drop.classList.contains('is-open')?close():open(); });
                document.addEventListener('click',function(e){ if(wrap&&wrap.contains(e.target))return; if(drop.classList.contains('is-open'))close(); });
                document.addEventListener('keydown',function(e){ if(e.key==='Escape'&&drop.classList.contains('is-open')){ close(); btn.focus(); } });
            })();
            </script>
            @else
            <div class="flex items-center gap-2 shrink-0">
                <a href="{{ route('dashboard.my-profile') }}" class="px-3 py-2 rounded-lg text-sm font-semibold theme-header-text theme-header-hover transition-colors">Profile</a>
                <form action="{{ route('student.account.logout') }}" method="post" class="inline">@csrf<button type="submit" class="px-3 py-2 rounded-lg text-sm font-semibold theme-header-text theme-header-hover transition-colors">Log out</button></form>
            </div>
            @endif
        </div>
    </header>

    <aside id="student-mobile-sidebar" class="fixed top-0 left-0 z-40 h-full w-72 max-w-[85vw] bg-white border-r border-slate-200 shadow-xl transition-transform duration-200 ease-out lg:hidden flex flex-col" style="transform: translateX(-100%);" aria-label="Mobile menu" aria-hidden="true">
        <div class="flex items-center justify-between h-14 px-4 theme-header shrink-0">
            <span class="text-sm font-bold theme-header-text">Menu</span>
            <button type="button" id="student-mobile-sidebar-close" class="p-2 rounded-lg theme-header-text theme-header-hover" aria-label="Close menu"><i class="fas fa-times"></i></button>
        </div>
        <nav class="p-4 space-y-1 flex-1 overflow-y-auto" aria-label="Dashboard navigation">
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

    @if(\App\Support\LiveSupportAccess::isEnabled())
    <div id="student-dashboard-support-fab" class="hidden lg:block">
        @include('student.partials.support-fab', ['supportContext' => $supportContext, 'supportPage' => $breadcrumbLabel])
    </div>
    @endif
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

        var overflowBtn = document.getElementById('student-mobile-overflow-btn');
        var overflowMenu = document.getElementById('student-mobile-overflow-menu');
        var overflowWrap = document.getElementById('student-mobile-overflow');
        if (overflowBtn && overflowMenu && overflowWrap) {
            var overflowTimer = null;
            function openOverflow() {
                if (overflowTimer) { clearTimeout(overflowTimer); overflowTimer = null; }
                overflowMenu.hidden = false;
                requestAnimationFrame(function () { overflowMenu.classList.add('is-open'); });
                overflowBtn.setAttribute('aria-expanded', 'true');
            }
            function closeOverflow() {
                overflowMenu.classList.remove('is-open');
                overflowBtn.setAttribute('aria-expanded', 'false');
                overflowTimer = setTimeout(function () {
                    if (!overflowMenu.classList.contains('is-open')) overflowMenu.hidden = true;
                    overflowTimer = null;
                }, 160);
            }
            overflowBtn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                overflowMenu.classList.contains('is-open') ? closeOverflow() : openOverflow();
            });
            document.addEventListener('click', function (e) {
                if (overflowWrap.contains(e.target)) return;
                if (overflowMenu.classList.contains('is-open')) closeOverflow();
            });
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && overflowMenu.classList.contains('is-open')) {
                    closeOverflow();
                    overflowBtn.focus();
                }
            });
        }
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
@if(\App\Support\LiveSupportAccess::isEnabled())
@include('student.partials.marketing-support-scripts')
@endif
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
        if (window.QuizSnapLive && typeof window.QuizSnapLive.isUserInteracting === 'function') {
            if (window.QuizSnapLive.isUserInteracting()) {
                return;
            }
        }

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
            if (window.QuizSnapLive && typeof window.QuizSnapLive.isUserInteracting === 'function') {
                if (window.QuizSnapLive.isUserInteracting()) {
                    scheduleSoftRefresh();
                    return;
                }
            }
            softRefreshContent();
        }, 500);
    }

    if (window.QuizSnapLive && typeof window.QuizSnapLive.registerRefresher === 'function') {
        window.QuizSnapLive.registerRefresher(function (type) {
            if (window.QuizSnapLive.isUserInteracting && window.QuizSnapLive.isUserInteracting()) {
                return;
            }
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
