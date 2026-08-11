const CACHE_VERSION = 'alumni-link-shell-v1';
const PRECACHE_URLS = [
    '/',
    '/portal/login',
    '/portal/register',
    '/manifest.webmanifest',
    '/images/apple-touch-icon.png',
    '/images/favicon-32.png',
    '/images/pwa-icon-192.png',
    '/images/pwa-icon-512.png',
    '/images/pwa-icon.svg',
    '/images/sbc-logo.svg',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_VERSION).then((cache) => cache.addAll(PRECACHE_URLS)).then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) =>
            Promise.all(keys.map((key) => (key === CACHE_VERSION ? Promise.resolve() : caches.delete(key))))
        ).then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    if (event.request.method !== 'GET') {
        return;
    }

    const url = new URL(event.request.url);

    if (url.origin !== self.location.origin) {
        return;
    }

    if (event.request.mode === 'navigate') {
        event.respondWith(
            fetch(event.request)
                .then((response) => {
                    const clone = response.clone();

                    caches.open(CACHE_VERSION).then((cache) => cache.put(event.request, clone));

                    return response;
                })
                .catch(() =>
                    caches.match(event.request).then((cached) => {
                        if (cached) {
                            return cached;
                        }

                        return new Response('This page is unavailable right now. Please try again.', {
                            status: 503,
                            statusText: 'Service Unavailable',
                            headers: {
                                'Content-Type': 'text/plain; charset=utf-8',
                            },
                        });
                    })
                )
        );

        return;
    }

    event.respondWith(
        caches.match(event.request).then((cached) => {
            if (cached) {
                return cached;
            }

            return fetch(event.request).then((response) => {
                if (response.ok) {
                    caches.open(CACHE_VERSION).then((cache) => cache.put(event.request, response.clone()));
                }

                return response;
            });
        })
    );
});
