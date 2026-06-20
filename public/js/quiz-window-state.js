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

    function requestFullscreen() {
        if (isFullscreenOrMaximized()) {
            fsDebug('requestFullscreen skipped (already active)');
            return Promise.resolve();
        }
        fsDebug('requestFullscreen called');
        var candidates = [document.documentElement, document.body];
        var options = { navigationUI: 'hide' };
        for (var i = 0; i < candidates.length; i++) {
            var el = candidates[i];
            if (!el) continue;
            var fn = el.requestFullscreen
                || el.webkitRequestFullscreen
                || el.mozRequestFullScreen
                || el.msRequestFullscreen;
            if (fn) {
                try {
                    return Promise.resolve(fn.call(el, options));
                } catch (e1) {
                    try {
                        return Promise.resolve(fn.call(el));
                    } catch (e2) {
                        return Promise.reject(e2);
                    }
                }
            }
        }
        return Promise.reject(new Error('unsupported'));
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

    window.QuizSnapWindowState = {
        isBrowserFullscreen: isBrowserFullscreen,
        isFullscreenOrMaximized: isFullscreenOrMaximized,
        requestFullscreen: requestFullscreen,
        requestMaximizeOrFullscreen: requestMaximizeOrFullscreen,
        waitForBrowserFullscreen: waitForBrowserFullscreen,
        waitForFullscreenOrMaximized: waitForFullscreenOrMaximized,
        bindFullscreenSync: bindFullscreenSync,
        fsDebug: fsDebug
    };
})();
