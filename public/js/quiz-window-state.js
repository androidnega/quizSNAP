/**
 * QuizSnap window state: cross-platform fullscreen/maximized detection and helpers.
 * Used by quiz-ready gate and quiz-proctoring.js.
 */
(function () {
    'use strict';

    /** Tolerance (px) for "maximized": macOS menu bar/dock, Windows taskbar, snap edges. */
    var TOLERANCE_PX = 100;

    /**
     * True if browser fullscreen is active or window is effectively maximized.
     * Priority: Fullscreen API first, then window vs screen comparison.
     */
    function isFullscreenOrMaximized() {
        if (document.fullscreenElement || document.webkitFullscreenElement) {
            return true;
        }
        var outerW = window.outerWidth;
        var outerH = window.outerHeight;
        if (typeof window.screen === 'undefined') {
            return false;
        }
        var availW = window.screen.availWidth;
        var availH = window.screen.availHeight;
        if (availW <= 0 || availH <= 0) {
            return false;
        }
        return (outerW >= availW - TOLERANCE_PX && outerH >= availH - TOLERANCE_PX);
    }

    function requestFullscreen() {
        var el = document.documentElement;
        var fn = el.requestFullscreen || el.webkitRequestFullscreen || el.mozRequestFullScreen || el.msRequestFullscreen;
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

    function bindFullscreenSync(onChange) {
        if (typeof onChange !== 'function') {
            return function () {};
        }
        document.addEventListener('fullscreenchange', onChange);
        document.addEventListener('webkitfullscreenchange', onChange);
        window.addEventListener('resize', onChange);
        return function () {
            document.removeEventListener('fullscreenchange', onChange);
            document.removeEventListener('webkitfullscreenchange', onChange);
            window.removeEventListener('resize', onChange);
        };
    }

    window.QuizSnapWindowState = {
        isFullscreenOrMaximized: isFullscreenOrMaximized,
        requestFullscreen: requestFullscreen,
        requestMaximizeOrFullscreen: requestMaximizeOrFullscreen,
        tryMaximizeWindow: tryMaximizeWindow,
        bindFullscreenSync: bindFullscreenSync,
        TOLERANCE_PX: TOLERANCE_PX
    };
})();
