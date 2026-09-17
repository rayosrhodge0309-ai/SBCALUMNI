# Interface refresh

The public site, admin workspace, and alumni dashboard share a school-blue palette, system fonts, lighter surfaces, consistent controls, and visible keyboard focus. Navigation includes a mobile menu trigger for both roles and a skip-to-content link. Dashboard metrics appear once, and tabs no longer make the content panel stick while scrolling. Wide tables can be scrolled with the keyboard; existing mobile card tables remain available.

The original header design has been restored: blue gradient school banners, large serif school titles, and the previous header navigation and controls. The other interface and performance improvements remain in place.

The original sidebar design is also restored, including its blue gradient, responsive widths, serif heading, text-only links, spacing, and account panel. The mobile sidebar uses the matching previous design.

## Assets and performance

- `public/css/shell.css` and `public/css/landing.css` contain styles previously repeated inside page HTML. `interface.css` provides shared controls and workspace styling; `dashboards.css` contains dashboard components. Blade URLs use file modification times for cache invalidation.
- Bootstrap 5.3.3 CSS and its bundle are served from `public/vendor/bootstrap/`, preserving the existing Bootstrap version and license headers. Menus and modals do not depend on the Bootstrap CDN.
- The Vite entry JavaScript decreased from approximately 90 KB to 41 KB before compression. Firebase is a separate approximately 50 KB chunk, loaded when notification permission and account context require it. This is a bundle-size comparison, not a measured page-load benchmark.
- Landing search preindexes its content and debounces typing. Secondary media defers loading; gallery playback pauses outside the viewport, in hidden tabs, and with reduced motion enabled.
- Admin navigation retrieves pending totals and latest IDs in two aggregate queries instead of four separate queries.

## Reliability

Notification polling skips hidden/offline pages, avoids overlapping requests, and aborts stalled requests. Browser notification failures do not interrupt in-app updates. Service workers cache only known public assets and versioned build files, bypass private pages, API responses, and uploads, tolerate cache failures, and no longer force homepage reloads on activation.

Native POST forms show temporary submission feedback and reject duplicate submissions while preserving named submit-button values. Cancelled confirmations and custom AJAX submissions retain control, and back navigation restores the form. This improves browser behavior; server-side idempotency is still separate.

## Verification

Run:

```text
npm run build
php vendor/bin/pest --compact
node --test tests/Frontend/*.test.mjs
php artisan view:cache
```

Validation passed: 68 Laravel tests (422 assertions), 34 frontend tests, the production build, and Blade compilation. The frontend suite covers notification loading/recovery, service-worker cache boundaries, and form submission behavior. Existing management-flow tests were aligned with the new empty-state wording and the currently supported Alumni ID request fields.

Seven pages were rendered with isolated in-memory test data and checked for inline JavaScript syntax and duplicate IDs. CSS parsing also passed. Browser visual checks and real-device performance measurements remain unverified because the hidden Chrome launch was aborted.

Deployment should include the new public CSS/JS/vendor files and the output of `npm run build`, then refresh compiled Blade views. Existing controllers, routes, account rules, and data were preserved.
