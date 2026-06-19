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
            if (isOk()) {
                unlockStart();
            } else {
                lockStart();
            }
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
                if (!isOk()) {
                    e.preventDefault();
                    lockStart();
                    alert('You must be in browser full screen before starting the quiz. Click "Enter full screen" first.');
                }
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
