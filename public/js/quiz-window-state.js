/**
 * QuizSnap window state: browser full-screen detection (Fullscreen API only).
 * Used by quiz-ready gate and quiz-proctoring.js.
 *
 * We intentionally do NOT treat a maximized window as full screen — tabs and
 * the address bar must be hidden via the browser Fullscreen API (or F11).
 */
(function () {
    'use strict';

    function isBrowserFullscreen() {
        return !!(
            document.fullscreenElement
            || document.webkitFullscreenElement
            || document.mozFullScreenElement
            || document.msFullscreenElement
        );
    }

    function isDisplayModeFullscreen() {
        try {
            return window.matchMedia && window.matchMedia('(display-mode: fullscreen)').matches;
        } catch (e) {
            return false;
        }
    }

    /** True only when the page is in browser full-screen mode. */
    function isFullscreenOrMaximized() {
        return isBrowserFullscreen() || isDisplayModeFullscreen();
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

    /** Try Fullscreen API on one element (with/without options, vendor-prefixed). */
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

    /**
     * Request full screen starting from the clicked element (user gesture), then documentElement/body.
     * Browsers require requestFullscreen in the same turn as the click; call this synchronously from click.
     */
    function requestFullscreenFromGesture(sourceEl) {
        if (isFullscreenOrMaximized()) {
            fsDebug('requestFullscreen skipped (already active)');
            return Promise.resolve();
        }
        var candidates = [];
        if (sourceEl && sourceEl.nodeType === 1) {
            var node = sourceEl;
            while (node) {
                candidates.push(node);
                if (node === document.documentElement) {
                    break;
                }
                node = node.parentElement;
            }
        }
        candidates.push(document.documentElement, document.body);
        candidates = uniqueElements(candidates);
        fsDebug('requestFullscreenFromGesture', { candidates: candidates.length, sourceId: sourceEl && sourceEl.id });

        var lastError = new Error('unsupported');
        for (var i = 0; i < candidates.length; i++) {
            var attempt = tryRequestFullscreenOn(candidates[i]);
            if (attempt && attempt.ok) {
                return attempt.promise;
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

    /** Quiz enforcement entry point — full screen API only (no window maximize fallback). */
    function requestMaximizeOrFullscreen() {
        return requestFullscreen();
    }

    function waitForBrowserFullscreen(maxMs) {
        maxMs = maxMs || 5000;
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

    var FULLSCREEN_DENIED_MESSAGE = 'Could not enter full screen. Click the button and allow full screen in your browser, or press F11 (Windows) / Ctrl+Cmd+F (Mac).';

    function getFullscreenDeniedMessage() {
        return FULLSCREEN_DENIED_MESSAGE;
    }

    /** Request browser full screen, then wait until the API reports active. */
    function enterAndWait(maxMs, sourceEl) {
        return requestFullscreenFromGesture(sourceEl || null).then(function () {
            return waitForBrowserFullscreen(maxMs);
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
        btn.addEventListener('click', function onEnterFullscreenClick(evt) {
            if (btn.dataset.quizsnapFsBusy === '1') {
                return;
            }
            btn.dataset.quizsnapFsBusy = '1';
            fsDebug('enter fullscreen button clicked', { id: btn.id });

            var enterPromise;
            try {
                enterPromise = enterAndWait(8000, btn);
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
        isBrowserFullscreen: isBrowserFullscreen,
        isFullscreenOrMaximized: isFullscreenOrMaximized,
        requestFullscreen: requestFullscreen,
        requestFullscreenFromGesture: requestFullscreenFromGesture,
        requestMaximizeOrFullscreen: requestMaximizeOrFullscreen,
        waitForBrowserFullscreen: waitForBrowserFullscreen,
        waitForFullscreenOrMaximized: waitForFullscreenOrMaximized,
        enterAndWait: enterAndWait,
        getFullscreenDeniedMessage: getFullscreenDeniedMessage,
        bindFullscreenSync: bindFullscreenSync,
        bindEnterFullscreenButton: bindEnterFullscreenButton,
        bindKnownFullscreenButtons: bindKnownFullscreenButtons,
        fsDebug: fsDebug
    };
})();
