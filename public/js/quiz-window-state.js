/**
 * QuizSnap window state: browser full-screen detection (Fullscreen API only).
 * Used by quiz-ready gate and quiz-proctoring.js.
 *
 * Full screen stays required on iPhone — students should use Chrome and Allow
 * when prompted. We do not treat a maximized window as full screen.
 */
(function () {
    'use strict';

    function isIOS() {
        var ua = navigator.userAgent || '';
        return /iPad|iPhone|iPod/i.test(ua)
            || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
    }

    function isMobileLike() {
        return isIOS()
            || /Android|webOS|BlackBerry|IEMobile|Opera Mini|Mobile/i.test(navigator.userAgent || '')
            || (window.matchMedia && window.matchMedia('(max-width: 900px) and (pointer: coarse)').matches);
    }

    function isBrowserFullscreen() {
        return !!(
            document.fullscreenElement
            || document.webkitFullscreenElement
            || document.mozFullScreenElement
            || document.msFullscreenElement
        );
    }

    /** True only when the page is in browser full-screen mode (Fullscreen API). */
    function isFullscreenOrMaximized() {
        return isBrowserFullscreen();
    }

    function normalizePromise(value) {
        if (value && typeof value.then === 'function') {
            return value;
        }
        return Promise.resolve(value);
    }

    function uniqueElements(list) {
        var out = [];
        for (var i = 0; i < list.length; i++) {
            var el = list[i];
            if (el && out.indexOf(el) === -1) {
                out.push(el);
            }
        }
        return out;
    }

    function tryRequestFullscreenOn(el) {
        if (!el) {
            return null;
        }
        var fn = el.requestFullscreen
            || el.webkitRequestFullscreen
            || el.webkitRequestFullScreen
            || el.mozRequestFullScreen
            || el.msRequestFullscreen;
        if (!fn) {
            return null;
        }
        var optionSets = [{ navigationUI: 'hide' }, undefined];
        var lastError = null;
        for (var o = 0; o < optionSets.length; o++) {
            try {
                var ret = optionSets[o] !== undefined ? fn.call(el, optionSets[o]) : fn.call(el);
                return { ok: true, promise: normalizePromise(ret) };
            } catch (err) {
                lastError = err;
            }
        }
        return { ok: false, error: lastError || new Error('unsupported') };
    }

    function getPreferredFullscreenRoot() {
        return document.querySelector('.quiz-writing-content')
            || document.getElementById('quiz-mobile-root')
            || document.getElementById('quizsnap-app')
            || null;
    }

    /**
     * Request browser full screen on a user gesture.
     * On iOS, try documentElement first (Chrome/Safari Fullscreen API), then content root.
     */
    function requestFullscreenFromGesture(sourceEl) {
        if (isFullscreenOrMaximized()) {
            fsDebug('requestFullscreen skipped (already active)');
            return Promise.resolve();
        }
        var candidates;
        if (isIOS()) {
            candidates = uniqueElements([
                document.documentElement,
                document.body,
                getPreferredFullscreenRoot()
            ]);
        } else {
            candidates = uniqueElements([
                getPreferredFullscreenRoot(),
                document.documentElement,
                document.body
            ]);
        }
        fsDebug('requestFullscreenFromGesture', {
            candidates: candidates.length,
            sourceId: sourceEl && sourceEl.id,
            ios: isIOS()
        });

        var lastError = new Error('unsupported');
        for (var i = 0; i < candidates.length; i++) {
            var attempt = tryRequestFullscreenOn(candidates[i]);
            if (attempt && attempt.ok) {
                return attempt.promise.catch(function (err) {
                    lastError = err || lastError;
                    return Promise.reject(err);
                });
            }
            if (attempt && attempt.error) {
                lastError = attempt.error;
            }
        }
        return Promise.reject(lastError);
    }

    function requestFullscreen(sourceEl) {
        return requestFullscreenFromGesture(sourceEl || null);
    }

    function requestMaximizeOrFullscreen() {
        return requestFullscreen();
    }

    function waitForBrowserFullscreen(maxMs) {
        maxMs = maxMs || (isIOS() ? 12000 : 5000);
        return new Promise(function (resolve, reject) {
            if (isFullscreenOrMaximized()) {
                resolve();
                return;
            }
            var started = Date.now();
            function tick() {
                if (isFullscreenOrMaximized()) {
                    resolve();
                    return;
                }
                if (Date.now() - started >= maxMs) {
                    reject(new Error('timeout'));
                    return;
                }
                window.requestAnimationFrame(tick);
            }
            tick();
        });
    }

    function waitForFullscreenOrMaximized(maxMs) {
        return waitForBrowserFullscreen(maxMs);
    }

    function getFullscreenDeniedMessage() {
        if (isIOS()) {
            return 'Could not enter full screen on this phone. Open this quiz in Chrome (or Safari), tap Enter full screen again, then tap Allow. Stay in full screen for the whole quiz.';
        }
        if (isMobileLike()) {
            return 'Could not enter full screen. Tap Enter full screen and choose Allow. Use Chrome if your browser blocks full screen.';
        }
        return 'Could not enter full screen. Click the button and allow full screen in your browser, or press F11 (Windows) / Ctrl+Cmd+F (Mac).';
    }

    function getFullscreenHintMessage() {
        if (isIOS()) {
            return 'On iPhone, open this quiz in <strong>Chrome</strong>, tap <strong>Enter full screen</strong>, then tap <strong>Allow</strong>. Keep full screen on for the whole quiz.';
        }
        if (isMobileLike()) {
            return 'Your quiz must run in <strong>full screen</strong>. Tap below and choose <strong>Allow</strong>. Chrome works best on phones.';
        }
        return 'Your quiz runs in browser full screen so tabs and the address bar are hidden. Click below and choose <strong>Allow</strong> when your browser asks.';
    }

    function enterAndWait(maxMs, sourceEl) {
        var waitMs = maxMs || (isIOS() ? 12000 : 8000);
        return requestFullscreenFromGesture(sourceEl || null).then(function () {
            return waitForBrowserFullscreen(waitMs);
        });
    }

    function bindFullscreenSync(onChange) {
        if (typeof onChange !== 'function') {
            return function () {};
        }
        function wrapped() {
            fsDebug('fullscreenchange', { active: isFullscreenOrMaximized() });
            onChange();
        }
        document.addEventListener('fullscreenchange', wrapped);
        document.addEventListener('webkitfullscreenchange', wrapped);
        document.addEventListener('mozfullscreenchange', wrapped);
        document.addEventListener('MSFullscreenChange', wrapped);
        return function () {
            document.removeEventListener('fullscreenchange', wrapped);
            document.removeEventListener('webkitfullscreenchange', wrapped);
            document.removeEventListener('mozfullscreenchange', wrapped);
            document.removeEventListener('MSFullscreenChange', wrapped);
        };
    }

    function fsDebugEnabled() {
        try {
            if (window.QuizSnapFsDebug === true) return true;
            if (sessionStorage.getItem('quizsnap_fs_debug') === '1') return true;
            return /(?:\?|&)fsdebug=1(?:&|$)/.test(window.location.search || '');
        } catch (e) {
            return false;
        }
    }

    function fsDebug(message, detail) {
        if (!fsDebugEnabled()) return;
        var payload = detail !== undefined ? detail : '';
        console.log('[QuizSnap FS]', message, payload);
        try {
            var hud = document.getElementById('quizsnap-fs-debug-hud');
            if (!hud) {
                hud = document.createElement('div');
                hud.id = 'quizsnap-fs-debug-hud';
                hud.setAttribute('aria-hidden', 'true');
                hud.style.cssText = 'position:fixed;bottom:8px;left:8px;z-index:99999;max-width:90vw;padding:6px 8px;font:11px/1.35 monospace;background:rgba(0,0,0,.82);color:#a7f3d0;border-radius:6px;pointer-events:none;white-space:pre-wrap;';
                document.body.appendChild(hud);
            }
            var line = message + (payload && typeof payload === 'object' ? ' ' + JSON.stringify(payload) : (payload ? ' ' + payload : ''));
            hud.textContent = line + '\n' + (hud.textContent || '').split('\n').slice(0, 4).join('\n');
        } catch (e) { /* ignore */ }
    }

    if (fsDebugEnabled()) {
        console.log('[QuizSnap FS] Debug logging enabled (?fsdebug=1 or sessionStorage quizsnap_fs_debug=1)');
    }

    var FULLSCREEN_BUTTON_IDS = ['resize-blur-enter-fs-btn', 'quiz-fs-gate-btn'];

    function bindEnterFullscreenButton(btn) {
        if (!btn || btn.dataset.quizsnapFsBound === '1') {
            return;
        }
        btn.dataset.quizsnapFsBound = '1';
        btn.addEventListener('click', function onEnterFullscreenClick() {
            if (btn.dataset.quizsnapFsBusy === '1') {
                return;
            }
            btn.dataset.quizsnapFsBusy = '1';
            fsDebug('enter fullscreen button clicked', { id: btn.id, ios: isIOS() });

            var enterPromise;
            try {
                enterPromise = enterAndWait(isIOS() ? 12000 : 8000, btn);
            } catch (err) {
                btn.dataset.quizsnapFsBusy = '0';
                alert(getFullscreenDeniedMessage());
                return;
            }

            enterPromise.then(function () {
                fsDebug('fullscreen active after button click', { id: btn.id });
                document.dispatchEvent(new CustomEvent('quizsnap:fullscreen-entered', {
                    bubbles: true,
                    detail: { buttonId: btn.id }
                }));
            }).catch(function (err) {
                fsDebug('fullscreen enter failed', { id: btn.id, error: err && err.message ? err.message : String(err) });
                alert(getFullscreenDeniedMessage());
            }).finally(function () {
                btn.dataset.quizsnapFsBusy = '0';
            });
        });
    }

    function bindKnownFullscreenButtons() {
        for (var i = 0; i < FULLSCREEN_BUTTON_IDS.length; i++) {
            bindEnterFullscreenButton(document.getElementById(FULLSCREEN_BUTTON_IDS[i]));
        }
    }

    bindKnownFullscreenButtons();
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bindKnownFullscreenButtons);
    }

    window.QuizSnapWindowState = {
        isIOS: isIOS,
        isMobileLike: isMobileLike,
        isBrowserFullscreen: isBrowserFullscreen,
        isFullscreenOrMaximized: isFullscreenOrMaximized,
        requestFullscreen: requestFullscreen,
        requestFullscreenFromGesture: requestFullscreenFromGesture,
        requestMaximizeOrFullscreen: requestMaximizeOrFullscreen,
        waitForBrowserFullscreen: waitForBrowserFullscreen,
        waitForFullscreenOrMaximized: waitForFullscreenOrMaximized,
        enterAndWait: enterAndWait,
        getFullscreenDeniedMessage: getFullscreenDeniedMessage,
        getFullscreenHintMessage: getFullscreenHintMessage,
        bindFullscreenSync: bindFullscreenSync,
        bindEnterFullscreenButton: bindEnterFullscreenButton,
        getPreferredFullscreenRoot: getPreferredFullscreenRoot,
        bindKnownFullscreenButtons: bindKnownFullscreenButtons,
        fsDebug: fsDebug
    };
})();
