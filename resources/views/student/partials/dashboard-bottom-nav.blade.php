@php
    $studentNavHome = request()->routeIs('dashboard') && !request()->routeIs('dashboard.my-*') && !request()->routeIs('dashboard.course-materials') && !request()->routeIs('dashboard.calendar');
    $fabItems = [
        ['route' => 'dashboard', 'label' => 'Home', 'icon' => 'fa-home', 'active' => $studentNavHome],
        ['route' => 'dashboard.my-quizzes', 'label' => 'Quizzes', 'icon' => 'fa-clipboard-list', 'active' => request()->routeIs('dashboard.my-quizzes*'), 'student_only' => true],
        ['route' => 'dashboard.course-materials', 'label' => 'Materials', 'icon' => 'fa-book', 'active' => request()->routeIs('dashboard.course-materials'), 'student_only' => true],
        ['route' => 'dashboard.my-profile', 'label' => 'Profile', 'icon' => 'fa-user', 'active' => request()->routeIs('dashboard.my-profile')],
    ];
    $fabItems = array_values(array_filter($fabItems, function ($item) use ($student) {
        return empty($item['student_only']) || ($student ?? null);
    }));
@endphp
<style>
    .sd-nav-fab-wrap {
        position: fixed;
        inset: 0;
        z-index: 45;
        pointer-events: none;
    }
    .sd-nav-fab-wrap.is-open {
        pointer-events: auto;
    }
    .sd-nav-fab-backdrop {
        position: absolute;
        inset: 0;
        background: rgba(15, 23, 42, 0.35);
        opacity: 0;
        visibility: hidden;
        transition: opacity 0.25s ease, visibility 0.25s;
    }
    .sd-nav-fab-wrap.is-open .sd-nav-fab-backdrop {
        opacity: 1;
        visibility: visible;
    }
    .sd-nav-fab {
        position: fixed;
        right: max(1rem, env(safe-area-inset-right));
        bottom: max(1rem, env(safe-area-inset-bottom));
        z-index: 46;
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 0.625rem;
        pointer-events: auto;
    }
    .sd-nav-fab-menu {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 0.5rem;
        pointer-events: none;
    }
    .sd-nav-fab-wrap.is-open .sd-nav-fab-menu {
        pointer-events: auto;
    }
    .sd-nav-fab-item {
        display: inline-flex;
        align-items: center;
        gap: 0.625rem;
        padding: 0.5rem 0.875rem 0.5rem 0.5rem;
        border-radius: 9999px;
        background: #fff;
        color: #334155;
        text-decoration: none;
        font-size: 0.8125rem;
        font-weight: 600;
        box-shadow: 0 8px 24px -10px rgba(15, 23, 42, 0.35);
        border: 1px solid #e2e8f0;
        white-space: nowrap;
        opacity: 0;
        visibility: hidden;
        transform: translateY(0.75rem) scale(0.92);
        transition: opacity 0.22s ease, transform 0.22s cubic-bezier(0.34, 1.4, 0.64, 1), visibility 0.22s, background 0.15s, color 0.15s, border-color 0.15s;
    }
    .sd-nav-fab-wrap.is-open .sd-nav-fab-item {
        opacity: 1;
        visibility: visible;
        transform: translateY(0) scale(1);
    }
    .sd-nav-fab-wrap.is-open .sd-nav-fab-item:nth-child(1) { transition-delay: 0.03s; }
    .sd-nav-fab-wrap.is-open .sd-nav-fab-item:nth-child(2) { transition-delay: 0.06s; }
    .sd-nav-fab-wrap.is-open .sd-nav-fab-item:nth-child(3) { transition-delay: 0.09s; }
    .sd-nav-fab-wrap.is-open .sd-nav-fab-item:nth-child(4) { transition-delay: 0.12s; }
    .sd-nav-fab-item.is-active {
        background: var(--theme-brand);
        border-color: var(--theme-brand-dark);
        color: var(--theme-header-text);
    }
    .sd-nav-fab-item-icon {
        width: 2.25rem;
        height: 2.25rem;
        border-radius: 9999px;
        display: grid;
        place-items: center;
        flex-shrink: 0;
        background: #f1f5f9;
        color: #475569;
        font-size: 0.875rem;
        transition: background 0.15s, color 0.15s;
    }
    .sd-nav-fab-item.is-active .sd-nav-fab-item-icon {
        background: var(--theme-text);
        color: var(--theme-brand);
    }
    .sd-nav-fab-toggle {
        width: 3.5rem;
        height: 3.5rem;
        border: none;
        border-radius: 9999px;
        cursor: pointer;
        color: var(--theme-header-text);
        background: var(--theme-brand);
        box-shadow: 0 12px 32px -12px color-mix(in srgb, var(--theme-brand) 75%, transparent);
        display: grid;
        place-items: center;
        transition: transform 0.22s cubic-bezier(0.34, 1.4, 0.64, 1), box-shadow 0.22s ease;
    }
    .sd-nav-fab-toggle:hover {
        transform: translateY(-2px);
        background: var(--theme-brand-dark);
        box-shadow: 0 16px 36px -12px color-mix(in srgb, var(--theme-brand) 85%, transparent);
    }
    .sd-nav-fab-toggle:active {
        transform: scale(0.94);
    }
    .sd-nav-fab-wrap.is-open .sd-nav-fab-toggle {
        transform: rotate(45deg);
    }
    .sd-nav-fab-toggle i {
        font-size: 1.125rem;
        transition: transform 0.22s ease;
    }
    @media (min-width: 1024px) {
        .sd-nav-fab-wrap { display: none; }
    }
</style>
<div class="sd-nav-fab-wrap lg:hidden" id="sd-nav-fab-wrap" aria-hidden="true">
    <div class="sd-nav-fab-backdrop" id="sd-nav-fab-backdrop" aria-hidden="true"></div>
    <div class="sd-nav-fab" id="sd-nav-fab">
        <div class="sd-nav-fab-menu" id="sd-nav-fab-menu" role="menu" aria-label="Dashboard navigation">
            @foreach($fabItems as $item)
            <a href="{{ route($item['route']) }}"
               class="sd-nav-fab-item {{ $item['active'] ? 'is-active' : '' }}"
               role="menuitem">
                <span class="sd-nav-fab-item-icon" aria-hidden="true"><i class="fas {{ $item['icon'] }}"></i></span>
                {{ $item['label'] }}
            </a>
            @endforeach
        </div>
        <button type="button"
                class="sd-nav-fab-toggle"
                id="sd-nav-fab-toggle"
                aria-label="Open navigation menu"
                aria-expanded="false"
                aria-controls="sd-nav-fab-menu">
            <i class="fas fa-plus" aria-hidden="true"></i>
        </button>
    </div>
</div>
<script>
(function () {
    var wrap = document.getElementById('sd-nav-fab-wrap');
    var toggle = document.getElementById('sd-nav-fab-toggle');
    var backdrop = document.getElementById('sd-nav-fab-backdrop');
    if (!wrap || !toggle) return;

    function setOpen(open) {
        wrap.classList.toggle('is-open', open);
        wrap.setAttribute('aria-hidden', open ? 'false' : 'true');
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        toggle.setAttribute('aria-label', open ? 'Close navigation menu' : 'Open navigation menu');
        document.body.style.overflow = open ? 'hidden' : '';
    }
    function closeMenu() { setOpen(false); }
    function toggleMenu(e) {
        if (e) { e.preventDefault(); e.stopPropagation(); }
        setOpen(!wrap.classList.contains('is-open'));
    }

    toggle.addEventListener('click', toggleMenu);
    if (backdrop) backdrop.addEventListener('click', closeMenu);
    var links = wrap.querySelectorAll('.sd-nav-fab-item');
    for (var i = 0; i < links.length; i++) {
        links[i].addEventListener('click', closeMenu);
    }
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && wrap.classList.contains('is-open')) closeMenu();
    });
})();
</script>
