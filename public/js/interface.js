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
    // slide (photo or video) full-size in a pop-up, where every slide can be
    // browsed with arrows, thumbnails, tap, swipe or the keyboard.
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
                        <div class="campus-lightbox-stage">
                            <div class="campus-lightbox-media"></div>
                            <button type="button" class="campus-lightbox-nav campus-lightbox-prev" aria-label="Previous photo">
                                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M15 6l-6 6 6 6" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </button>
                            <button type="button" class="campus-lightbox-nav campus-lightbox-next" aria-label="Next photo">
                                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M9 6l6 6-6 6" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </button>
                        </div>
                        <div class="campus-lightbox-caption">
                            <span class="campus-lightbox-counter" aria-live="polite"></span>
                            <span class="campus-lightbox-hint">Tap photo or swipe to browse</span>
                        </div>
                        <div class="campus-lightbox-thumbs" role="group" aria-label="Choose a photo"></div>
                        <p class="campus-lightbox-detail mb-0"></p>
                    </div>
                </div>
            </div>`;
        document.body.appendChild(modalElement);

        const modal = window.bootstrap.Modal.getOrCreateInstance(modalElement);
        const stage = modalElement.querySelector('.campus-lightbox-stage');
        const mediaHolder = modalElement.querySelector('.campus-lightbox-media');
        const titleElement = modalElement.querySelector('.modal-title');
        const detailElement = modalElement.querySelector('.campus-lightbox-detail');
        const counterElement = modalElement.querySelector('.campus-lightbox-counter');
        const hintElement = modalElement.querySelector('.campus-lightbox-hint');
        const thumbsElement = modalElement.querySelector('.campus-lightbox-thumbs');
        const navButtons = [...modalElement.querySelectorAll('.campus-lightbox-nav')];
        const carousel = () => window.bootstrap.Carousel?.getInstance(gallery);

        let slides = [];
        let index = 0;

        // Read every slide from the carousel each time, so new slides are included.
        const readSlides = () => [...gallery.querySelectorAll('.carousel-item')].map((slide) => {
            const source = slide.querySelector('.campus-gallery-image');
            if (!source) return null;
            return {
                isVideo: source.tagName === 'VIDEO',
                src: source.currentSrc || source.getAttribute('src') || '',
                alt: source.getAttribute('alt') || '',
                title: slide.querySelector('.campus-gallery-title')?.textContent.trim() || '',
                detail: slide.querySelector('.campus-gallery-detail')?.textContent.trim() || '',
            };
        }).filter((slide) => slide && slide.src);

        const buildThumbs = () => {
            thumbsElement.replaceChildren(...slides.map((slide, slideIndex) => {
                const thumb = document.createElement('button');
                thumb.type = 'button';
                thumb.className = 'campus-lightbox-thumb';
                thumb.dataset.index = String(slideIndex);
                thumb.setAttribute('aria-label', `${slide.isVideo ? 'Video' : 'Photo'} ${slideIndex + 1} of ${slides.length}`);
                if (slide.isVideo) {
                    thumb.textContent = 'Video';
                } else {
                    const image = document.createElement('img');
                    image.src = slide.src;
                    image.alt = '';
                    image.loading = 'lazy';
                    thumb.append(image);
                }
                return thumb;
            }));
        };

        const show = (nextIndex) => {
            if (!slides.length) return;
            index = (nextIndex + slides.length) % slides.length; // wraps around
            const slide = slides[index];

            let media;
            if (slide.isVideo) {
                media = document.createElement('video');
                media.src = slide.src;
                media.controls = true;
                media.playsInline = true;
                media.autoplay = true;
            } else {
                media = document.createElement('img');
                media.src = slide.src;
                media.alt = slide.alt;
            }
            mediaHolder.replaceChildren(media);

            titleElement.textContent = slide.title || 'Campus gallery';
            detailElement.textContent = slide.detail;
            detailElement.hidden = !slide.detail;
            counterElement.textContent = `${index + 1} of ${slides.length} · ${slide.isVideo ? 'Video' : 'Photo'}`;
            thumbsElement.querySelectorAll('.campus-lightbox-thumb').forEach((thumb) => {
                const current = Number(thumb.dataset.index) === index;
                thumb.setAttribute('aria-current', String(current));
                if (current) thumb.scrollIntoView({ block: 'nearest', inline: 'nearest' });
            });
        };

        expandButton.addEventListener('click', () => {
            slides = readSlides();
            if (!slides.length) return;
            const items = [...gallery.querySelectorAll('.carousel-item')];
            const activeIndex = Math.max(0, items.findIndex((item) => item.classList.contains('active')));

            const browsable = slides.length > 1;
            navButtons.forEach((button) => { button.hidden = !browsable; });
            hintElement.hidden = !browsable;
            thumbsElement.hidden = !browsable;
            stage.classList.toggle('is-browsable', browsable);
            buildThumbs();
            show(Math.min(activeIndex, slides.length - 1));

            carousel()?.pause();
            modal.show();
        });

        // Arrows, thumbnails, and tapping the photo (not a video) for the next one.
        let suppressClickUntil = 0;
        modalElement.addEventListener('click', (event) => {
            if (slides.length < 2 || Date.now() < suppressClickUntil) return;
            const thumb = event.target.closest('.campus-lightbox-thumb');
            if (thumb) return show(Number(thumb.dataset.index));
            if (event.target.closest('.campus-lightbox-prev')) return show(index - 1);
            if (event.target.closest('.campus-lightbox-next')) return show(index + 1);
            if (event.target.closest('.campus-lightbox-media img')) show(index + 1);
        });

        modalElement.addEventListener('keydown', (event) => {
            if (slides.length < 2 || event.target.closest('video')
                || !['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;
            event.preventDefault();
            show(event.key === 'Home' ? 0 : event.key === 'End' ? slides.length - 1 : index + (event.key === 'ArrowRight' ? 1 : -1));
        });

        // Swipe left/right on touch screens.
        let touchStart = null;
        stage.addEventListener('touchstart', (event) => {
            touchStart = event.touches.length === 1 && !event.target.closest('video')
                ? { x: event.touches[0].clientX, y: event.touches[0].clientY } : null;
        }, { passive: true });
        stage.addEventListener('touchend', (event) => {
            const start = touchStart;
            touchStart = null;
            if (!start || slides.length < 2 || !event.changedTouches.length) return;
            const dx = event.changedTouches[0].clientX - start.x;
            const dy = event.changedTouches[0].clientY - start.y;
            if (Math.abs(dx) >= 45 && Math.abs(dx) > Math.abs(dy) * 1.5) {
                suppressClickUntil = Date.now() + 500; // don't also count the tap
                show(index + (dx < 0 ? 1 : -1));
            }
        }, { passive: true });

        modalElement.addEventListener('hidden.bs.modal', () => {
            mediaHolder.replaceChildren(); // stops any playing video
            // Continue the slideshow from the photo that was being viewed.
            if (slides.length > 1) carousel()?.to(index);
            carousel()?.cycle();
            expandButton.focus();
        });
    }
})();
