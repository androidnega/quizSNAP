/**
 * QuizSnap service worker — push notifications + optional static asset cache (after user consent).
 */
var staticCacheName = 'quizsnap-static-v1';
var staticCacheEnabled = false;

var staticAssetPattern = /\.(js|css|svg|ico|woff2?|ttf|png|jpg|jpeg|webp)(\?|$)/i;

self.addEventListener('message', function (event) {
    if (!event.data) return;
    if (event.data.type === 'ENABLE_STATIC_CACHE') {
        staticCacheEnabled = true;
        event.waitUntil(
            caches.open(staticCacheName).then(function () {
                return self.clients.matchAll();
            }).then(function (clients) {
                clients.forEach(function (client) {
                    client.postMessage({ type: 'STATIC_CACHE_READY' });
                });
            })
        );
    }
    if (event.data.type === 'DISABLE_STATIC_CACHE') {
        staticCacheEnabled = false;
        event.waitUntil(caches.delete(staticCacheName));
    }
});

self.addEventListener('fetch', function (event) {
    if (!staticCacheEnabled || event.request.method !== 'GET') return;

    var url = new URL(event.request.url);
    if (url.origin !== self.location.origin) return;
    if (!staticAssetPattern.test(url.pathname)) return;
    if (url.pathname.indexOf('/sw.js') !== -1) return;

    event.respondWith(
        caches.open(staticCacheName).then(function (cache) {
            return cache.match(event.request).then(function (cached) {
                if (cached) return cached;
                return fetch(event.request).then(function (response) {
                    if (response && response.status === 200 && response.type === 'basic') {
                        cache.put(event.request, response.clone());
                    }
                    return response;
                });
            });
        })
    );
});

self.addEventListener('push', function (event) {
    if (!event.data) return;
    var data = {};
    try {
        data = event.data.json();
    } catch (e) {
        data = { title: 'QuizSnap', body: event.data.text() || 'Reminder' };
    }
    var title = data.title || 'Exam reminder';
    var body = data.body || 'You have an exam soon.';
    var options = {
        body: body,
        icon: '/favicon.ico',
        badge: '/favicon.ico',
        tag: 'exam-reminder',
        renotify: true,
    };
    event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', function (event) {
    event.notification.close();
    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (windowClients) {
            var url = '/dashboard/calendar';
            for (var i = 0; i < windowClients.length; i++) {
                if (windowClients[i].url.indexOf(url) !== -1 && 'focus' in windowClients[i]) {
                    return windowClients[i].focus();
                }
            }
            if (clients.openWindow) {
                return clients.openWindow('/dashboard');
            }
        })
    );
});
