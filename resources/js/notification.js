const metaContent = (name) => document.querySelector(`meta[name="${name}"]`)?.content || "";
const vapidKey = "BL1gaDmSbxQ7IfZwOqxJqjHXNqg2yuoy2p_lVot6wsjGPXWMD7q1UbPqBs3cOq9rgM8-F0Thk9vBbjFwwHmpB3E";

let tokenRequestInProgress = false;
let tokenSaved = false;
let foregroundListenerAttached = false;
let retryAfter = 0;

const canUseNotifications = () => (
    "Notification" in window
    && "serviceWorker" in navigator
    && window.isSecureContext
);

const attachForegroundListener = (client, registration, notificationIconUrl) => {
    if (foregroundListenerAttached) {
        return;
    }

    client.onMessage(client.messaging, (payload) => {
        if (Notification.permission !== "granted") {
            return;
        }

        const title = payload.notification?.title || payload.data?.title || "SBC Alumni Link";
        const options = {
            body: payload.notification?.body || payload.data?.body || "You have a new notification.",
            icon: notificationIconUrl,
        };

        // Mobile browsers require the service worker notification API.
        registration.showNotification(title, options).catch(() => {});
    });

    foregroundListenerAttached = true;
};

const saveFcmToken = async ({ shouldPrompt = false } = {}) => {
    const tokenUrl = metaContent("fcm-token-url");
    const csrfToken = metaContent("csrf-token");

    if (tokenSaved || tokenRequestInProgress || !tokenUrl || !csrfToken || !canUseNotifications()) {
        return;
    }

    if (navigator.onLine === false || Notification.permission === "denied") {
        return;
    }

    if ((Notification.permission === "default" && !shouldPrompt) || (!shouldPrompt && Date.now() < retryAfter)) {
        return;
    }

    tokenRequestInProgress = true;

    try {
        // Keep this call synchronous with the user gesture, before downloading the SDK.
        const permission = Notification.permission === "granted"
            ? "granted"
            : await Notification.requestPermission();

        if (permission !== "granted") {
            return;
        }

        const { getNotificationClient } = await import("./firebase");
        const client = await getNotificationClient();

        if (!client) {
            return;
        }

        const serviceWorkerUrl = metaContent("firebase-messaging-sw-url") || "/firebase-messaging-sw.js";
        const notificationIconUrl = metaContent("notification-icon-url") || "/icons/icon-192.png";
        const registration = await navigator.serviceWorker.register(serviceWorkerUrl);
        const token = await client.getToken(client.messaging, {
            vapidKey,
            serviceWorkerRegistration: registration,
        });

        if (!token) {
            return;
        }

        attachForegroundListener(client, registration, notificationIconUrl);

        const controller = new AbortController();
        const timeout = window.setTimeout(() => controller.abort(), 15000);

        try {
            const response = await fetch(tokenUrl, {
                method: "POST",
                credentials: "same-origin",
                signal: controller.signal,
                headers: {
                    "Content-Type": "application/json",
                    "Accept": "application/json",
                    "X-CSRF-TOKEN": csrfToken,
                },
                body: JSON.stringify({ token }),
            });

            if (!response.ok) {
                throw new Error(`FCM token save failed with HTTP ${response.status}`);
            }

            tokenSaved = true;
            retryAfter = 0;
        } finally {
            window.clearTimeout(timeout);
        }
    } finally {
        tokenRequestInProgress = false;
    }
};

const enableFcmNotifications = (options = {}) => saveFcmToken(options).catch(() => {
    // Push is optional: offline/storage failures must not interrupt the interface.
    retryAfter = Date.now() + 30000;
});

window.enableFcmNotifications = enableFcmNotifications;

const resumeNotifications = () => {
    if (canUseNotifications() && Notification.permission === "granted") {
        enableFcmNotifications();
    }
};

const initializeNotifications = () => {
    if ("requestIdleCallback" in window) {
        window.requestIdleCallback(resumeNotifications, { timeout: 3000 });
    } else {
        window.setTimeout(resumeNotifications, 1000);
    }
};

if (document.readyState === "complete") {
    initializeNotifications();
} else {
    window.addEventListener("load", initializeNotifications, { once: true });
}

document.addEventListener("pointerdown", () => {
    enableFcmNotifications({ shouldPrompt: true });
}, { once: true, passive: true });

document.addEventListener("keydown", () => {
    enableFcmNotifications({ shouldPrompt: true });
}, { once: true });

document.addEventListener("click", (event) => {
    if (event.target instanceof Element && event.target.closest("[data-enable-notifications]")) {
        enableFcmNotifications({ shouldPrompt: true });
    }
});

window.addEventListener("focus", resumeNotifications);
window.addEventListener("online", () => {
    retryAfter = 0;
    resumeNotifications();
});
