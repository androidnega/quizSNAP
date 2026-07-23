@if(!empty($birthdayDashboardSurprise))
<div id="birthday-surprise-root" class="birthday-surprise-root" data-storage-key="{{ e($birthdayDashboardSurprise['storage_key']) }}" data-max-shows="{{ (int) ($birthdayDashboardSurprise['max_shows'] ?? 1) }}" data-play-song="{{ ($birthdayDashboardSurprise['play_song'] ?? false) ? '1' : '0' }}" data-song-url="{{ e($birthdayDashboardSurprise['song_url'] ?? '') }}" aria-hidden="true">
    <div class="birthday-surprise-backdrop" id="birthday-surprise-backdrop"></div>
    <div class="birthday-surprise-countdown" id="birthday-surprise-countdown" aria-live="polite" aria-atomic="true">
        <span class="birthday-surprise-countdown__label">Get ready</span>
        <span class="birthday-surprise-countdown__num" id="birthday-surprise-countdown-num">3</span>
    </div>
    <div class="birthday-surprise-balloons" aria-hidden="true"></div>
    <div class="birthday-surprise-confetti" id="birthday-surprise-confetti" aria-hidden="true"></div>

    <div class="birthday-surprise-modal" role="dialog" aria-modal="true" aria-labelledby="birthday-surprise-title">
        <div class="birthday-surprise-modal__glow" aria-hidden="true"></div>
        <div class="birthday-surprise-modal__inner">
            <p class="birthday-surprise-eyebrow">🎂 From the QuizSnap team</p>
            <h2 id="birthday-surprise-title" class="birthday-surprise-title">{{ $birthdayDashboardSurprise['title'] }}</h2>
            <p class="birthday-surprise-headline">{{ $birthdayDashboardSurprise['headline'] }}</p>
            <div class="birthday-surprise-photo-wrap">
                <img src="{{ e($birthdayDashboardSurprise['image_url']) }}" alt="" class="birthday-surprise-photo" loading="eager">
            </div>
            <p class="birthday-surprise-message">{{ $birthdayDashboardSurprise['message'] }}</p>
            <button type="button" class="birthday-surprise-cta" id="birthday-surprise-cta">Continue to dashboard</button>
        </div>
    </div>
</div>

@push('styles')
<style>
.birthday-surprise-root {
    position: fixed;
    inset: 0;
    z-index: 200;
    display: none;
    pointer-events: none;
}
.birthday-surprise-root.is-open {
    display: block;
    pointer-events: auto;
}
.birthday-surprise-backdrop {
    position: absolute;
    inset: 0;
    background: rgba(15, 23, 42, 0.55);
    backdrop-filter: blur(4px);
    opacity: 0;
    transition: opacity 0.45s ease;
}
.birthday-surprise-root.is-open .birthday-surprise-backdrop { opacity: 1; }
.birthday-surprise-root.is-flash .birthday-surprise-backdrop {
    background: rgba(251, 191, 36, 0.42);
    transition: background 0.08s ease;
}

.birthday-surprise-countdown {
    display: none;
    position: absolute;
    left: 50%;
    top: 50%;
    transform: translate(-50%, -50%);
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 0.35rem;
    z-index: 205;
    pointer-events: none;
    text-align: center;
}
.birthday-surprise-root.is-countdown .birthday-surprise-countdown {
    display: flex;
}
.birthday-surprise-root.is-countdown .birthday-surprise-modal {
    visibility: hidden;
    opacity: 0;
    pointer-events: none;
}
.birthday-surprise-countdown__label {
    font-size: 0.8125rem;
    font-weight: 700;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    color: rgba(255, 251, 235, 0.92);
    text-shadow: 0 2px 12px rgba(15, 23, 42, 0.45);
}
.birthday-surprise-countdown__num {
    display: block;
    font-size: clamp(4.5rem, 18vw, 7rem);
    font-weight: 800;
    line-height: 1;
    color: #fff;
    text-shadow:
        0 0 40px rgba(251, 191, 36, 0.85),
        0 8px 32px rgba(15, 23, 42, 0.35);
    transform: scale(0.72);
    opacity: 0;
    transition: transform 0.28s cubic-bezier(0.34, 1.4, 0.64, 1), opacity 0.18s ease;
}
.birthday-surprise-countdown__num.is-pop {
    transform: scale(1);
    opacity: 1;
}
.birthday-surprise-countdown__num.is-exit {
    transform: scale(1.15);
    opacity: 0;
    transition: transform 0.22s ease, opacity 0.22s ease;
}

.birthday-surprise-modal {
    position: absolute;
    left: 50%;
    top: 50%;
    transform: translate(-50%, -46%) scale(0.92);
    width: min(92vw, 28rem);
    opacity: 0;
    transition: transform 0.55s cubic-bezier(0.34, 1.4, 0.64, 1), opacity 0.4s ease;
}
.birthday-surprise-root.is-open .birthday-surprise-modal {
    transform: translate(-50%, -50%) scale(1);
    opacity: 1;
}
.birthday-surprise-modal__inner {
    position: relative;
    background: linear-gradient(165deg, #fff 0%, #fffbeb 55%, #fef3c7 100%);
    border-radius: 1.25rem;
    padding: 1.75rem 1.5rem 1.5rem;
    text-align: center;
    box-shadow: 0 25px 60px -20px rgba(245, 158, 11, 0.45), 0 0 0 1px rgba(251, 191, 36, 0.35);
    max-height: min(88vh, 640px);
    overflow-y: auto;
}
.birthday-surprise-modal__glow {
    position: absolute;
    inset: -20%;
    background: radial-gradient(circle at 50% 0%, rgba(251, 191, 36, 0.35), transparent 55%);
    pointer-events: none;
    z-index: -1;
}
.birthday-surprise-close {
    position: absolute;
    top: 0.65rem;
    right: 0.75rem;
    z-index: 2;
    width: 2.25rem;
    height: 2.25rem;
    border: none;
    border-radius: 9999px;
    background: rgba(255,255,255,0.85);
    color: #64748b;
    font-size: 1.35rem;
    line-height: 1;
    cursor: pointer;
    box-shadow: 0 1px 4px rgba(0,0,0,0.08);
}
.birthday-surprise-eyebrow {
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: #b45309;
    margin: 0 0 0.5rem;
}
.birthday-surprise-title {
    font-size: 1.35rem;
    font-weight: 800;
    color: #0f172a;
    margin: 0 0 0.35rem;
    line-height: 1.2;
}
.birthday-surprise-headline {
    font-size: 1rem;
    font-weight: 600;
    color: #d97706;
    margin: 0 0 1rem;
}
.birthday-surprise-photo-wrap {
    margin: 0 auto 1rem;
    max-width: 12.5rem;
}
.birthday-surprise-photo {
    width: 100%;
    height: auto;
    aspect-ratio: 819 / 1024;
    border-radius: 1rem;
    border: 3px solid #fff;
    box-shadow: 0 12px 28px -8px rgba(15, 23, 42, 0.25);
    object-fit: cover;
    object-position: center top;
}
.birthday-surprise-message {
    font-size: 0.9375rem;
    line-height: 1.55;
    color: #334155;
    margin: 0 0 1.25rem;
    text-align: left;
}
.birthday-surprise-cta {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    padding: 0.75rem 1rem;
    border: none;
    border-radius: 0.75rem;
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: #fff;
    font-size: 0.9375rem;
    font-weight: 700;
    cursor: pointer;
    box-shadow: 0 8px 20px -6px rgba(217, 119, 6, 0.55);
}
.birthday-surprise-cta:hover { filter: brightness(1.05); }

.birthday-surprise-balloons {
    position: absolute;
    inset: 0;
    overflow: hidden;
    pointer-events: none;
}
.birthday-balloon {
    position: absolute;
    bottom: -120px;
    width: 42px;
    height: 52px;
    border-radius: 50% 50% 45% 45%;
    opacity: 0.92;
    animation: birthday-float-up linear forwards;
}
.birthday-balloon::after {
    content: '';
    position: absolute;
    bottom: -28px;
    left: 50%;
    width: 2px;
    height: 28px;
    background: rgba(100, 116, 139, 0.45);
    transform: translateX(-50%);
}
@keyframes birthday-float-up {
    0% { transform: translateY(0) translateX(0) rotate(0deg); opacity: 0; }
    8% { opacity: 0.95; }
    100% { transform: translateY(-110vh) translateX(var(--drift, 0px)) rotate(12deg); opacity: 0.2; }
}

.birthday-confetti-piece {
    position: fixed;
    top: -12px;
    width: 8px;
    height: 12px;
    opacity: 0.9;
    animation: birthday-confetti-fall linear forwards;
    pointer-events: none;
    z-index: 201;
}
@keyframes birthday-confetti-fall {
    to { transform: translateY(105vh) rotate(720deg); opacity: 0.3; }
}
</style>
@endpush

@push('scripts')
<script src="{{ asset('js/birthday-surprise.js') }}?v={{ filemtime(public_path('js/birthday-surprise.js')) }}"></script>
@endpush
@endif
