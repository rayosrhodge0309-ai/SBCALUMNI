import { messaging } from "./firebase";
import { getToken, onMessage } from "firebase/messaging";

const metaContent = (name) => document.querySelector(`meta[name="${name}"]`)?.content || "";
const vapidKey = "BL1gaDmSbxQ7IfZwOqxJqjHXNqg2yuoy2p_lVot6wsjGPXWMD7q1UbPqBs3cOq9rgM8-F0Thk9vBbjFwwHmpB3E";

let tokenRequestInProgress = false;
let tokenSaved = false;
let foregroundListenerAttached = false;

const attachForegroundListener = (notificationIconUrl) => {
    if (foregroundListenerAttached) {
        return;
    }

    foregroundListenerAttached = true;

    onMessage(messaging, (payload) => {
        console.log("Foreground Message:", payload);
        const title = payload.notification?.title || payload.data?.title || "SBC Alumni Link";
        const body = payload.notification?.body || payload.data?.body || "You have a new notification.";

        new Notification(title, {
            body: body,
            icon: notificationIconUrl,
        });
    });
};

const saveFcmToken = async ({ shouldPrompt = false } = {}) => {
    const tokenUrl = metaContent("fcm-token-url");
    const csrfToken = metaContent("csrf-token");
    const serviceWorkerUrl = metaContent("firebase-messaging-sw-url") || "/firebase-messaging-sw.js";
    const notificationIconUrl = metaContent("notification-icon-url") || "/icons/icon-192.png";

    if (tokenSaved || tokenRequestInProgress || !tokenUrl || !csrfToken) {
        return;
    }

    if (!("Notification" in window) || !("serviceWorker" in navigator)) {
        return;
    }

    if (!window.isSecureContext) {
        console.warn("FCM notifications need HTTPS or localhost to register a service worker.");
        return;
    }

    if (!window.isSecureContext) {
        console.warn("FCM notifications need HTTPS or localhost to register a service worker.");
        return;
    }

    if (Notification.permission === "denied") {
        console.log("Notification Permission Denied");
        return;
    }

    if (Notification.permission === "default" && !shouldPrompt) {
        return;
    }

    tokenRequestInProgress = true;

    const permission = Notification.permission === "granted"
        ? "granted"
        : await Notification.requestPermission();

    if (permission !== "granted") {
        console.log("Notification Permission Denied");
        tokenRequestInProgress = false;
        return;
    }

    console.log("Notification Permission Granted");

    const registration = await navigator.serviceWorker.register(
        serviceWorkerUrl
    );

    const token = await getToken(messaging, {
        vapidKey: vapidKey,
        serviceWorkerRegistration: registration,
    });

    console.log("FCM TOKEN:", token);

    if (!token) {
        tokenRequestInProgress = false;
        return;
    }

    const response = await fetch(tokenUrl, {
        method: "POST",
        credentials: "same-origin",
        headers: {
            "Content-Type": "application/json",
            "Accept": "application/json",
            "X-CSRF-TOKEN": csrfToken,
        },
        body: JSON.stringify({
            token: token,
        }),
    });

    if (!response.ok) {
        throw new Error(`FCM token save failed with HTTP ${response.status}`);
    }

    tokenSaved = true;
    tokenRequestInProgress = false;
    attachForegroundListener(notificationIconUrl);
};

const enableFcmNotifications = (options = {}) => {
    saveFcmToken(options).catch((error) => {
        tokenRequestInProgress = false;
        console.warn("Unable to enable FCM notifications.", error);
    });
};

window.enableFcmNotifications = enableFcmNotifications;

const currentNotificationPermission = "Notification" in window ? Notification.permission : "denied";

enableFcmNotifications({ shouldPrompt: currentNotificationPermission === "granted" });

document.addEventListener("pointerdown", () => {
    enableFcmNotifications({ shouldPrompt: true });
}, { once: true });

document.addEventListener("keydown", () => {
    enableFcmNotifications({ shouldPrompt: true });
}, { once: true });

document.addEventListener("click", (event) => {
    if (event.target.closest("[data-enable-notifications]")) {
        enableFcmNotifications({ shouldPrompt: true });
    }
});

window.addEventListener("focus", () => {
    enableFcmNotifications({ shouldPrompt: Notification.permission === "granted" });
});

window.addEventListener("online", () => {
    enableFcmNotifications({
        shouldPrompt: "Notification" in window && Notification.permission === "granted",
    });
});
