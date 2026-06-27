<style>
    .qs-support-modal {
        position: fixed;
        inset: 0;
        z-index: 120;
        display: flex;
        align-items: flex-end;
        justify-content: center;
        padding: max(1rem, env(safe-area-inset-top)) max(1rem, env(safe-area-inset-right)) max(1rem, env(safe-area-inset-bottom)) max(1rem, env(safe-area-inset-left));
        pointer-events: none;
        visibility: hidden;
        opacity: 0;
        transition: opacity 0.24s ease, visibility 0.24s ease;
    }
    .qs-support-modal.is-open {
        pointer-events: auto;
        visibility: visible;
        opacity: 1;
    }
    .qs-support-modal__backdrop {
        position: absolute;
        inset: 0;
        background: rgba(15, 23, 42, 0.45);
        backdrop-filter: blur(4px);
        -webkit-backdrop-filter: blur(4px);
    }
    .qs-support-modal__panel {
        position: relative;
        z-index: 1;
        width: min(100%, 28rem);
        max-height: min(92vh, 36rem);
        overflow: auto;
        border-radius: 1.25rem;
        background: #fff;
        box-shadow: 0 24px 64px -24px rgba(15, 23, 42, 0.45);
        border: 1px solid rgba(226, 232, 240, 0.95);
        transform: translateY(1.25rem) scale(0.98);
        transition: transform 0.28s cubic-bezier(0.22, 1.14, 0.36, 1);
    }
    .qs-support-modal.is-open .qs-support-modal__panel {
        transform: translateY(0) scale(1);
    }
    @media (min-width: 640px) {
        .qs-support-modal {
            align-items: center;
        }
    }
    .qs-support-modal__header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 0.75rem;
        padding: 1.125rem 1.125rem 0.75rem;
        border-bottom: 1px solid #f1f5f9;
    }
    .qs-support-modal__title {
        margin: 0;
        font-size: 1.0625rem;
        font-weight: 700;
        color: #0f172a;
        letter-spacing: -0.02em;
    }
    .qs-support-modal__subtitle {
        margin: 0.25rem 0 0;
        font-size: 0.8125rem;
        line-height: 1.45;
        color: #64748b;
    }
    .qs-support-modal__close {
        flex-shrink: 0;
        width: 2rem;
        height: 2rem;
        border: none;
        border-radius: 9999px;
        background: #f1f5f9;
        color: #475569;
        cursor: pointer;
        display: grid;
        place-items: center;
    }
    .qs-support-modal__body {
        padding: 0.875rem 1.125rem 1.125rem;
    }
    .qs-support-modal__context {
        display: flex;
        flex-wrap: wrap;
        gap: 0.375rem;
        margin-bottom: 0.875rem;
    }
    .qs-support-modal__chip {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        padding: 0.25rem 0.625rem;
        border-radius: 9999px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        font-size: 0.6875rem;
        font-weight: 600;
        color: #475569;
    }
    .qs-support-modal__chip--warn {
        background: #fff7ed;
        border-color: #fed7aa;
        color: #9a3412;
    }
    .qs-support-modal__label {
        display: block;
        font-size: 0.8125rem;
        font-weight: 600;
        color: #334155;
        margin-bottom: 0.375rem;
    }
    .qs-support-modal__textarea {
        width: 100%;
        min-height: 6.5rem;
        resize: vertical;
        border: 1px solid #cbd5e1;
        border-radius: 0.75rem;
        padding: 0.75rem 0.875rem;
        font-size: 0.875rem;
        line-height: 1.45;
        color: #0f172a;
        background: #fff;
    }
    .qs-support-modal__textarea:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.18);
    }
    .qs-support-modal__textarea.is-invalid {
        border-color: #f87171;
        box-shadow: 0 0 0 3px rgba(248, 113, 113, 0.15);
    }
    .qs-support-modal__quick {
        display: flex;
        flex-wrap: wrap;
        gap: 0.375rem;
        margin-top: 0.625rem;
    }
    .qs-support-modal__quick-btn {
        border: 1px solid #e2e8f0;
        background: #f8fafc;
        color: #475569;
        border-radius: 9999px;
        padding: 0.3125rem 0.6875rem;
        font-size: 0.6875rem;
        font-weight: 600;
        cursor: pointer;
    }
    .qs-support-modal__quick-btn:hover {
        background: #eff6ff;
        border-color: #bfdbfe;
        color: #1d4ed8;
    }
    .qs-support-modal__error {
        display: none;
        margin-top: 0.5rem;
        font-size: 0.75rem;
        color: #b91c1c;
    }
    .qs-support-modal__error.is-visible {
        display: block;
    }
    .qs-support-modal__actions {
        display: flex;
        gap: 0.625rem;
        margin-top: 1rem;
    }
    .qs-support-modal__btn {
        flex: 1;
        border: none;
        border-radius: 0.75rem;
        padding: 0.6875rem 1rem;
        font-size: 0.875rem;
        font-weight: 600;
        cursor: pointer;
    }
    .qs-support-modal__btn--ghost {
        background: #f1f5f9;
        color: #475569;
    }
    .qs-support-modal__btn--live {
        background: linear-gradient(145deg, #6366f1 0%, #4f46e5 100%);
        color: #fff;
    }
    .qs-support-modal__btn--live:disabled {
        opacity: 0.55;
        cursor: not-allowed;
    }
    @media (prefers-reduced-motion: reduce) {
        .qs-support-modal,
        .qs-support-modal__panel {
            transition-duration: 0.01ms !important;
        }
    }
</style>
<div id="qs-support-modal" class="qs-support-modal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="qs-support-modal-title">
    <div class="qs-support-modal__backdrop" data-qs-support-modal-close tabindex="-1"></div>
    <div class="qs-support-modal__panel">
        <div class="qs-support-modal__header">
            <div>
                <h2 id="qs-support-modal-title" class="qs-support-modal__title">Describe your issue</h2>
                <p class="qs-support-modal__subtitle">Describe your issue and we will connect you to live chat. If your <strong>index is not found</strong>, contact your class rep or lecturer.</p>
            </div>
            <button type="button" class="qs-support-modal__close" data-qs-support-modal-close aria-label="Close">
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form id="qs-support-modal-form" class="qs-support-modal__body" novalidate>
            <div id="qs-support-modal-context" class="qs-support-modal__context" hidden></div>
            <label for="qs-support-modal-description" class="qs-support-modal__label">What do you need help with?</label>
            <textarea id="qs-support-modal-description" class="qs-support-modal__textarea" rows="4" required minlength="10" maxlength="1200" placeholder="Example: I cannot start my quiz — the camera stays on “waiting” and never turns green."></textarea>
            <div class="qs-support-modal__quick" aria-label="Common issues">
                <button type="button" class="qs-support-modal__quick-btn" data-qs-support-index-help="1">My index is not recognized</button>
                <button type="button" class="qs-support-modal__quick-btn" data-qs-support-quick="I cannot log in to my account.">Login problem</button>
                <button type="button" class="qs-support-modal__quick-btn" data-qs-support-quick="My quiz will not start or the camera is not working.">Quiz / camera</button>
                <button type="button" class="qs-support-modal__quick-btn" data-qs-support-quick="I submitted my quiz but cannot see my score or result.">Results / score</button>
                <button type="button" class="qs-support-modal__quick-btn" data-qs-support-quick="The page shows an error and I need help fixing it.">Error on screen</button>
            </div>
            <p id="qs-support-modal-error" class="qs-support-modal__error" role="alert">Please describe your issue in at least 10 characters.</p>
            <div class="qs-support-modal__actions">
                <button type="button" class="qs-support-modal__btn qs-support-modal__btn--ghost" data-qs-support-modal-close>Cancel</button>
                <button type="submit" class="qs-support-modal__btn qs-support-modal__btn--live" id="qs-support-modal-submit">Start live chat</button>
            </div>
        </form>
    </div>
</div>
