@push('styles')
<style>
    .glance-card {
        position: relative;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        border-radius: 1rem;
        background: #fff;
        border: 1px solid rgba(226, 232, 240, 0.95);
        box-shadow:
            0 1px 2px rgba(15, 23, 42, 0.04),
            0 4px 16px rgba(15, 23, 42, 0.05);
        transition: transform 0.22s ease, box-shadow 0.22s ease, border-color 0.22s ease;
    }

    a.glance-card:hover,
    .glance-card:has(a.glance-card__body:hover) {
        transform: translateY(-2px);
        border-color: rgba(203, 213, 225, 0.95);
        box-shadow:
            0 4px 8px rgba(15, 23, 42, 0.05),
            0 12px 28px rgba(15, 23, 42, 0.08);
    }

    .glance-card__glow {
        position: absolute;
        top: -1.5rem;
        right: -1.5rem;
        width: 5rem;
        height: 5rem;
        border-radius: 9999px;
        opacity: 0.55;
        pointer-events: none;
        transition: opacity 0.22s ease, transform 0.22s ease;
    }

    .glance-card--blue .glance-card__glow { background: radial-gradient(circle, rgba(59, 130, 246, 0.22) 0%, transparent 70%); }
    .glance-card--emerald .glance-card__glow { background: radial-gradient(circle, rgba(16, 185, 129, 0.22) 0%, transparent 70%); }
    .glance-card--amber .glance-card__glow { background: radial-gradient(circle, rgba(245, 158, 11, 0.22) 0%, transparent 70%); }
    .glance-card--violet .glance-card__glow { background: radial-gradient(circle, rgba(139, 92, 246, 0.2) 0%, transparent 70%); }

    a.glance-card:hover .glance-card__glow,
    .glance-card:has(a.glance-card__body:hover) .glance-card__glow {
        opacity: 0.85;
        transform: scale(1.08);
    }

    .glance-card__body {
        position: relative;
        z-index: 1;
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        padding: 0.875rem 0.875rem 1rem;
        min-height: 100%;
    }

    @media (min-width: 640px) {
        .glance-card__body { padding: 1rem 1rem 1.125rem; gap: 0.875rem; }
    }

    @media (min-width: 1280px) {
        .sd-home-compact .glance-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 1rem;
        }
        .sd-home-compact .glance-card__body {
            padding: 0.875rem 1rem 1rem;
            gap: 0.75rem;
        }
        .sd-home-compact .glance-card__icon {
            width: 2rem;
            height: 2rem;
            font-size: 0.8125rem;
        }
        .sd-home-compact .glance-card__value {
            font-size: 1.125rem;
        }
        .sd-home-compact .glance-card__value--sm {
            font-size: 0.9375rem;
        }
    }

    .glance-card__icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2.125rem;
        height: 2.125rem;
        border-radius: 0.75rem;
        font-size: 0.875rem;
        color: #fff;
        box-shadow: 0 6px 14px rgba(15, 23, 42, 0.12);
        transition: transform 0.22s ease, box-shadow 0.22s ease;
    }

    @media (min-width: 640px) {
        .glance-card__icon { width: 2.375rem; height: 2.375rem; font-size: 0.9375rem; }
    }

    .glance-card__icon--blue { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); box-shadow: 0 6px 16px rgba(37, 99, 235, 0.28); }
    .glance-card__icon--emerald { background: linear-gradient(135deg, #10b981 0%, #059669 100%); box-shadow: 0 6px 16px rgba(5, 150, 105, 0.28); }
    .glance-card__icon--amber { background: #f59e0b; box-shadow: 0 6px 16px rgba(245, 158, 11, 0.28); }
    .glance-card__icon--violet { background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); box-shadow: 0 6px 16px rgba(124, 58, 237, 0.28); }

    a.glance-card:hover .glance-card__icon,
    .glance-card:has(a.glance-card__body:hover) .glance-card__icon {
        transform: scale(1.05);
    }

    .glance-card__content {
        display: flex;
        flex-direction: column;
        gap: 0.125rem;
    }

    .glance-card__value {
        font-size: 1.375rem;
        line-height: 1.15;
        font-weight: 800;
        letter-spacing: -0.03em;
        color: #0f172a;
        font-variant-numeric: tabular-nums;
    }

    .glance-card__value--sm {
        font-size: 0.8125rem;
        font-weight: 700;
        letter-spacing: -0.02em;
    }

    @media (min-width: 640px) {
        .glance-card__value { font-size: 1.5rem; }
        .glance-card__value--sm { font-size: 0.875rem; }
    }

    .glance-card__label {
        font-size: 0.625rem;
        line-height: 1.35;
        font-weight: 600;
        color: #64748b;
        letter-spacing: 0.01em;
    }

    .glance-card__label--hint {
        color: #94a3b8;
        font-size: 0.5625rem;
    }

    .glance-mobile-quiz-panel {
        display: flex;
        flex-direction: column;
        align-items: stretch;
        gap: 0.5rem;
        width: 100%;
        margin-top: 0.75rem;
        padding: 0.8125rem 0.875rem 0.875rem;
        border-radius: 0.9375rem;
        text-decoration: none;
        background: #fff;
        border: 1px solid rgba(226, 232, 240, 0.95);
        box-shadow:
            0 1px 2px rgba(15, 23, 42, 0.04),
            0 6px 18px rgba(15, 23, 42, 0.06);
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }

    .glance-mobile-quiz-panel:active {
        transform: scale(0.99);
    }

    .glance-mobile-quiz-panel__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.5rem;
        min-width: 0;
    }

    .glance-mobile-quiz-panel__course {
        flex: 1;
        min-width: 0;
        font-size: 0.625rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: #64748b;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .glance-mobile-quiz-panel__type {
        flex-shrink: 0;
        display: inline-flex;
        align-items: center;
        padding: 0.1875rem 0.4375rem;
        border-radius: 9999px;
        font-size: 0.5625rem;
        font-weight: 700;
        letter-spacing: 0.03em;
        text-transform: uppercase;
        line-height: 1.2;
    }

    .glance-mobile-quiz-panel__type--quiz {
        color: #1d4ed8;
        background: #eff6ff;
        border: 1px solid #bfdbfe;
    }

    .glance-mobile-quiz-panel__type--midsem {
        color: #b45309;
        background: #fffbeb;
        border: 1px solid #fde68a;
    }

    .glance-mobile-quiz-panel__type--end_of_semester {
        color: #334155;
        background: #f1f5f9;
        border: 1px solid #cbd5e1;
    }

    .glance-mobile-quiz-panel__title {
        margin: 0;
        font-size: 0.8125rem;
        font-weight: 700;
        line-height: 1.35;
        letter-spacing: -0.02em;
        color: #0f172a;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .glance-mobile-quiz-panel__cta {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        margin-top: 0.125rem;
        padding: 0.6875rem 1rem;
        border-radius: 0.6875rem;
        font-size: 0.8125rem;
        font-weight: 700;
        letter-spacing: 0.03em;
        text-align: center;
        line-height: 1.25;
        font-variant-numeric: tabular-nums;
    }

    .glance-mobile-quiz-panel__cta--start {
        color: #fff;
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        box-shadow: 0 4px 14px rgba(5, 150, 105, 0.28);
    }

    .glance-mobile-quiz-panel__cta--continue {
        color: #fff;
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        box-shadow: 0 4px 14px rgba(37, 99, 235, 0.26);
    }

    .glance-mobile-quiz-panel__cta--countdown {
        color: #0f766e;
        background: linear-gradient(180deg, #ecfdf5 0%, #d1fae5 100%);
        border: 1px solid rgba(16, 185, 129, 0.32);
        text-transform: none;
        letter-spacing: 0.02em;
    }

    .glance-mobile-quiz-panel--countdown.is-ready .glance-mobile-quiz-panel__cta--countdown,
    .glance-mobile-quiz-panel__cta--start {
        color: #fff;
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        border-color: transparent;
        box-shadow: 0 4px 14px rgba(5, 150, 105, 0.28);
    }

    .glance-card__cta {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        align-self: flex-start;
        margin-top: 0.375rem;
        padding: 0.375rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.5625rem;
        font-weight: 700;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        line-height: 1.2;
        white-space: nowrap;
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }

    a.glance-card__body:hover .glance-card__cta--start,
    .glance-card:has(a.glance-card__body:hover) .glance-card__cta--start {
        transform: translateY(-1px);
        box-shadow: 0 5px 14px rgba(5, 150, 105, 0.35);
    }

    a.glance-card__body:hover .glance-card__cta--continue,
    .glance-card:has(a.glance-card__body:hover) .glance-card__cta--continue {
        transform: translateY(-1px);
        box-shadow: 0 5px 14px rgba(37, 99, 235, 0.28);
    }

    .glance-card__cta--start {
        color: #fff;
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        box-shadow: 0 3px 10px rgba(5, 150, 105, 0.28);
    }

    @keyframes glance-start-pulse {
        0%, 100% {
            box-shadow: 0 3px 10px rgba(5, 150, 105, 0.28);
            transform: scale(1);
        }
        50% {
            box-shadow: 0 6px 22px rgba(5, 150, 105, 0.48);
            transform: scale(1.05);
        }
    }

    @media (min-width: 640px) {
        .glance-card__cta--start {
            animation: glance-start-pulse 1.8s ease-in-out infinite;
        }

        a.glance-card__body:hover .glance-card__cta--start,
        .glance-card:has(a.glance-card__body:hover) .glance-card__cta--start {
            animation: none;
            transform: translateY(-1px) scale(1.05);
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .glance-card__cta--start {
            animation: none !important;
        }
    }

    .glance-card__cta--continue {
        color: #fff;
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        box-shadow: 0 3px 10px rgba(37, 99, 235, 0.25);
    }

    .glance-card__cta--countdown {
        color: #0f766e;
        background: linear-gradient(180deg, #ecfdf5 0%, #d1fae5 100%);
        border: 1px solid rgba(16, 185, 129, 0.28);
        box-shadow: 0 2px 6px rgba(16, 185, 129, 0.12);
        font-variant-numeric: tabular-nums;
        text-transform: none;
        letter-spacing: 0.02em;
        font-size: 0.5625rem;
    }

    .glance-card__chevron--emerald {
        background: #ecfdf5;
        color: #059669;
        opacity: 1;
        transform: none;
    }

    .glance-card--actionable .glance-card__chevron--emerald {
        opacity: 1;
    }

    .glance-card--has-cta .glance-card__body {
        align-items: flex-start;
    }

    .glance-card--has-cta .glance-card__content {
        width: 100%;
    }

    @media (max-width: 639px) {
        .glance-card__cta--in-card,
        .glance-card__label--hint {
            display: none !important;
        }

        .glance-card--has-cta .glance-card__body {
            flex-direction: row;
            align-items: center;
        }
    }

    @media (min-width: 640px) {
        .glance-mobile-quiz-panel {
            display: none !important;
        }

        .glance-card--has-cta .glance-card__body {
            align-items: flex-start;
        }
    }

    @media (min-width: 640px) {
        .glance-card__cta {
            margin-top: 0.5rem;
            padding: 0.4375rem 0.875rem;
            font-size: 0.625rem;
        }

        .glance-card__cta--countdown {
            font-size: 0.625rem;
        }

        .glance-card__label { font-size: 0.6875rem; }
    }

    .glance-card__chevron {
        position: absolute;
        right: 0.875rem;
        bottom: 0.875rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 1.375rem;
        height: 1.375rem;
        border-radius: 9999px;
        background: #f8fafc;
        color: #94a3b8;
        font-size: 0.5625rem;
        opacity: 0;
        transform: translateX(-4px);
        transition: opacity 0.22s ease, transform 0.22s ease, background 0.22s ease, color 0.22s ease;
    }

    a.glance-card:hover .glance-card__chevron,
    .glance-card:has(a.glance-card__body:hover) .glance-card__chevron {
        opacity: 1;
        transform: translateX(0);
    }

    .glance-card--blue:hover .glance-card__chevron { background: #eff6ff; color: #2563eb; }
    .glance-card--emerald:hover .glance-card__chevron { background: #ecfdf5; color: #059669; }
    .glance-card--amber:hover .glance-card__chevron { background: #fffbeb; color: #d97706; }
    .glance-card--violet:hover .glance-card__chevron { background: #f5f3ff; color: #7c3aed; }

    @media (max-width: 639px) {
        .glance-card {
            border-radius: 0.875rem;
            box-shadow:
                0 1px 2px rgba(15, 23, 42, 0.03),
                0 2px 10px rgba(15, 23, 42, 0.04);
        }

        .glance-card__body {
            flex-direction: row;
            align-items: center;
            gap: 0.6875rem;
            padding: 0.9375rem 0.875rem;
            min-height: 5.5rem;
        }

        .glance-card__icon {
            width: 2rem;
            height: 2rem;
            border-radius: 0.625rem;
            font-size: 0.78125rem;
            flex-shrink: 0;
            box-shadow: 0 4px 10px rgba(15, 23, 42, 0.1);
        }

        .glance-card__content {
            flex: 1;
            min-width: 0;
            gap: 0.1875rem;
        }

        .glance-card__value {
            font-size: 1.25rem;
            line-height: 1.1;
        }

        .glance-card__value--sm {
            font-size: 0.75rem;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .glance-card__label {
            font-size: 0.625rem;
            line-height: 1.35;
        }

        .glance-card__chevron {
            position: static;
            flex-shrink: 0;
            width: 1.125rem;
            height: 1.125rem;
            margin-left: auto;
            opacity: 0.45;
            transform: none;
            background: transparent;
            font-size: 0.5rem;
        }

        a.glance-card:hover .glance-card__chevron,
        .glance-card:has(a.glance-card__body:hover) .glance-card__chevron {
            opacity: 0.65;
            transform: none;
        }

        .glance-card__chevron--emerald {
            opacity: 1;
            background: #ecfdf5;
            color: #059669;
        }

        .glance-card__glow {
            width: 3.5rem;
            height: 3.5rem;
            top: -1rem;
            right: -1rem;
            opacity: 0.4;
        }
    }

    .sd-featured-quiz {
        width: 100%;
        min-width: 0;
    }

    @media (min-width: 1024px) {
        .sd-hero-row {
            align-items: stretch;
        }

        .sd-hero-row > * {
            display: flex;
            min-height: 0;
            height: 100%;
            align-self: stretch;
        }

        .sd-hero-row .sd-featured-quiz,
        .sd-hero-row .sd-hero-banner {
            flex: 1 1 auto;
            width: 100%;
            min-height: 100%;
            height: 100%;
        }

        .sd-hero-row .sd-featured-quiz__card {
            flex: 1 1 auto;
            min-height: 100%;
            height: 100%;
        }
    }

    .sd-hero-banner__img {
        object-fit: contain;
        object-position: center;
    }

    .sd-featured-quiz__card {
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        width: 100%;
        height: 100%;
        padding: 1rem 1.125rem 1.125rem;
        border-radius: 0.75rem;
        border: 1px solid #cbd5e1;
        background: #ffffff;
        box-shadow: none;
    }

    .sd-featured-quiz__card:hover {
        transform: none;
        border-color: #cbd5e1;
        box-shadow: none;
    }

    .sd-featured-quiz__card--countdown {
        border-color: #6ee7b7;
        background: #ecfdf5;
    }

    .sd-featured-quiz__card--countdown .sd-featured-quiz__eyebrow,
    .sd-featured-quiz__card--countdown .sd-featured-quiz__course,
    .sd-featured-quiz__card--countdown .sd-featured-quiz__meta {
        color: #047857;
    }

    .sd-featured-quiz__card--countdown .sd-featured-quiz__title {
        color: #064e3b;
    }

    .sd-featured-quiz__card--ready,
    .sd-featured-quiz__card--active {
        border-color: #bfdbfe;
        background: #eff6ff;
    }

    .sd-featured-quiz__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.5rem;
        margin-bottom: 0.625rem;
    }

    .sd-featured-quiz__eyebrow {
        font-size: 0.625rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #334155;
    }

    .sd-featured-quiz__badge {
        flex-shrink: 0;
        display: inline-flex;
        align-items: center;
        padding: 0.1875rem 0.5rem;
        border-radius: 9999px;
        font-size: 0.5625rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        line-height: 1.2;
    }

    .sd-featured-quiz__badge--quiz { color: #1d4ed8; background: #eff6ff; border: 1px solid #bfdbfe; }
    .sd-featured-quiz__badge--midsem { color: #b45309; background: #fffbeb; border: 1px solid #fde68a; }
    .sd-featured-quiz__badge--end_of_semester { color: #334155; background: #f1f5f9; border: 1px solid #cbd5e1; }

    .sd-featured-quiz__course {
        margin: 0 0 0.375rem;
        font-size: 0.6875rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: #1e293b;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .sd-featured-quiz__title {
        margin: 0;
        font-size: 0.9375rem;
        font-weight: 800;
        line-height: 1.35;
        letter-spacing: -0.02em;
        color: #0f172a;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .sd-featured-quiz__meta {
        margin: 0.625rem 0 0;
        font-size: 0.6875rem;
        line-height: 1.45;
        color: #475569;
    }

    .sd-featured-quiz__countdown {
        display: block;
        margin-top: auto;
        padding-top: 0.875rem;
        font-size: 1.625rem;
        font-weight: 800;
        line-height: 1;
        letter-spacing: -0.03em;
        font-variant-numeric: tabular-nums;
        color: #047857;
    }

    .sd-featured-quiz__countdown-label {
        display: block;
        margin-top: 0.25rem;
        font-size: 0.625rem;
        font-weight: 600;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        color: #059669;
    }

    .sd-featured-quiz__card--ready .sd-featured-quiz__countdown,
    .sd-featured-quiz__card--ready .sd-featured-quiz__countdown-label,
    .sd-featured-quiz__card.is-ready .sd-featured-quiz__countdown,
    .sd-featured-quiz__card.is-ready .sd-featured-quiz__countdown-label {
        display: none;
    }

    .sd-featured-quiz__cta {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        align-self: flex-start;
        margin-top: auto;
        padding-top: 0.875rem;
        padding: 0.625rem 0.875rem;
        margin-top: auto;
        border-radius: 0.6875rem;
        font-size: 0.6875rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        line-height: 1.2;
    }

    .sd-featured-quiz__cta--start {
        color: #fff;
        background: #059669;
        box-shadow: none;
    }

    .sd-featured-quiz__cta--continue {
        color: #fff;
        background: #2563eb;
        box-shadow: none;
    }

    .sd-featured-quiz__cta--muted {
        color: #475569;
        background: #f1f5f9;
        border: 1px solid #e2e8f0;
    }

    .sd-featured-quiz__card--countdown .sd-featured-quiz__cta {
        display: none;
    }

    .sd-featured-quiz__cta--after-countdown {
        display: none;
    }

    .sd-featured-quiz__card--ready .sd-featured-quiz__cta--after-countdown,
    .sd-featured-quiz__card--countdown.is-ready .sd-featured-quiz__cta--after-countdown {
        display: inline-flex;
    }
</style>
@endpush
