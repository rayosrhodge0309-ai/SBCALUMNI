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

    // Landing page campus gallery: an Expand button that opens the current
    // slide (photo or video) full-size in a pop-up.
    const gallery = document.querySelectorAll('.campus-gallery-carousel')[0];
    if (gallery && window.bootstrap?.Modal) {
        const expandButton = document.createElement('button');
        expandButton.type = 'button';
        expandButton.className = 'campus-gallery-expand';
        expandButton.setAttribute('aria-label', 'Expand photo');
        expandButton.setAttribute('aria-haspopup', 'dialog');
        expandButton.innerHTML = '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M15 3h6v6M9 21H3v-6M21 3l-7 7M3 21l7-7" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg><span>Expand</span>';
        gallery.appendChild(expandButton);

        const modalElement = document.createElement('div');
        modalElement.className = 'modal fade campus-lightbox';
        modalElement.id = 'campusGalleryLightbox';
        modalElement.tabIndex = -1;
        modalElement.setAttribute('aria-labelledby', 'campusGalleryLightboxTitle');
        modalElement.setAttribute('aria-hidden', 'true');
        modalElement.innerHTML = `
            <div class="modal-dialog modal-dialog-centered modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 class="modal-title h5" id="campusGalleryLightboxTitle">Campus gallery</h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="campus-lightbox-media"></div>
                        <p class="campus-lightbox-detail mb-0"></p>
                    </div>
                </div>
            </div>`;
        document.body.appendChild(modalElement);

        const modal = window.bootstrap.Modal.getOrCreateInstance(modalElement);
        const mediaHolder = modalElement.querySelector('.campus-lightbox-media');
        const titleElement = modalElement.querySelector('.modal-title');
        const detailElement = modalElement.querySelector('.campus-lightbox-detail');
        const carousel = () => window.bootstrap.Carousel?.getInstance(gallery);

        expandButton.addEventListener('click', () => {
            const slide = gallery.querySelector('.carousel-item.active');
            const source = slide?.querySelector('.campus-gallery-image');
            if (!source) return;

            let media;
            if (source.tagName === 'VIDEO') {
                media = document.createElement('video');
                media.src = source.currentSrc || source.src;
                media.controls = true;
                media.playsInline = true;
                media.autoplay = true;
            } else {
                media = document.createElement('img');
                media.src = source.currentSrc || source.src;
                media.alt = source.alt || '';
            }
            mediaHolder.replaceChildren(media);

            const title = slide.querySelector('.campus-gallery-title')?.textContent.trim();
            const detail = slide.querySelector('.campus-gallery-detail')?.textContent.trim();
            titleElement.textContent = title || 'Campus gallery';
            detailElement.textContent = detail || '';
            detailElement.hidden = !detail;

            carousel()?.pause();
            modal.show();
        });

        modalElement.addEventListener('hidden.bs.modal', () => {
            mediaHolder.replaceChildren(); // stops any playing video
            carousel()?.cycle();
            expandButton.focus();
        });
    }
})();
