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

        var ws = window.QuizSnapWindowState || {};
        var fsDebug = ws.fsDebug || function () {};
        var gate = document.getElementById(options.gateId || 'quiz-fs-gate');
        var gateBtn = document.getElementById(options.gateBtnId || 'quiz-fs-gate-btn');
        var gateHint = document.getElementById(options.gateHintId || 'quiz-fs-gate-hint');
        var startBtn = document.getElementById(options.startBtnId || 'start-quiz-btn');
        var startForm = document.getElementById(options.startFormId || 'quiz-start-form');

        function isOk() {
            if (ws.isBrowserFullscreen) {
                return ws.isBrowserFullscreen() || (ws.isFullscreenOrMaximized && ws.isFullscreenOrMaximized());
            }
            return ws.isFullscreenOrMaximized ? ws.isFullscreenOrMaximized() : false;
        }

        function requestFs() {
            if (ws.requestFullscreen) {
                return ws.requestFullscreen();
            }
            return Promise.reject(new Error('unsupported'));
        }

        function waitReady() {
            if (ws.waitForBrowserFullscreen) {
                return ws.waitForBrowserFullscreen(5000);
            }
            if (ws.waitForFullscreenOrMaximized) {
                return ws.waitForFullscreenOrMaximized(5000);
            }
            return isOk() ? Promise.resolve() : Promise.reject(new Error('timeout'));
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
            if (!startForm) return Promise.reject(new Error('no form'));
            var formData = new FormData(startForm);
            var csrf = formData.get('_token') || '';
            fsDebug('quiz-ready starting session via fetch');
            return fetch(startForm.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrf
                }
            })
                .then(function (r) {
                    return r.json().then(function (data) {
                        return { ok: r.ok, data: data };
                    });
                })
                .then(function (res) {
                    if (!res.ok || !res.data.success) {
                        throw new Error(res.data.message || 'Could not start quiz.');
                    }
                    if (startBtn) startBtn.disabled = true;
                    fsDebug('quiz-ready session started, navigating (fullscreen will reset on navigation)');
                    window.location.href = res.data.redirect || startForm.action;
                });
        }

        if (gateBtn) {
            gateBtn.addEventListener('click', function () {
                requestFs()
                    .then(waitReady)
                    .then(syncGate)
                    .catch(function () {
                        alert('Could not enter full screen. Click "Enter full screen" and allow it in your browser, or press F11 (Windows) / Ctrl+Cmd+F (Mac).');
                        syncGate();
                    });
            });
        }

        if (startForm) {
            startForm.addEventListener('submit', function (e) {
                e.preventDefault();
                if (!isOk()) {
                    lockStart();
                    alert('You must be in browser full screen before starting the quiz. Click "Enter full screen" first.');
                    return;
                }
                if (startBtn) startBtn.disabled = true;
                startQuizSession().catch(function (err) {
                    if (startBtn) startBtn.disabled = false;
                    alert(err && err.message ? err.message : 'Could not start quiz. Try again.');
                    syncGate();
                });
            });
        }

        if (ws.bindFullscreenSync) {
            ws.bindFullscreenSync(syncGate);
        } else {
            document.addEventListener('fullscreenchange', syncGate);
            document.addEventListener('webkitfullscreenchange', syncGate);
        }

        syncGate();
    }

    window.QuizSnapFullscreenGate = { init: init };
})();
