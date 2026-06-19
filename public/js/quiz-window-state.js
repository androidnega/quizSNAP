/**
 * QuizSnap window state: cross-platform fullscreen/maximized detection and helpers.
 * Used by quiz-ready gate and quiz-proctoring.js.
 */
(function () {
    'use strict';

    /** Tolerance (px) for "maximized": macOS menu bar/dock, Windows taskbar, snap edges. */
    var TOLERANCE_PX = 120;

    function isDocumentFullscreen() {
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

    /**
     * True if browser fullscreen is active or window is effectively maximized.
     * Checks Fullscreen API, display-mode, then viewport vs screen size.
     */
    function isFullscreenOrMaximized() {
        if (isDocumentFullscreen() || isDisplayModeFullscreen()) {
            return true;
        }
        if (typeof window.screen === 'undefined') {
            return false;
        }
        var availW = window.screen.availWidth || 0;
        var availH = window.screen.availHeight || 0;
        if (availW <= 0 || availH <= 0) {
            return false;
        }
        var innerW = window.innerWidth || 0;
        var innerH = window.innerHeight || 0;
        var outerW = window.outerWidth || 0;
        var outerH = window.outerHeight || 0;
        var tol = TOLERANCE_PX;

        if (innerW >= availW - tol && innerH >= availH - tol) {
            return true;
        }
        if (outerW >= availW - tol && outerH >= availH - tol) {
            return true;
        }
        return false;
    }

    function requestFullscreen() {
        var el = document.documentElement;
        var fn = el.requestFullscreen
            || el.webkitRequestFullscreen
            || el.mozRequestFullScreen
            || el.msRequestFullscreen;
        if (!fn) {
            return Promise.reject(new Error('unsupported'));
        }
        return Promise.resolve(fn.call(el));
    }

    /** Best-effort window maximize (may be blocked by the browser). */
    function tryMaximizeWindow() {
        try {
            if (window.screen && window.screen.availWidth > 0 && window.screen.availHeight > 0) {
                window.moveTo(0, 0);
                window.resizeTo(window.screen.availWidth, window.screen.availHeight);
            }
        } catch (e) {
            /* resizeTo/moveTo blocked in many browsers */
        }
    }

    /** Enter fullscreen when possible; otherwise try to maximize the window. */
    function requestMaximizeOrFullscreen() {
        return requestFullscreen().catch(function () {
            tryMaximizeWindow();
            if (isFullscreenOrMaximized()) {
                return Promise.resolve();
            }
            return Promise.reject(new Error('Could not enter full screen or maximize window'));
        });
    }

    /** Poll until fullscreen/maximized or timeout (handles delayed fullscreenchange). */
    function waitForFullscreenOrMaximized(maxMs) {
        maxMs = maxMs || 4000;
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

    function bindFullscreenSync(onChange) {
        if (typeof onChange !== 'function') {
            return function () {};
        }
        document.addEventListener('fullscreenchange', onChange);
        document.addEventListener('webkitfullscreenchange', onChange);
        document.addEventListener('mozfullscreenchange', onChange);
        window.addEventListener('resize', onChange);
        window.addEventListener('focus', onChange);
        return function () {
            document.removeEventListener('fullscreenchange', onChange);
            document.removeEventListener('webkitfullscreenchange', onChange);
            document.removeEventListener('mozfullscreenchange', onChange);
            window.removeEventListener('resize', onChange);
            window.removeEventListener('focus', onChange);
        };
    }

    window.QuizSnapWindowState = {
        isFullscreenOrMaximized: isFullscreenOrMaximized,
        requestFullscreen: requestFullscreen,
        requestMaximizeOrFullscreen: requestMaximizeOrFullscreen,
        waitForFullscreenOrMaximized: waitForFullscreenOrMaximized,
        tryMaximizeWindow: tryMaximizeWindow,
        bindFullscreenSync: bindFullscreenSync,
        TOLERANCE_PX: TOLERANCE_PX
    };
})();
