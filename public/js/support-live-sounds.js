/**
 * QuizSnap live support — message alerts, continuous ring, and typing sounds.
 */
(function () {
    'use strict';

    var ctx = null;
    var typingLastAt = 0;
    var TYPING_COOLDOWN_MS = 72;
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
        tone(880, 0.09, 0.048, 'sine');
        setTimeout(function () { tone(1175, 0.12, 0.044, 'sine'); }, 75);
        setTimeout(function () { tone(1480, 0.16, 0.036, 'sine'); }, 155);
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

    function playTypingRemote() {
        var now = Date.now();
        if (now - typingLastAt < TYPING_COOLDOWN_MS) return;
        typingLastAt = now;
        var taps = [620, 660, 640, 680, 650];
        var base = taps[Math.floor(Math.random() * taps.length)];
        tone(base, 0.028, 0.02, 'triangle');
        setTimeout(function () { tone(base + 40, 0.022, 0.014, 'triangle'); }, 38);
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
        playTyping: playTypingRemote,
        unlock: function () {
            audioContext();
        },
    };

    document.addEventListener('click', function unlockOnce() {
        window.QuizSnapSupportSounds.unlock();
        document.removeEventListener('click', unlockOnce);
    }, { once: true, capture: true });
})();
