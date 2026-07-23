(function () {
    'use strict';

    var root = document.getElementById('birthday-surprise-root');
    if (!root) return;

    var storageKey = root.getAttribute('data-storage-key') || 'quizsnap_birthday_surprise';
    if (window.localStorage && localStorage.getItem(storageKey) === '1') {
        return;
    }

    var playSong = root.getAttribute('data-play-song') === '1';
    var songUrl = (root.getAttribute('data-song-url') || '').trim();
    var backdrop = document.getElementById('birthday-surprise-backdrop');
    var closeBtn = document.getElementById('birthday-surprise-close');
    var ctaBtn = document.getElementById('birthday-surprise-cta');
    var confettiHost = document.getElementById('birthday-surprise-confetti');
    var balloonsHost = root.querySelector('.birthday-surprise-balloons');

    function closeModal() {
        root.classList.remove('is-open');
        document.body.classList.remove('birthday-surprise-open');
        try { localStorage.setItem(storageKey, '1'); } catch (e) { /* ignore */ }
    }

    function spawnBalloons() {
        if (!balloonsHost) return;
        var colors = ['#f59e0b', '#ef4444', '#22c55e', '#3b82f6', '#a855f7', '#ec4899'];
        for (var i = 0; i < 14; i++) {
            (function (idx) {
                setTimeout(function () {
                    var b = document.createElement('div');
                    b.className = 'birthday-balloon';
                    b.style.left = (5 + Math.random() * 90) + '%';
                    b.style.background = colors[idx % colors.length];
                    b.style.setProperty('--drift', ((Math.random() - 0.5) * 80) + 'px');
                    b.style.animationDuration = (9 + Math.random() * 6) + 's';
                    balloonsHost.appendChild(b);
                    setTimeout(function () { b.remove(); }, 16000);
                }, idx * 280);
            })(i);
        }
    }

    function spawnConfetti() {
        if (!confettiHost) return;
        var colors = ['#fde047', '#fbbf24', '#f472b6', '#60a5fa', '#34d399', '#fb7185'];
        for (var i = 0; i < 48; i++) {
            var p = document.createElement('span');
            p.className = 'birthday-confetti-piece';
            p.style.left = (Math.random() * 100) + 'vw';
            p.style.background = colors[i % colors.length];
            p.style.animationDuration = (2.5 + Math.random() * 2.5) + 's';
            p.style.animationDelay = (Math.random() * 0.8) + 's';
            confettiHost.appendChild(p);
            setTimeout(function (el) { el.remove(); }, 6000, p);
        }
    }

    function playBirthdayTune() {
        if (!playSong) return;
        if (songUrl) {
            var audio = new Audio(songUrl);
            audio.volume = 0.55;
            audio.play().catch(function () { playSynthTune(); });
            return;
        }
        playSynthTune();
    }

    function playSynthTune() {
        try {
            var AudioCtx = window.AudioContext || window.webkitAudioContext;
            if (!AudioCtx) return;
            var ctx = new AudioCtx();
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
                gain.gain.exponentialRampToValueAtTime(0.12, start + 0.04);
                gain.gain.exponentialRampToValueAtTime(0.0001, start + n.d);
                osc.stop(start + n.d + 0.05);
            });
            setTimeout(function () { ctx.close().catch(function () {}); }, 3000);
        } catch (e) { /* ignore */ }
    }

    function openModal() {
        root.classList.add('is-open');
        document.body.classList.add('birthday-surprise-open');
        spawnBalloons();
        spawnConfetti();
        setTimeout(playBirthdayTune, 400);
    }

    if (closeBtn) closeBtn.addEventListener('click', closeModal);
    if (ctaBtn) ctaBtn.addEventListener('click', closeModal);
    if (backdrop) backdrop.addEventListener('click', closeModal);

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { setTimeout(openModal, 600); });
    } else {
        setTimeout(openModal, 600);
    }
})();
