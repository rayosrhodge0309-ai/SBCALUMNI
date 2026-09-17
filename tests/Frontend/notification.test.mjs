import test from "node:test";
import assert from "node:assert/strict";
import { readFileSync } from "node:fs";
import { runInNewContext } from "node:vm";

const source = readFileSync(new URL("../../resources/js/notification.js", import.meta.url), "utf8");

function createPage(options = {}) {
    const handlers = { window: new Map(), document: new Map() };
    const calls = { imports: 0, permission: 0, register: 0, fetch: 0, listener: 0, idle: [], shown: [] };
    let permission = options.permission ?? "default";
    let foregroundListener;
    let failRequest = Boolean(options.fetchError);
    const registration = {
        showNotification: async (...args) => { calls.shown.push(args); },
    };
    const notification = {
        get permission() { return permission; },
        requestPermission: async () => {
            calls.permission++;
            if (options.permissionError) throw new Error("Permission unavailable");
            permission = "granted";
            return permission;
        },
    };
    const window = {
        isSecureContext: options.secure !== false,
        addEventListener: (name, handler) => handlers.window.set(name, handler),
        setTimeout: () => 1,
        clearTimeout: () => {},
        requestIdleCallback: (handler) => calls.idle.push(handler),
    };
    if (!options.unsupported) window.Notification = notification;

    const context = {
        window,
        document: {
            readyState: "complete",
            addEventListener: (name, handler) => handlers.document.set(name, handler),
            querySelector: (selector) => {
                if (selector.includes("fcm-token-url")) return options.guest ? null : { content: "/token" };
                if (selector.includes("csrf-token")) return { content: "csrf" };
                return null;
            },
        },
        navigator: {
            onLine: options.online !== false,
            serviceWorker: { register: async () => { calls.register++; return registration; } },
        },
        Element: class {},
        AbortController,
        Date,
        fetch: async () => {
            calls.fetch++;
            if (failRequest) throw new Error("Offline");
            return { ok: true };
        },
        loadFirebase: async () => {
            calls.imports++;
            return { getNotificationClient: async () => ({
                messaging: {},
                getToken: async () => "token",
                onMessage: (messaging, listener) => {
                    calls.listener++;
                    foregroundListener = listener;
                },
            }) };
        },
    };
    if (!options.unsupported) context.Notification = notification;

    // Replace only the module-loading boundary; execute the real event and retry logic.
    runInNewContext(source.replace('import("./firebase")', "loadFirebase()"), context);

    return {
        calls,
        window,
        emit: (target, name) => handlers[target].get(name)?.({ target: {} }),
        sendMessage: (payload) => foregroundListener?.(payload),
        restoreNetwork: () => { failRequest = false; },
    };
}

const settle = () => new Promise((resolve) => setImmediate(resolve));

test("SDK stays unloaded before permission and on guest pages", async () => {
    for (const options of [{}, { guest: true, permission: "granted" }]) {
        const page = createPage(options);
        for (const idle of page.calls.idle) idle();
        await settle();

        assert.equal(page.calls.imports, 0);
        assert.equal(page.calls.permission, 0);
    }
});

test("unsupported, insecure, denied and offline environments remain usable", async () => {
    for (const options of [
        { unsupported: true }, { secure: false }, { permission: "denied" }, { online: false },
    ]) {
        const page = createPage(options);
        page.emit("window", "focus");
        page.emit("window", "online");
        await page.window.enableFcmNotifications({ shouldPrompt: true });

        assert.equal(page.calls.imports, 0);
        assert.equal(page.calls.permission, 0);
    }
});

test("returning users load messaging only during idle time", async () => {
    const page = createPage({ permission: "granted" });

    assert.equal(page.calls.imports, 0);
    page.calls.idle[0]();
    await settle();

    assert.equal(page.calls.imports, 1);
    assert.equal(page.calls.fetch, 1);
});

test("repeated interaction prompts and saves only once", async () => {
    const page = createPage();

    await Promise.all([
        page.window.enableFcmNotifications({ shouldPrompt: true }),
        page.window.enableFcmNotifications({ shouldPrompt: true }),
    ]);
    await page.window.enableFcmNotifications({ shouldPrompt: true });

    assert.equal(page.calls.permission, 1);
    assert.equal(page.calls.register, 1);
    assert.equal(page.calls.fetch, 1);
    assert.equal(page.calls.listener, 1);
});

test("failed requests back off and reconnect retries without duplicate listeners", async () => {
    const page = createPage({ permission: "granted", fetchError: true });

    await page.window.enableFcmNotifications();
    page.emit("window", "focus");
    await settle();
    assert.equal(page.calls.fetch, 1);

    page.restoreNetwork();
    page.emit("window", "online");
    await settle();

    assert.equal(page.calls.fetch, 2);
    assert.equal(page.calls.listener, 1);
    page.sendMessage({ notification: { title: "Approved", body: "Request ready" } });
    assert.equal(page.calls.shown[0][0], "Approved");
});

test("permission API failures are contained and allow another attempt", async () => {
    const page = createPage({ permissionError: true });

    await page.window.enableFcmNotifications({ shouldPrompt: true });
    await page.window.enableFcmNotifications({ shouldPrompt: true });

    assert.equal(page.calls.permission, 2);
    assert.equal(page.calls.imports, 0);
});
