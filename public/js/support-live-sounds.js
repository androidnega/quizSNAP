/**
 * QuizSnap live support — message alerts, continuous ring, and typing sounds.
 */
(function () {
    'use strict';

    var ctx = null;
    var typingLastAt = 0;
    var localTypingLastAt = 0;
    var TYPING_COOLDOWN_MS = 80;
    var LOCAL_TYPING_COOLDOWN_MS = 100;
    var alertTimer = null;
    var alertSessionUuid = null;
    var ALERT_INTERVAL_MS = 2600;

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

    function playMessageOnce() {
        tone(880, 0.12, 0.055, 'sine');
        setTimeout(function () { tone(1175, 0.14, 0.05, 'sine'); }, 90);
    }

    function playRingBurst() {
        tone(784, 0.18, 0.07, 'sine');
        setTimeout(function () { tone(988, 0.18, 0.065, 'sine'); }, 200);
        setTimeout(function () { tone(784, 0.18, 0.06, 'sine'); }, 400);
        setTimeout(function () { tone(988, 0.22, 0.055, 'sine'); }, 620);
    }

    function startMessageAlert(sessionUuid) {
        alertSessionUuid = sessionUuid || 'any';
        playRingBurst();
        if (alertTimer) return;
        alertTimer = setInterval(playRingBurst, ALERT_INTERVAL_MS);
    }

    function stopMessageAlert() {
        if (alertTimer) {
            clearInterval(alertTimer);
            alertTimer = null;
        }
        alertSessionUuid = null;
    }

    window.QuizSnapSupportSounds = {
        playMessage: function () {
            playMessageOnce();
        },
        playMessageOnce: playMessageOnce,
        startMessageAlert: startMessageAlert,
        stopMessageAlert: stopMessageAlert,
        playAgentAvailable: function () {
            tone(523, 0.35, 0.06, 'sine');
            setTimeout(function () { tone(659, 0.45, 0.055, 'sine'); }, 180);
            setTimeout(function () { tone(784, 0.55, 0.05, 'sine'); }, 380);
        },
        playTyping: function () {
            var now = Date.now();
            if (now - typingLastAt < TYPING_COOLDOWN_MS) return;
            typingLastAt = now;
            tone(620, 0.045, 0.032, 'triangle');
            setTimeout(function () { tone(740, 0.04, 0.026, 'triangle'); }, 50);
        },
        playTypingLocal: function () {
            var now = Date.now();
            if (now - localTypingLastAt < LOCAL_TYPING_COOLDOWN_MS) return;
            localTypingLastAt = now;
            tone(520, 0.028, 0.014, 'triangle');
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
