import test from "node:test";
import assert from "node:assert/strict";
import { readFileSync } from "node:fs";
import { runInNewContext } from "node:vm";

const source = readFileSync(new URL("../../public/js/interface.js", import.meta.url), "utf8");

class Element {
    constructor(attributes = {}, children = []) {
        this.attributes = new Map(Object.entries(attributes));
        this.childNodes = children;
    }
    setAttribute(name, value) { this.attributes.set(name, String(value)); }
    getAttribute(name) { return this.attributes.get(name) ?? null; }
    hasAttribute(name) { return this.attributes.has(name); }
    removeAttribute(name) { this.attributes.delete(name); }
    replaceChildren(...children) { this.childNodes = children; }
}
class Form extends Element {}
class Button extends Element {
    name = "decision";
    value = "approve";
    disabled = false;
}

function createPage() {
    const documentHandlers = new Map();
    const windowHandlers = new Map();
    const timers = new Map();
    let timerId = 0;

    runInNewContext(source, {
        document: {
            querySelectorAll: () => [],
            addEventListener: (name, handler) => documentHandlers.set(name, handler),
            createElement: () => new Element(),
            createTextNode: (text) => ({ textContent: text }),
        },
        window: {
            addEventListener: (name, handler) => windowHandlers.set(name, handler),
        },
        HTMLFormElement: Form,
        HTMLButtonElement: Button,
        setTimeout: (callback, delay) => {
            const id = ++timerId;
            timers.set(id, { callback, delay });
            return id;
        },
        clearTimeout: (id) => timers.delete(id),
    });

    return {
        submit(form, button, cancelled = false) {
            const event = {
                target: form,
                submitter: button,
                defaultPrevented: cancelled,
                stopped: false,
                preventDefault() { this.defaultPrevented = true; },
                stopImmediatePropagation() { this.stopped = true; },
            };
            documentHandlers.get("submit")(event);
            return event;
        },
        runTimers(delay) {
            for (const [id, timer] of [...timers]) {
                if (timer.delay === delay) {
                    timers.delete(id);
                    timer.callback();
                }
            }
        },
        pageshow: () => windowHandlers.get("pageshow")(),
        timerCount: () => timers.size,
    };
}

const postForm = (attrs = {}) => new Form({ method: "POST", ...attrs });
const submitButton = (attrs = {}) => new Button(attrs, [{ textContent: "Approve" }]);

test("native POST keeps named button values and immediately blocks duplicate submissions", () => {
    const page = createPage();
    const form = postForm();
    const button = submitButton();
    const children = button.childNodes;

    assert.equal(page.submit(form, button).defaultPrevented, false);
    assert.equal(button.childNodes, children, "feedback waits for the next task");
    assert.equal(page.submit(form, button).defaultPrevented, true);

    page.runTimers(0);

    assert.equal(form.getAttribute("aria-busy"), "true");
    assert.equal(button.getAttribute("aria-disabled"), "true");
    assert.equal(button.name, "decision");
    assert.equal(button.value, "approve");
    assert.equal(button.disabled, false, "successful controls must remain enabled");
    assert.equal(page.submit(form, button).defaultPrevented, true);
});

test("a later AJAX listener can cancel without shared feedback overwriting its state", () => {
    const page = createPage();
    const form = postForm();
    const button = submitButton();
    const event = page.submit(form, button);
    const ajaxContent = { textContent: "Sending with AJAX" };

    event.preventDefault();
    form.setAttribute("aria-busy", "true");
    button.replaceChildren(ajaxContent);
    page.runTimers(0);

    assert.equal(form.getAttribute("aria-busy"), "true");
    assert.equal(button.childNodes[0], ajaxContent);
    assert.equal(button.getAttribute("aria-disabled"), null);
    assert.equal(page.timerCount(), 0);
    assert.equal(page.submit(form, button).defaultPrevented, false, "cancelled form is released");
});

test("cancelled confirmation can resubmit before the feedback task runs", () => {
    const page = createPage();
    const form = postForm();
    const button = submitButton();
    const event = page.submit(form, button);

    event.preventDefault();
    assert.equal(page.submit(form, button).defaultPrevented, false);

    page.runTimers(0);
    assert.equal(form.getAttribute("aria-busy"), "true");
    assert.equal(page.timerCount(), 1, "only the current submission has a recovery timer");
});

test("already-cancelled AJAX submissions never reserve or mutate a form", () => {
    const page = createPage();
    const form = postForm();
    const button = submitButton();

    page.submit(form, button, true);
    page.runTimers(0);

    assert.equal(form.getAttribute("aria-busy"), null);
    assert.equal(page.timerCount(), 0);
    assert.equal(page.submit(form, button).defaultPrevented, false);
});

test("recovery restores original child nodes and ARIA state before allowing a retry", () => {
    const page = createPage();
    const form = postForm({ "aria-busy": "false" });
    const button = submitButton({ "aria-disabled": "false" });
    const originalNode = button.childNodes[0];

    page.submit(form, button);
    page.runTimers(0);
    page.runTimers(15000);

    assert.equal(button.childNodes[0], originalNode);
    assert.equal(button.getAttribute("aria-disabled"), "false");
    assert.equal(form.getAttribute("aria-busy"), "false");
    assert.equal(page.timerCount(), 0);
    assert.equal(page.submit(form, button).defaultPrevented, false);
});

test("back navigation releases pending forms and cancels queued feedback", () => {
    const page = createPage();
    const form = postForm();
    const button = submitButton();

    page.submit(form, button);
    page.pageshow();
    page.runTimers(0);

    assert.equal(form.getAttribute("aria-busy"), null);
    assert.equal(page.timerCount(), 0);
    assert.equal(page.submit(form, button).defaultPrevented, false);

    page.runTimers(0);
    page.pageshow();
    assert.equal(form.getAttribute("aria-busy"), null);
    assert.equal(button.getAttribute("aria-disabled"), null);
});

test("GET, external targets and opt-out forms keep native behavior, including submitter overrides", () => {
    const examples = [
        [new Form({ method: "get" }), submitButton()],
        [postForm({ target: "_blank" }), submitButton()],
        [postForm({ "data-no-submission-guard": "" }), submitButton()],
        [postForm(), submitButton({ formmethod: "get" })],
        [postForm(), submitButton({ formtarget: "_blank" })],
    ];

    for (const [form, button] of examples) {
        const page = createPage();
        assert.equal(page.submit(form, button).defaultPrevented, false);
        assert.equal(page.submit(form, button).defaultPrevented, false);
        assert.equal(page.timerCount(), 0);
    }
});
