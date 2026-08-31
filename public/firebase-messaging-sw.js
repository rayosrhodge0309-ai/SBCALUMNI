importScripts("https://www.gstatic.com/firebasejs/12.18.0/firebase-app-compat.js");
importScripts("https://www.gstatic.com/firebasejs/12.18.0/firebase-messaging-compat.js");

firebase.initializeApp({
    apiKey: "AIzaSyABFaF6VN8jdsjQ1KnxqeSgWIzdDd-RnRE",
    authDomain: "sbc-alumni-link.firebaseapp.com",
    projectId: "sbc-alumni-link",
    storageBucket: "sbc-alumni-link.firebasestorage.app",
    messagingSenderId: "121228610827",
    appId: "1:121228610827:web:9be380dfeb111f5b22260e"
});

const messaging = firebase.messaging();
const appScope = self.registration.scope;
const appUrl = (path) => new URL(path, appScope).href;
const notificationIcon = appUrl("icons/icon-192.png");

const CACHE_VERSION = "sbc-alumni-cache-v2";
const PRECACHE_URLS = [
    "",
    "manifest.webmanifest",
    "images/favicon-32.png",
    "images/pwa-icon-192.png",
    "images/pwa-icon-512.png"
];

self.addEventListener("install", function(event) {
    event.waitUntil(
        caches.open(CACHE_VERSION)
            .then(function(cache) {
                return cache.addAll(PRECACHE_URLS.map(appUrl));
            })
            .catch(function() {
                return null;
            })
            .then(function() {
                return self.skipWaiting();
            })
    );
});

self.addEventListener("activate", function(event) {
    event.waitUntil(
        caches.keys()
            .then(function(keys) {
                return Promise.all(
                    keys.map(function(key) {
                        return key === CACHE_VERSION ? Promise.resolve() : caches.delete(key);
                    })
                );
            })
            .then(function() {
                return self.clients.claim();
            })
    );
});

self.addEventListener("fetch", function(event) {
    if (event.request.method !== "GET") {
        return;
    }

    const url = new URL(event.request.url);

    if (url.origin !== self.location.origin) {
        return;
    }

    event.respondWith(
        caches.match(event.request).then(function(response) {
            return response || fetch(event.request);
        })
    );
});

messaging.onBackgroundMessage(function(payload) {

    console.log("Background message:", payload);
    const title = payload.notification?.title || payload.data?.title || "SBC Alumni Link";
    const body = payload.notification?.body || payload.data?.body || "You have a new notification.";
    const url = payload.fcmOptions?.link || payload.data?.url || appScope;

    self.registration.showNotification(
        title,
        {
            body: body,
            icon: notificationIcon,
            badge: notificationIcon,
            data: {
                url: url
            }
        }
    );

});

self.addEventListener("notificationclick", function(event) {
    event.notification.close();

    const targetUrl = event.notification.data?.url || appScope;

    event.waitUntil(
        clients.matchAll({ type: "window", includeUncontrolled: true }).then(function(clientList) {
            for (const client of clientList) {
                if ("focus" in client && client.url === targetUrl) {
                    return client.focus();
                }
            }

            if (clients.openWindow) {
                return clients.openWindow(targetUrl);
            }

            return null;
        })
    );
});
