/**
 * QuizSnap Reverb WebSocket client (Pusher protocol).
 * Single shared connection for dashboard, live proctor, and student quiz voice.
 */
(function () {
    'use strict';

    var pusher = null;
    var channelCache = {};

    function config() {
        return window.REVERB_CONFIG || null;
    }

    function isEnabled() {
        var c = config();
        return !!(c && c.key);
    }

    function buildOptions(c) {
        var useTls = (c.scheme || 'http') === 'https';
        var port = parseInt(c.port, 10) || (useTls ? 443 : 8080);
        return {
            wsHost: c.host,
            wsPort: port,
            wssPort: port,
            forceTLS: useTls,
            disableStats: true,
            enabledTransports: useTls ? ['ws', 'wss'] : ['ws'],
            cluster: 'mt1',
        };
    }

    function getPusher() {
        if (!isEnabled() || typeof Pusher === 'undefined') {
            return null;
        }
        if (pusher) {
            return pusher;
        }
        try {
            pusher = new Pusher(config().key, buildOptions(config()));
            pusher.connection.bind('connected', function () {
                window.dispatchEvent(new CustomEvent('quizsnap-reverb-connected'));
            });
            pusher.connection.bind('disconnected', function () {
                window.dispatchEvent(new CustomEvent('quizsnap-reverb-disconnected'));
            });
            pusher.connection.bind('error', function () {
                window.dispatchEvent(new CustomEvent('quizsnap-reverb-disconnected'));
            });
            pusher.subscribe('quizsnap').bind('DataUpdated', function (data) {
                window.dispatchEvent(new CustomEvent('quizsnap-data-updated', { detail: data || {} }));
            });
        } catch (e) {
            console.warn('[QuizSnap Reverb] init failed:', e);
            pusher = null;
        }
        return pusher;
    }

    function subscribe(channelName) {
        var client = getPusher();
        if (!client || !channelName) {
            return null;
        }
        if (!channelCache[channelName]) {
            channelCache[channelName] = client.subscribe(channelName);
        }
        return channelCache[channelName];
    }

    function bind(channelName, eventName, handler) {
        var channel = subscribe(channelName);
        if (channel && typeof handler === 'function') {
            channel.bind(eventName, handler);
        }
    }

    function initWhenReady() {
        if (!isEnabled()) {
            return;
        }
        if (typeof Pusher !== 'undefined') {
            getPusher();
        } else {
            window.addEventListener('load', function () {
                getPusher();
            }, { once: true });
        }
    }

    window.QuizSnapReverb = {
        isEnabled: isEnabled,
        getPusher: getPusher,
        subscribe: subscribe,
        bind: bind,
    };

    initWhenReady();
})();
