(function () {
    'use strict';

    var STORAGE_KEY = 'quizsnap_cache_consent';
    var SW_URL = '/sw.js';

    function readConsent() {
        try {
            return localStorage.getItem(STORAGE_KEY);
        } catch (e) {
            return null;
        }
    }

    function writeConsent(value) {
        try {
            localStorage.setItem(STORAGE_KEY, value);
        } catch (e) {}
    }

    function enableStaticCache(registration) {
        if (!registration || !registration.active) return;
        registration.active.postMessage({ type: 'ENABLE_STATIC_CACHE' });
    }

    function registerServiceWorker() {
        if (!('serviceWorker' in navigator)) return Promise.resolve(null);
        return navigator.serviceWorker.register(SW_URL, { scope: '/' }).then(function (registration) {
            if (readConsent() === 'accepted') {
                if (registration.active) {
                    enableStaticCache(registration);
                } else {
                    registration.addEventListener('updatefound', function () {
                        var worker = registration.installing;
                        if (!worker) return;
                        worker.addEventListener('statechange', function () {
                            if (worker.state === 'activated') {
                                enableStaticCache(registration);
                            }
                        });
                    });
                }
            }
            return registration;
        }).catch(function () {
            return null;
        });
    }

    function hideBanner() {
        var banner = document.getElementById('quizsnap-cache-consent');
        if (banner) {
            banner.classList.add('hidden');
            banner.setAttribute('aria-hidden', 'true');
        }
    }

    function showBanner() {
        var banner = document.getElementById('quizsnap-cache-consent');
        if (!banner) return;
        banner.classList.remove('hidden');
        banner.setAttribute('aria-hidden', 'false');
    }

    function acceptCache() {
        writeConsent('accepted');
        hideBanner();
        registerServiceWorker().then(function (registration) {
            if (registration) enableStaticCache(registration);
        });
        document.dispatchEvent(new CustomEvent('quizsnap-cache-consent', { detail: { accepted: true } }));
    }

    function declineCache() {
        writeConsent('declined');
        hideBanner();
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.getRegistration().then(function (registration) {
                if (registration && registration.active) {
                    registration.active.postMessage({ type: 'DISABLE_STATIC_CACHE' });
                }
            });
        }
        document.dispatchEvent(new CustomEvent('quizsnap-cache-consent', { detail: { accepted: false } }));
    }

    window.QuizSnapCacheConsent = {
        isAccepted: function () { return readConsent() === 'accepted'; },
        isDeclined: function () { return readConsent() === 'declined'; },
        registerServiceWorker: registerServiceWorker,
        accept: acceptCache,
        decline: declineCache,
    };

    document.addEventListener('DOMContentLoaded', function () {
        var acceptBtn = document.getElementById('quizsnap-cache-accept');
        var declineBtn = document.getElementById('quizsnap-cache-decline');
        if (acceptBtn) acceptBtn.addEventListener('click', acceptCache);
        if (declineBtn) declineBtn.addEventListener('click', declineCache);

        var consent = readConsent();
        if (consent === 'accepted') {
            registerServiceWorker();
            return;
        }
        if (consent === 'declined') {
            return;
        }
        showBanner();
    });
})();
