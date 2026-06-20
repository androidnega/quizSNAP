@php
    use App\Support\SupportContact;

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

    $supportContext = [];
    if (isset($student) && $student) {
        $supportContext = array_filter([
            'name' => $student->display_name ?? null,
            'index_number' => $student->index_number ?? null,
        ]);
    }
    $supportWhatsAppUrl = SupportContact::whatsAppUrl($supportContext);
    $supportCallE164 = SupportContact::callNumber();
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
        background: rgba(15, 23, 42, 0.26);
        backdrop-filter: blur(6px);
        -webkit-backdrop-filter: blur(6px);
        opacity: 0;
        visibility: hidden;
        transition: opacity 0.32s ease, visibility 0.32s ease;
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
        gap: 0.5rem;
        pointer-events: auto;
    }
    .sd-nav-fab-menu {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 0.4375rem;
        pointer-events: none;
    }
    .sd-nav-fab-wrap.is-open .sd-nav-fab-menu {
        pointer-events: auto;
    }
    .sd-nav-fab-item,
    .sd-nav-fab-divider {
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
        transform: translate3d(0, 1.25rem, 0) scale(0.86);
        transform-origin: right center;
        filter: blur(3px);
        transition:
            opacity 0.34s cubic-bezier(0.22, 1, 0.36, 1),
            transform 0.4s cubic-bezier(0.22, 1.14, 0.36, 1),
            visibility 0.34s,
            filter 0.28s ease,
            background 0.18s ease,
            color 0.18s ease,
            border-color 0.18s ease,
            box-shadow 0.18s ease;
        transition-delay: calc((var(--fab-max, 6) - var(--fab-i, 0)) * 26ms);
    }
    .sd-nav-fab-wrap.is-open .sd-nav-fab-item,
    .sd-nav-fab-wrap.is-open .sd-nav-fab-divider {
        opacity: 1;
        visibility: visible;
        pointer-events: auto;
        transform: translate3d(0, 0, 0) scale(1);
        filter: blur(0);
        transition-delay: calc(var(--fab-i, 0) * 48ms);
    }
    .sd-nav-fab-item {
        display: inline-flex;
        align-items: center;
        gap: 0.625rem;
        padding: 0.5rem 0.9375rem 0.5rem 0.5rem;
        border-radius: 9999px;
        background: rgba(255, 255, 255, 0.98);
        color: #334155;
        text-decoration: none;
        font-size: 0.8125rem;
        font-weight: 600;
        letter-spacing: -0.01em;
        box-shadow:
            0 1px 2px rgba(15, 23, 42, 0.04),
            0 10px 28px -12px rgba(15, 23, 42, 0.26);
        border: 1px solid rgba(226, 232, 240, 0.92);
        white-space: nowrap;
        touch-action: manipulation;
    }
    .sd-nav-fab-wrap.is-open .sd-nav-fab-item:hover {
        transform: translate3d(0, -2px, 0) scale(1);
        box-shadow:
            0 2px 4px rgba(15, 23, 42, 0.05),
            0 14px 32px -12px rgba(15, 23, 42, 0.3);
    }
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
        transition: background 0.18s ease, color 0.18s ease, transform 0.28s cubic-bezier(0.22, 1.14, 0.36, 1);
    }
    .sd-nav-fab-wrap.is-open .sd-nav-fab-item:active .sd-nav-fab-item-icon {
        transform: scale(0.92);
    }
    .sd-nav-fab-item.is-active .sd-nav-fab-item-icon {
        background: var(--theme-text);
        color: var(--theme-brand);
    }
    .sd-nav-fab-divider {
        width: 2.75rem;
        height: 1px;
        margin: 0.0625rem 0;
        background: linear-gradient(90deg, transparent, #cbd5e1, transparent);
        border: none;
        filter: none;
    }
    .sd-nav-fab-item-icon--whatsapp {
        background: linear-gradient(145deg, #2ee06a 0%, #128c7e 100%) !important;
        color: #fff !important;
    }
    .sd-nav-fab-item-icon--call {
        background: linear-gradient(145deg, #3b82f6 0%, #1d4ed8 100%) !important;
        color: #fff !important;
    }
    .sd-nav-fab-toggle {
        position: relative;
        width: 3.625rem;
        height: 3.625rem;
        border: none;
        border-radius: 9999px;
        cursor: pointer;
        color: var(--theme-header-text);
        background: linear-gradient(145deg, var(--theme-brand) 0%, var(--theme-brand-dark) 100%);
        box-shadow:
            0 1px 2px rgba(15, 23, 42, 0.08),
            0 14px 32px -12px color-mix(in srgb, var(--theme-brand) 78%, transparent);
        display: grid;
        place-items: center;
        touch-action: manipulation;
        transition:
            transform 0.38s cubic-bezier(0.22, 1.14, 0.36, 1),
            box-shadow 0.28s ease,
            background 0.28s ease;
    }
    .sd-nav-fab-wrap:not(.is-open) .sd-nav-fab-toggle {
        animation: sd-nav-fab-glow 3s ease-in-out infinite;
    }
    @keyframes sd-nav-fab-glow {
        0%, 100% {
            box-shadow:
                0 1px 2px rgba(15, 23, 42, 0.06),
                0 12px 28px -12px color-mix(in srgb, var(--theme-brand) 72%, transparent);
        }
        50% {
            box-shadow:
                0 2px 6px rgba(15, 23, 42, 0.1),
                0 18px 38px -10px color-mix(in srgb, var(--theme-brand) 88%, transparent);
        }
    }
    .sd-nav-fab-toggle:hover {
        transform: translateY(-2px) scale(1.02);
    }
    .sd-nav-fab-toggle:active {
        transform: scale(0.94);
    }
    .sd-nav-fab-wrap.is-open .sd-nav-fab-toggle {
        animation: none;
    }
    .sd-nav-fab-toggle-icon {
        position: absolute;
        display: grid;
        place-items: center;
        font-size: 1.125rem;
        line-height: 1;
        transition: opacity 0.26s ease, transform 0.32s cubic-bezier(0.22, 1.14, 0.36, 1);
    }
    .sd-nav-fab-toggle-icon--close {
        opacity: 0;
        transform: rotate(-72deg) scale(0.65);
    }
    .sd-nav-fab-wrap.is-open .sd-nav-fab-toggle-icon--open {
        opacity: 0;
        transform: rotate(72deg) scale(0.65);
    }
    .sd-nav-fab-wrap.is-open .sd-nav-fab-toggle-icon--close {
        opacity: 1;
        transform: rotate(0deg) scale(1);
    }
    @media (min-width: 1024px) {
        .sd-nav-fab-wrap { display: none; }
    }
    @media (prefers-reduced-motion: reduce) {
        .sd-nav-fab-item,
        .sd-nav-fab-divider,
        .sd-nav-fab-toggle,
        .sd-nav-fab-toggle-icon,
        .sd-nav-fab-backdrop {
            animation: none !important;
            transition-duration: 0.01ms !important;
            filter: none !important;
        }
    }
</style>
<div class="sd-nav-fab-wrap lg:hidden" id="sd-nav-fab-wrap" aria-hidden="true">
    <div class="sd-nav-fab-backdrop" id="sd-nav-fab-backdrop" aria-hidden="true"></div>
    <div class="sd-nav-fab" id="sd-nav-fab">
        <div class="sd-nav-fab-menu" id="sd-nav-fab-menu" role="menu" aria-label="Dashboard navigation" style="--fab-max: {{ count($fabItems) + 2 }}">
            @foreach($fabItems as $fabIndex => $item)
            <a href="{{ route($item['route']) }}"
               class="sd-nav-fab-item {{ $item['active'] ? 'is-active' : '' }}"
               style="--fab-i: {{ $fabIndex }}"
               role="menuitem">
                <span class="sd-nav-fab-item-icon" aria-hidden="true"><i class="fas {{ $item['icon'] }}"></i></span>
                {{ $item['label'] }}
            </a>
            @endforeach
            <div class="sd-nav-fab-divider" style="--fab-i: {{ count($fabItems) }}" role="separator" aria-hidden="true"></div>
            <a href="{{ $supportWhatsAppUrl }}"
               class="sd-nav-fab-item sd-nav-fab-item--support"
               style="--fab-i: {{ count($fabItems) + 1 }}"
               role="menuitem"
               target="_blank"
               rel="noopener noreferrer">
                <span class="sd-nav-fab-item-icon sd-nav-fab-item-icon--whatsapp" aria-hidden="true">
                    <i class="fas fa-headset"></i>
                </span>
                Support
            </a>
            <a href="tel:{{ $supportCallE164 }}"
               class="sd-nav-fab-item sd-nav-fab-item--support"
               style="--fab-i: {{ count($fabItems) + 2 }}"
               role="menuitem">
                <span class="sd-nav-fab-item-icon sd-nav-fab-item-icon--call" aria-hidden="true">
                    <i class="fas fa-phone-alt"></i>
                </span>
                Call
            </a>
        </div>
        <button type="button"
                class="sd-nav-fab-toggle"
                id="sd-nav-fab-toggle"
                aria-label="Open menu and support"
                aria-expanded="false"
                aria-controls="sd-nav-fab-menu">
            <span class="sd-nav-fab-toggle-icon sd-nav-fab-toggle-icon--open" aria-hidden="true"><i class="fas fa-plus"></i></span>
            <span class="sd-nav-fab-toggle-icon sd-nav-fab-toggle-icon--close" aria-hidden="true"><i class="fas fa-times"></i></span>
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
        toggle.setAttribute('aria-label', open ? 'Close menu and support' : 'Open menu and support');
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
