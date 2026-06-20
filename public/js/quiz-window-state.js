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
        var candidates = [document.documentElement, document.body];
        for (var i = 0; i < candidates.length; i++) {
            var el = candidates[i];
            if (!el) continue;
            var fn = el.requestFullscreen
                || el.webkitRequestFullscreen
                || el.mozRequestFullScreen
                || el.msRequestFullscreen;
            if (fn) {
                return Promise.resolve(fn.call(el));
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
        document.addEventListener('fullscreenchange', onChange);
        document.addEventListener('webkitfullscreenchange', onChange);
        document.addEventListener('mozfullscreenchange', onChange);
        document.addEventListener('MSFullscreenChange', onChange);
        return function () {
            document.removeEventListener('fullscreenchange', onChange);
            document.removeEventListener('webkitfullscreenchange', onChange);
            document.removeEventListener('mozfullscreenchange', onChange);
            document.removeEventListener('MSFullscreenChange', onChange);
        };
    }

    window.QuizSnapWindowState = {
        isBrowserFullscreen: isBrowserFullscreen,
        isFullscreenOrMaximized: isFullscreenOrMaximized,
        requestFullscreen: requestFullscreen,
        requestMaximizeOrFullscreen: requestMaximizeOrFullscreen,
        waitForBrowserFullscreen: waitForBrowserFullscreen,
        waitForFullscreenOrMaximized: waitForFullscreenOrMaximized,
        bindFullscreenSync: bindFullscreenSync
    };
})();
