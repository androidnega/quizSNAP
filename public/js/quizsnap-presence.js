/**
 * Lightweight site presence ping — counts active visitors for super admin live dashboard.
 */
(function () {
    'use strict';

    var PING_INTERVAL_MS = 60000;
    var STORAGE_KEY = 'quizsnap_visitor_id';

    function visitorId() {
        try {
            var id = localStorage.getItem(STORAGE_KEY);
            if (id && id.length <= 128) return id;
            id = 'v_' + Date.now().toString(36) + '_' + Math.random().toString(36).slice(2, 12);
            localStorage.setItem(STORAGE_KEY, id);
            return id;
        } catch (e) {
            return 'v_' + Date.now();
        }
    }

    function csrf() {
        var el = document.querySelector('meta[name="csrf-token"]');
        return el ? el.getAttribute('content') : '';
    }

    function ping() {
        if (document.visibilityState === 'hidden') return;
        fetch('/presence/ping', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf(),
            },
            body: JSON.stringify({ visitor_id: visitorId() }),
        }).catch(function () {});
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', ping);
    } else {
        ping();
    }
    setInterval(ping, PING_INTERVAL_MS);
    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'visible') ping();
    });
})();
