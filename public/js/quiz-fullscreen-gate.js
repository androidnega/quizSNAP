/**
 * Pre-quiz fullscreen gate for quiz-ready page.
 * Requires quiz-window-state.js loaded first.
 */
(function () {
    'use strict';

    function init(options) {
        options = options || {};
        if (!options.required) {
            var startBtn = document.getElementById(options.startBtnId || 'start-quiz-btn');
            if (startBtn) {
                startBtn.disabled = false;
            }
            return;
        }

        var ws = window.QuizSnapWindowState;
        if (!ws || !ws.isFullscreenOrMaximized || !ws.enterAndWait) {
            console.warn('[QuizSnap] quiz-window-state.js must load before quiz-fullscreen-gate.js');
            return;
        }

        var fsDebug = ws.fsDebug || function () {};
        var gate = document.getElementById(options.gateId || 'quiz-fs-gate');
        var gateBtn = document.getElementById(options.gateBtnId || 'quiz-fs-gate-btn');
        var gateHint = document.getElementById(options.gateHintId || 'quiz-fs-gate-hint');
        var startBtn = document.getElementById(options.startBtnId || 'start-quiz-btn');
        var startForm = document.getElementById(options.startFormId || 'quiz-start-form');
        var deniedMessage = ws.getFullscreenDeniedMessage ? ws.getFullscreenDeniedMessage() : 'Could not enter full screen.';

        function isOk() {
            return ws.isFullscreenOrMaximized();
        }

        function lockStart() {
            if (startBtn) {
                startBtn.disabled = true;
            }
            if (gate) {
                gate.classList.remove('hidden');
                gate.classList.add('flex');
                gate.setAttribute('aria-hidden', 'false');
            }
            if (gateHint) {
                gateHint.classList.add('hidden');
            }
        }

        function unlockStart() {
            if (startBtn) {
                startBtn.disabled = false;
            }
            if (gate) {
                gate.classList.add('hidden');
                gate.classList.remove('flex');
                gate.setAttribute('aria-hidden', 'true');
            }
            if (gateHint) {
                gateHint.classList.remove('hidden');
            }
        }

        function syncGate() {
            fsDebug('quiz-ready syncGate', { ok: isOk() });
            if (isOk()) {
                unlockStart();
            } else {
                lockStart();
            }
        }

        function startQuizSession() {
            if (!startForm) {
                return Promise.reject(new Error('no form'));
            }
            var formData = new FormData(startForm);
            var csrf = formData.get('_token') || '';
            fsDebug('quiz-ready starting session via fetch');
            return fetch(startForm.action, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrf
                }
            })
                .then(function (r) {
                    if (r.status === 419) {
                        throw new Error('Session expired. Please refresh this page and try again.');
                    }
                    return r.json().then(function (data) {
                        return { ok: r.ok, data: data };
                    });
                })
                .then(function (res) {
                    if (!res.ok || !res.data.success) {
                        throw new Error(res.data.message || 'Could not start quiz.');
                    }
                    if (startBtn) {
                        startBtn.disabled = true;
                    }
                    fsDebug('quiz-ready session started, navigating (fullscreen will reset on navigation)');
                    window.location.href = res.data.redirect || startForm.action;
                });
        }

        if (gateBtn && ws.bindEnterFullscreenButton) {
            ws.bindEnterFullscreenButton(gateBtn);
        }

        document.addEventListener('quizsnap:fullscreen-entered', function () {
            syncGate();
        });

        if (startForm) {
            startForm.addEventListener('submit', function (e) {
                e.preventDefault();
                if (!isOk()) {
                    lockStart();
                    alert('You must be in browser full screen before starting the quiz. Click "Enter full screen" first.');
                    return;
                }
                if (startBtn) {
                    startBtn.disabled = true;
                    startBtn.textContent = 'Starting...';
                }
                startQuizSession().catch(function (err) {
                    if (startBtn) {
                        startBtn.disabled = false;
                        startBtn.textContent = 'Start Quiz';
                    }
                    alert(err && err.message ? err.message : 'Could not start quiz. Try again.');
                    syncGate();
                });
            });
        }

        ws.bindFullscreenSync(syncGate);
        syncGate();
    }

    window.QuizSnapFullscreenGate = { init: init };
})();
