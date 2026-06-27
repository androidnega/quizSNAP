/**
 * QuizSnap live support — subtle message and typing sounds (Web Audio API).
 */
(function () {
    'use strict';

    var ctx = null;
    var typingLastAt = 0;
    var TYPING_COOLDOWN_MS = 120;

    function audioContext() {
        if (!ctx) {
            try {
                ctx = new (window.AudioContext || window.webkitAudioContext)();
            } catch (e) {
                ctx = null;
            }
        }
        if (ctx && ctx.state === 'suspended') {
            ctx.resume().catch(function () {});
        }
        return ctx;
    }

    function tone(freq, duration, gain, type) {
        var ac = audioContext();
        if (!ac) return;
        var osc = ac.createOscillator();
        var g = ac.createGain();
        osc.type = type || 'sine';
        osc.frequency.value = freq;
        g.gain.value = gain || 0.04;
        osc.connect(g);
        g.connect(ac.destination);
        var now = ac.currentTime;
        g.gain.setValueAtTime(gain || 0.04, now);
        g.gain.exponentialRampToValueAtTime(0.001, now + duration);
        osc.start(now);
        osc.stop(now + duration + 0.02);
    }

    window.QuizSnapSupportSounds = {
        playMessage: function () {
            tone(880, 0.12, 0.05, 'sine');
            setTimeout(function () { tone(1175, 0.14, 0.045, 'sine'); }, 90);
        },
        playTyping: function () {
            var now = Date.now();
            if (now - typingLastAt < TYPING_COOLDOWN_MS) return;
            typingLastAt = now;
            tone(520, 0.03, 0.018, 'triangle');
        },
        unlock: function () {
            audioContext();
        },
    };

    document.addEventListener('click', function unlockOnce() {
        window.QuizSnapSupportSounds.unlock();
        document.removeEventListener('click', unlockOnce);
    }, { once: true, capture: true });
})();
