/* Small shared enhancements; native forms and links continue to work without JS. */
(() => {
    'use strict';

    document.querySelectorAll('.sidebar a.active, .offcanvas a.active, .mobile-portal-dock a.active').forEach((link) => {
        link.setAttribute('aria-current', 'page');
    });
    document.querySelectorAll('.alert-danger').forEach((alert) => alert.setAttribute('role', 'alert'));
    document.querySelectorAll('.alert-success, .alert-warning').forEach((alert) => alert.setAttribute('role', 'status'));
    document.querySelectorAll('.is-invalid').forEach((input) => input.setAttribute('aria-invalid', 'true'));

    // Only horizontally overflowing tables need an extra keyboard focus stop.
    const tables = document.querySelectorAll('.table-responsive');
    const updateTable = (table) => {
        const overflows = table.scrollWidth > table.clientWidth + 1;
        if (overflows) {
            table.tabIndex = 0;
            table.setAttribute('role', 'region');
            table.setAttribute('aria-label', 'Scrollable table');
        } else {
            table.removeAttribute('tabindex');
            table.removeAttribute('role');
            table.removeAttribute('aria-label');
        }
    };
    if ('ResizeObserver' in window) {
        const observer = new ResizeObserver((entries) => entries.forEach(({ target }) => updateTable(target)));
        tables.forEach((table) => observer.observe(table));
    } else {
        tables.forEach(updateTable);
    }

    const pendingForms = new Map();
    const restoreAttribute = (element, name, value) => {
        if (value === null) element.removeAttribute(name);
        else element.setAttribute(name, value);
    };
    const release = (form) => {
        const state = pendingForms.get(form);
        if (!state) return;
        clearTimeout(state.feedbackTimer);
        clearTimeout(state.timer);
        if (state.feedbackShown) {
            restoreAttribute(form, 'aria-busy', state.ariaBusy);
            if (state.button) {
                restoreAttribute(state.button, 'aria-disabled', state.ariaDisabled);
                state.button.replaceChildren(...state.children);
            }
        }
        pendingForms.delete(form);
    };

    // Do not disable named submit buttons: their values must still reach Laravel.
    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || event.defaultPrevented
            || form.hasAttribute('data-no-submission-guard')) return;

        const submitter = event.submitter;
        const method = submitter?.getAttribute('formmethod') ?? form.getAttribute('method') ?? 'get';
        const target = submitter?.getAttribute('formtarget') ?? form.getAttribute('target');
        if (method.toLowerCase() !== 'post' || (target && target !== '_self')) return;

        // A later listener may have cancelled the previous event before resubmitting.
        if (pendingForms.get(form)?.event.defaultPrevented) release(form);

        if (pendingForms.has(form)) {
            event.preventDefault();
            event.stopImmediatePropagation();
            return;
        }
        // Reserve immediately so two submissions in the same task cannot slip through.
        const state = { event, feedbackShown: false, feedbackTimer: null, timer: null };
        pendingForms.set(form, state);
        state.feedbackTimer = setTimeout(() => {
            if (pendingForms.get(form) !== state) return;
            // A new task waits for all submit listeners, including later AJAX handlers.
            if (event.defaultPrevented) {
                release(form);
                return;
            }

            const button = submitter instanceof HTMLButtonElement ? submitter : null;
            state.button = button;
            state.children = button ? [...button.childNodes] : [];
            state.ariaBusy = form.getAttribute('aria-busy');
            state.ariaDisabled = button?.getAttribute('aria-disabled');
            state.feedbackShown = true;
            form.setAttribute('aria-busy', 'true');
            if (button) {
                button.setAttribute('aria-disabled', 'true');
                const spinner = document.createElement('span');
                spinner.className = 'submit-progress';
                spinner.setAttribute('aria-hidden', 'true');
                button.replaceChildren(spinner, document.createTextNode('Please wait…'));
            }
            state.timer = setTimeout(() => release(form), 15000);
        }, 0);
    });
    window.addEventListener('pageshow', () => [...pendingForms.keys()].forEach(release));

    // Admin desktop sidebar: close/open buttons, remembered per browser.
    const sidebarToggles = [...document.querySelectorAll('[data-admin-sidebar-toggle]')];
    if (sidebarToggles.length > 0) {
        const root = document.documentElement;
        const storageKey = 'alumniLink.adminSidebar';
        const closeButton = document.querySelector('.sidebar-collapse-button');
        const reopenButton = document.querySelector('.sidebar-reopen-button');
        const sidebar = document.getElementById('admin-sidebar');

        const apply = (collapsed) => {
            root.classList.toggle('admin-sidebar-collapsed', collapsed);
            sidebarToggles.forEach((button) => button.setAttribute('aria-expanded', String(!collapsed)));
            // Keep the hidden sidebar's links out of the keyboard tab order.
            if (sidebar) {
                if (collapsed) sidebar.setAttribute('inert', '');
                else sidebar.removeAttribute('inert');
            }
        };

        apply(root.classList.contains('admin-sidebar-collapsed'));

        sidebarToggles.forEach((button) => button.addEventListener('click', () => {
            const collapsed = !root.classList.contains('admin-sidebar-collapsed');
            apply(collapsed);
            try { localStorage.setItem(storageKey, collapsed ? 'collapsed' : 'open'); } catch (error) { /* storage blocked */ }
            (collapsed ? reopenButton : closeButton)?.focus();
        }));
    }
})();
