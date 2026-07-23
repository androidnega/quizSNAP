(function () {
    'use strict';

    var root = document.getElementById('birthday-surprise-root');
    if (!root) return;

    var storageKey = root.getAttribute('data-storage-key') || 'quizsnap_birthday_surprise';
    var maxShows = parseInt(root.getAttribute('data-max-shows') || '1', 10);
    if (isNaN(maxShows) || maxShows < 0) {
        maxShows = 1;
    }

    function getShowCount() {
        try {
            var raw = localStorage.getItem(storageKey);
            if (!raw) return 0;
            var n = parseInt(raw, 10);
            return isNaN(n) ? 0 : n;
        } catch (e) {
            return 0;
        }
    }

    if (maxShows > 0 && getShowCount() >= maxShows) {
        return;
    }

    var playSong = root.getAttribute('data-play-song') === '1';
    var songUrl = (root.getAttribute('data-song-url') || '').trim();
    var closeBtn = null;
    var ctaBtn = document.getElementById('birthday-surprise-cta');
    var confettiHost = document.getElementById('birthday-surprise-confetti');
    var balloonsHost = root.querySelector('.birthday-surprise-balloons');

    var balloonTimer = null;
    var confettiTimer = null;
    var synthLoopTimer = null;
    var audioEl = null;
    var synthCtx = null;

    function stopEffects() {
        if (balloonTimer) {
            clearInterval(balloonTimer);
            balloonTimer = null;
        }
        if (confettiTimer) {
            clearInterval(confettiTimer);
            confettiTimer = null;
        }
        if (synthLoopTimer) {
            clearInterval(synthLoopTimer);
            synthLoopTimer = null;
        }
        if (audioEl) {
            try {
                audioEl.pause();
                audioEl.currentTime = 0;
            } catch (e) { /* ignore */ }
            audioEl = null;
        }
        if (synthCtx) {
            try {
                synthCtx.close();
            } catch (e) { /* ignore */ }
            synthCtx = null;
        }
        if (balloonsHost) balloonsHost.innerHTML = '';
        if (confettiHost) confettiHost.innerHTML = '';
    }

    function recordShowAndClose() {
        stopEffects();
        root.classList.remove('is-open');
        document.body.classList.remove('birthday-surprise-open');
        try {
            if (maxShows > 0) {
                localStorage.setItem(storageKey, String(getShowCount() + 1));
            }
        } catch (e) { /* ignore */ }
    }

    function spawnBalloonBatch() {
        if (!balloonsHost) return;
        var colors = ['#f59e0b', '#ef4444', '#22c55e', '#3b82f6', '#a855f7', '#ec4899'];
        var count = 4 + Math.floor(Math.random() * 3);
        for (var i = 0; i < count; i++) {
            var b = document.createElement('div');
            b.className = 'birthday-balloon';
            b.style.left = (5 + Math.random() * 90) + '%';
            b.style.background = colors[Math.floor(Math.random() * colors.length)];
            b.style.setProperty('--drift', ((Math.random() - 0.5) * 100) + 'px');
            b.style.animationDuration = (10 + Math.random() * 8) + 's';
            balloonsHost.appendChild(b);
            (function (el) {
                setTimeout(function () {
                    if (el.parentNode) el.remove();
                }, 20000);
            })(b);
        }
    }

    function spawnConfettiBatch() {
        if (!confettiHost) return;
        var colors = ['#fde047', '#fbbf24', '#f472b6', '#60a5fa', '#34d399', '#fb7185'];
        var count = 16 + Math.floor(Math.random() * 12);
        for (var i = 0; i < count; i++) {
            var p = document.createElement('span');
            p.className = 'birthday-confetti-piece';
            p.style.left = (Math.random() * 100) + 'vw';
            p.style.background = colors[i % colors.length];
            p.style.animationDuration = (2.2 + Math.random() * 2.8) + 's';
            p.style.animationDelay = (Math.random() * 0.5) + 's';
            confettiHost.appendChild(p);
            (function (el) {
                setTimeout(function () {
                    if (el.parentNode) el.remove();
                }, 5500);
            })(p);
        }
    }

    function playSynthPhrase() {
        try {
            var AudioCtx = window.AudioContext || window.webkitAudioContext;
            if (!AudioCtx) return;
            if (!synthCtx || synthCtx.state === 'closed') {
                synthCtx = new AudioCtx();
            }
            var ctx = synthCtx;
            var notes = [
                { f: 392, t: 0, d: 0.18 },
                { f: 392, t: 0.2, d: 0.18 },
                { f: 440, t: 0.42, d: 0.36 },
                { f: 392, t: 0.82, d: 0.36 },
                { f: 523.25, t: 1.22, d: 0.36 },
                { f: 493.88, t: 1.65, d: 0.55 },
            ];
            notes.forEach(function (n) {
                var osc = ctx.createOscillator();
                var gain = ctx.createGain();
                osc.type = 'triangle';
                osc.frequency.value = n.f;
                gain.gain.value = 0.0001;
                osc.connect(gain);
                gain.connect(ctx.destination);
                var start = ctx.currentTime + n.t;
                osc.start(start);
                gain.gain.exponentialRampToValueAtTime(0.1, start + 0.04);
                gain.gain.exponentialRampToValueAtTime(0.0001, start + n.d);
                osc.stop(start + n.d + 0.05);
            });
        } catch (e) { /* ignore */ }
    }

    function startMusic() {
        if (!playSong) return;
        if (songUrl) {
            audioEl = new Audio(songUrl);
            audioEl.volume = 0.55;
            audioEl.loop = true;
            audioEl.play().catch(function () {
                audioEl = null;
                startSynthLoop();
            });
            return;
        }
        startSynthLoop();
    }

    function startSynthLoop() {
        playSynthPhrase();
        synthLoopTimer = setInterval(playSynthPhrase, 2800);
    }

    function openModal() {
        root.classList.add('is-open');
        document.body.classList.add('birthday-surprise-open');
        spawnBalloonBatch();
        spawnConfettiBatch();
        balloonTimer = setInterval(spawnBalloonBatch, 2200);
        confettiTimer = setInterval(spawnConfettiBatch, 1800);
        setTimeout(startMusic, 350);
    }

    if (closeBtn) closeBtn.addEventListener('click', recordShowAndClose);
    if (ctaBtn) ctaBtn.addEventListener('click', recordShowAndClose);

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { setTimeout(openModal, 600); });
    } else {
        setTimeout(openModal, 600);
    }
})();
