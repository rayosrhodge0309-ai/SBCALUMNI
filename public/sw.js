const appScope = self.registration.scope;
const appUrl = (path) => new URL(path, appScope).href;
const scopePath = new URL(appScope).pathname;
const CACHE_VERSION = "sbc-alumni-assets-v4-" + encodeURIComponent(scopePath);
const LEGACY_CACHE_PATTERN = /^(?:sbc-alumni-cache|sbc-alumni-legacy-cleanup|alumni-link-shell)-v\d+$/;
const PRECACHE_URLS = [
    "manifest.webmanifest",
    "images/favicon-32.png",
    "images/pwa-icon-192.png",
    "images/pwa-icon-512.png",
].map(appUrl);

function isPublicAsset(request) {
    const url = new URL(request.url);

    if (request.method !== "GET" || request.cache === "no-store" || url.origin !== self.location.origin || !url.pathname.startsWith(scopePath)) {
        return false;
    }

    const relativePath = url.pathname.slice(scopePath.length);
    const isVersionedBuild = /^build\/assets\/[^/]+-[A-Za-z0-9_-]{8}\.(?:css|js|woff2?|png|jpe?g|webp|avif|svg)$/.test(relativePath);

    return isVersionedBuild || PRECACHE_URLS.includes(url.href);
}

self.addEventListener("install", (event) => {
    event.waitUntil(
        caches.open(CACHE_VERSION)
            .then((cache) => cache.addAll(PRECACHE_URLS))
            .catch(() => {})
            .then(() => self.skipWaiting())
    );
});

self.addEventListener("activate", (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(keys
                .filter((key) => LEGACY_CACHE_PATTERN.test(key))
                .map((key) => caches.delete(key))))
            .catch(() => {})
            .then(() => self.clients.claim())
    );
});

self.addEventListener("fetch", (event) => {
    // Pages, API responses, downloads, and user uploads always use the network.
    // In particular, a cached JSON response must never hide a new notification.
    if (!isPublicAsset(event.request) || event.request.mode === "navigate") {
        return;
    }

    const cachePromise = caches.open(CACHE_VERSION).catch(() => null);

    event.respondWith(cachePromise.then(async (cache) => {
        const cached = await cache?.match(event.request).catch(() => null);

        if (cached) {
            return cached;
        }

        const response = await fetch(event.request);
        const cacheControl = response.headers.get("Cache-Control") || "";
        const contentType = response.headers.get("Content-Type") || "";

        if (cache && response.ok && !response.redirected && !/private|no-store/i.test(cacheControl)
            && !/text\/html|application\/(?:json|problem\+json)/i.test(contentType)) {
            const copy = response.clone();
            // Storage can be full or disabled; the successful response still loads.
            event.waitUntil(cache.put(event.request, copy).catch(() => {}));
        }

        return response;
    }));
});
