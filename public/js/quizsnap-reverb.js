/**
 * QuizSnap Reverb WebSocket client (Pusher protocol).
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

    function csrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    function buildOptions(c) {
        var useTls = (c.scheme || 'http') === 'https';
        var port = parseInt(c.port, 10) || (useTls ? 443 : 8080);
        var options = {
            wsHost: c.host,
            wsPort: port,
            wssPort: port,
            forceTLS: useTls,
            disableStats: true,
            enabledTransports: useTls ? ['ws', 'wss'] : ['ws'],
            cluster: 'mt1',
        };

        if (window.MONITORING_ACCESS || window.OPERATIONS_ACCESS || window.INTELLIGENCE_ACCESS) {
            options.authEndpoint = '/broadcasting/auth';
            options.auth = {
                headers: {
                    'X-CSRF-TOKEN': csrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
            };
        }

        return options;
    }

    function monitoringChannelName() {
        return window.MONITORING_ACCESS ? 'private-quizsnap-monitoring' : 'quizsnap-monitoring';
    }

    function operationsChannelName() {
        return window.OPERATIONS_ACCESS ? 'private-quizsnap-operations' : 'quizsnap-operations';
    }

    function intelligenceChannelName() {
        return window.INTELLIGENCE_ACCESS ? 'private-quizsnap-intelligence' : 'quizsnap-intelligence';
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
            if (window.MONITORING_ACCESS) {
                pusher.subscribe(monitoringChannelName());
            }
            if (window.OPERATIONS_ACCESS) {
                pusher.subscribe(operationsChannelName());
            }
            if (window.INTELLIGENCE_ACCESS) {
                pusher.subscribe(intelligenceChannelName());
            }
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
        monitoringChannel: monitoringChannelName,
        operationsChannel: operationsChannelName,
        intelligenceChannel: intelligenceChannelName,
    };

    initWhenReady();
})();
