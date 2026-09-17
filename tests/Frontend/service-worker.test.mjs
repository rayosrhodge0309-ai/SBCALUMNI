import test from "node:test";
import assert from "node:assert/strict";
import { readFileSync } from "node:fs";
import { runInNewContext } from "node:vm";

const scope = "https://example.test/alumni-link/public/";
const workers = ["firebase-messaging-sw.js", "service-worker.js", "sw.js"];

function createWorker(file, options = {}) {
    const handlers = new Map();
    const calls = { fetch: 0, cacheOpen: 0, put: [], deleted: [], precached: [], claimed: 0, skipped: 0 };
    const cache = {
        match: async () => {
            if (options.matchError) throw new Error("Storage unavailable");
            return options.cached;
        },
        put: async (request) => {
            if (options.putError) throw new Error("Quota exceeded");
            calls.put.push(request.url);
        },
        addAll: async (urls) => {
            calls.precached.push(...urls);
            if (options.installError) throw new Error("Offline");
        },
    };
    const self = {
        registration: { scope, showNotification: async () => {} },
        location: { origin: new URL(scope).origin },
        addEventListener: (name, handler) => handlers.set(name, handler),
        skipWaiting: async () => { calls.skipped++; },
        clients: {
            claim: async () => { calls.claimed++; },
            matchAll: async () => {
                throw new Error("Updating a worker must not reload an open page");
            },
        },
    };

    runInNewContext(readFileSync(new URL("../../public/" + file, import.meta.url), "utf8"), {
        self,
        clients: self.clients,
        URL,
        Response,
        console,
        importScripts: () => {},
        firebase: {
            initializeApp: () => {},
            messaging: () => ({ onBackgroundMessage: () => {} }),
        },
        caches: {
            open: async () => {
                calls.cacheOpen++;
                if (options.openError) throw new Error("Storage disabled");
                return cache;
            },
            keys: async () => options.keys || [],
            delete: async (key) => { calls.deleted.push(key); },
        },
        fetch: async () => {
            calls.fetch++;
            return options.response || new Response("body", {
                headers: { "Content-Type": "text/css" },
            });
        },
    });

    return {
        calls,
        async dispatch(name, request) {
            let response;
            const pending = [];
            handlers.get(name)({
                request,
                respondWith: (promise) => { response = promise; },
                waitUntil: (promise) => pending.push(promise),
            });
            const result = await response;
            await Promise.all(pending);
            return result;
        },
    };
}

const request = (path, overrides = {}) => ({
    url: new URL(path, scope).href,
    method: "GET",
    mode: "cors",
    cache: "default",
    ...overrides,
});

for (const file of workers) {
    test(file + ": private data and navigation bypass the cache", async () => {
        const worker = createWorker(file, { cached: new Response("stale private data") });
        const requests = [
            request("users/pending/notifications?after=3"),
            request("requests/notifications?after=9"),
            request("portal/requests/notifications?after=12"),
            request("portal/dashboard"),
            request("portal/login"),
            request("storage/avatars/user.png"),
            request("build/assets/app-unversioned.js"),
            request("https://cdn.example.test/build/assets/app-12345678.js"),
            request("/another-app/build/assets/app-12345678.js"),
            request("build/assets/app-12345678.js", { method: "POST" }),
            request("build/assets/app-12345678.js", { mode: "navigate" }),
            request("build/assets/app-12345678.js", { cache: "no-store" }),
        ];

        for (const item of requests) {
            assert.equal(await worker.dispatch("fetch", item), undefined, item.url);
        }

        assert.equal(worker.calls.cacheOpen, 0);
        assert.equal(worker.calls.fetch, 0);
    });

    test(file + ": hashed assets load from cache without another request", async () => {
        const worker = createWorker(file, { cached: new Response("cached stylesheet") });
        const response = await worker.dispatch("fetch", request("build/assets/app-Abc_1234.css"));

        assert.equal(await response.text(), "cached stylesheet");
        assert.equal(worker.calls.fetch, 0);
    });

    test(file + ": successful public assets are cached", async () => {
        const worker = createWorker(file);
        const asset = request("build/assets/app-12345678.css");
        const response = await worker.dispatch("fetch", asset);

        assert.equal(await response.text(), "body");
        assert.deepEqual(worker.calls.put, [asset.url]);
    });

    test(file + ": failed, private, and HTML/JSON responses are never cached", async () => {
        const responses = [
            new Response("error", { status: 500 }),
            new Response("private", { headers: { "Cache-Control": "private, max-age=0" } }),
            new Response("private", { headers: { "Cache-Control": "no-store" } }),
            new Response("{}", { headers: { "Content-Type": "application/json" } }),
            new Response("login page", { headers: { "Content-Type": "text/html" } }),
        ];

        for (const response of responses) {
            const worker = createWorker(file, { response });
            assert.equal(await worker.dispatch("fetch", request("build/assets/app-12345678.js")), response);
            assert.equal(worker.calls.put.length, 0);
        }
    });

    test(file + ": storage failures do not break asset loading", async () => {
        for (const failure of ["openError", "matchError", "putError"]) {
            const worker = createWorker(file, { [failure]: true });
            const response = await worker.dispatch("fetch", request("build/assets/app-12345678.css"));

            assert.equal(await response.text(), "body");
            assert.equal(worker.calls.fetch, 1);
        }
    });

    test(file + ": update removes only old alumni caches without reloading pages", async () => {
        const worker = createWorker(file, {
            keys: ["unrelated-app-v1", "sbc-alumni-cache-v1", "sbc-alumni-cache-v3",
                "sbc-alumni-legacy-cleanup-v2", "alumni-link-shell-v2",
                "sbc-alumni-assets-v4-" + encodeURIComponent(new URL(scope).pathname)],
        });

        await worker.dispatch("activate");

        assert.deepEqual(worker.calls.deleted, ["sbc-alumni-cache-v1", "sbc-alumni-cache-v3",
            "sbc-alumni-legacy-cleanup-v2", "alumni-link-shell-v2"]);
        assert.equal(worker.calls.claimed, 1);
    });

    test(file + ": precache is relative to the installation and updates tolerate offline storage", async () => {
        const worker = createWorker(file, { installError: true });

        await worker.dispatch("install");

        assert.equal(worker.calls.precached.length, 4);
        assert.ok(worker.calls.precached.every((url) => url.startsWith(scope)));
        assert.ok(worker.calls.precached.every((url) => !url.includes("portal/")));
        assert.equal(worker.calls.skipped, 1);
    });
}
