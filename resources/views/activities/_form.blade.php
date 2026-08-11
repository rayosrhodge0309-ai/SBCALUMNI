@php
    $currentActivity = $activity ?? null;
    $composerUser = auth()->user();
    $composerName = $composerUser?->name ?? 'Records Admin';
    $composerInitials = $composerUser?->initials ?? 'RA';
    $previewHeadline = old('title', $currentActivity?->title);
    $previewDescription = old('description', $currentActivity?->description);
    $previewTheme = old('theme', $currentActivity?->theme);
    $previewLocation = old('location', $currentActivity?->location);
    $previewDate = old('activity_date', $currentActivity?->activity_date?->format('Y-m-d'));
    $isPublished = (bool) old('is_published', $currentActivity?->is_published ?? true);
    $existingMediaUrl = $currentActivity?->media_url;
    $existingMediaType = $currentActivity?->media_type;
@endphp

@once
    @push('styles')
        <style>
            .activity-composer-shell {
                display: grid;
                gap: 1.5rem;
            }

            @media (min-width: 1200px) {
                .activity-composer-shell {
                    grid-template-columns: minmax(0, 1.15fr) minmax(320px, 0.85fr);
                    align-items: start;
                }

                .activity-preview-column {
                    position: sticky;
                    top: 1rem;
                }
            }

            .activity-composer-card,
            .activity-preview-card {
                background: rgba(255, 255, 255, 0.96);
                border: 1px solid rgba(4, 0, 120, 0.12);
                border-radius: 1.4rem;
                box-shadow: 0 20px 42px rgba(4, 0, 120, 0.08);
            }

            .activity-composer-card {
                padding: 1.4rem;
                background: #aee8f3;
            }

            .composer-user-row {
                display: flex;
                align-items: center;
                gap: 0.9rem;
                margin-bottom: 1.2rem;
            }

            .composer-avatar {
                width: 3.3rem;
                height: 3.3rem;
                border-radius: 1rem;
                overflow: hidden;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                flex-shrink: 0;
                background: #07116f;
                color: #fff;
                font-weight: 700;
                letter-spacing: 0.08em;
                box-shadow: 0 12px 24px rgba(4, 0, 120, 0.16);
            }

            .composer-avatar img {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }

            .composer-privacy-badge,
            .preview-meta-chip {
                display: inline-flex;
                align-items: center;
                gap: 0.4rem;
                border-radius: 999px;
                padding: 0.35rem 0.75rem;
                font-size: 0.78rem;
                font-weight: 700;
            }

            .composer-privacy-badge {
                color: #274c77;
                background: #edf4ff;
                border: 1px solid rgba(39, 76, 119, 0.1);
            }

            .preview-meta-chip {
                color: var(--ink);
                background: rgba(246, 211, 29, 0.18);
                border: 1px solid rgba(4, 0, 120, 0.1);
            }

            .composer-surface,
            .composer-detail-card,
            .composer-upload-card {
                background: rgba(255, 255, 255, 0.92);
                border: 1px solid rgba(4, 0, 120, 0.1);
                border-radius: 1.2rem;
                padding: 1rem;
            }

            .composer-headline {
                border: 0;
                box-shadow: none !important;
                font-size: 1.15rem;
                font-weight: 700;
                padding-left: 0;
                padding-right: 0;
            }

            .composer-headline::placeholder,
            .composer-textarea::placeholder {
                color: #8a8f98;
            }

            .composer-textarea {
                border: 0;
                box-shadow: none !important;
                min-height: 180px;
                padding-left: 0;
                padding-right: 0;
                resize: none;
                font-size: 1.05rem;
                line-height: 1.7;
            }

            .composer-tools {
                display: flex;
                flex-wrap: wrap;
                gap: 0.75rem;
                padding-top: 1rem;
                border-top: 1px solid rgba(4, 0, 120, 0.08);
            }

            .composer-tool {
                display: inline-flex;
                align-items: center;
                gap: 0.6rem;
                padding: 0.65rem 0.95rem;
                border-radius: 999px;
                border: 1px solid rgba(4, 0, 120, 0.12);
                background: #fff;
                color: var(--ink);
                font-weight: 600;
                transition: all 0.2s ease;
            }

            .composer-tool:hover {
                background: rgba(246, 211, 29, 0.18);
                border-color: rgba(4, 0, 120, 0.22);
            }

            .composer-tool-dot {
                width: 0.78rem;
                height: 0.78rem;
                border-radius: 50%;
                flex-shrink: 0;
            }

            .composer-tool-dot-photo {
                background: #2ea44f;
            }

            .composer-tool-dot-theme {
                background: #e08a00;
            }

            .composer-tool-dot-date {
                background: #2563eb;
            }

            .composer-tool-dot-place {
                background: #db2777;
            }

            .composer-detail-grid {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 1rem;
            }

            .composer-detail-field label {
                display: block;
                font-size: 0.84rem;
                font-weight: 700;
                color: var(--muted);
                margin-bottom: 0.4rem;
                text-transform: uppercase;
                letter-spacing: 0.06em;
            }

            .composer-detail-field .form-control {
                border-radius: 1rem;
                min-height: 3rem;
            }

            .composer-detail-field-full {
                grid-column: 1 / -1;
            }

            .composer-upload-card {
                border-style: dashed;
                background: #fff;
            }

            .composer-upload-status {
                color: var(--muted);
                font-size: 0.92rem;
                margin-top: 0.5rem;
            }

            .composer-visibility-row {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                justify-content: space-between;
                gap: 1rem;
                padding: 0.95rem 1rem;
                border-radius: 1.2rem;
                background: rgba(246, 211, 29, 0.16);
                border: 1px solid rgba(4, 0, 120, 0.08);
            }

            .composer-visibility-copy {
                max-width: 32rem;
            }

            .activity-form-actions {
                display: flex;
                flex-wrap: wrap;
                gap: 0.8rem;
                align-items: center;
                margin-top: 1.4rem;
            }

            .activity-preview-card {
                overflow: hidden;
            }

            .activity-preview-header {
                padding: 1.25rem 1.25rem 0;
            }

            .activity-preview-body {
                padding: 0.9rem 1.25rem 1.25rem;
            }

            .activity-preview-description {
                white-space: pre-wrap;
                line-height: 1.7;
            }

            .activity-preview-stage {
                min-height: 240px;
                background: #fff;
                border-top: 1px solid rgba(15, 23, 42, 0.05);
                border-bottom: 1px solid rgba(15, 23, 42, 0.05);
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 1rem;
            }

            .activity-preview-stage.is-empty {
                padding: 1.6rem;
            }

            .activity-preview-placeholder {
                max-width: 18rem;
                text-align: center;
                color: var(--muted);
            }

            .activity-preview-actions {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                border-top: 1px solid rgba(15, 23, 42, 0.06);
            }

            .activity-preview-action {
                padding: 0.95rem 0.75rem;
                text-align: center;
                font-weight: 600;
                color: var(--muted);
            }

            .activity-preview-action + .activity-preview-action {
                border-left: 1px solid rgba(15, 23, 42, 0.05);
            }

            .activity-preview-summary {
                color: var(--muted);
            }

            @media (max-width: 767.98px) {
                .composer-detail-grid {
                    grid-template-columns: 1fr;
                }
            }
        </style>
    @endpush

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                document.querySelectorAll('[data-activity-composer]').forEach((form) => {
                    const composerShell = form.querySelector('.activity-composer-shell');
                    const titleInput = form.querySelector('#title');
                    const descriptionInput = form.querySelector('#description');
                    const themeInput = form.querySelector('#theme');
                    const dateInput = form.querySelector('#activity_date');
                    const locationInput = form.querySelector('#location');
                    const mediaInput = form.querySelector('#media');
                    const removeMediaInput = form.querySelector('#remove_media');
                    const publishInput = form.querySelector('#is_published');
                    const mediaStatus = form.querySelector('[data-media-status]');
                    const previewTitle = form.querySelector('[data-preview-title]');
                    const previewDescription = form.querySelector('[data-preview-description]');
                    const previewTheme = form.querySelector('[data-preview-theme]');
                    const previewDate = form.querySelector('[data-preview-date]');
                    const previewLocation = form.querySelector('[data-preview-location]');
                    const previewAudience = form.querySelector('[data-preview-audience]');
                    const previewStatus = form.querySelector('[data-preview-status]');
                    const previewStage = form.querySelector('[data-preview-media-stage]');
                    const previewPlaceholder = form.querySelector('[data-preview-placeholder]');
                    const previewImage = form.querySelector('[data-preview-image]');
                    const previewVideo = form.querySelector('[data-preview-video]');
                    const previewVideoSource = form.querySelector('[data-preview-video-source]');
                    const existingMediaUrl = composerShell?.dataset.existingMediaUrl || '';
                    const existingMediaType = composerShell?.dataset.existingMediaType || '';

                    let generatedMediaUrl = null;

                    const formatDate = (value) => {
                        if (!value) {
                            return '';
                        }

                        const [year, month, day] = value.split('-').map(Number);

                        if (!year || !month || !day) {
                            return '';
                        }

                        return new Date(year, month - 1, day).toLocaleDateString(undefined, {
                            month: 'long',
                            day: 'numeric',
                            year: 'numeric',
                        });
                    };

                    const setText = (node, value, fallback) => {
                        if (!node) {
                            return;
                        }

                        node.textContent = value || fallback;
                    };

                    const toggleChip = (node, value) => {
                        if (!node) {
                            return;
                        }

                        node.textContent = value;
                        node.classList.toggle('d-none', !value);
                    };

                    const resetPreviewMedia = () => {
                        previewImage?.classList.add('d-none');
                        previewVideo?.classList.add('d-none');
                        previewImage?.removeAttribute('src');
                        previewVideo?.pause();
                        previewVideoSource?.removeAttribute('src');
                        previewVideo?.load();
                        previewPlaceholder?.classList.remove('d-none');
                        previewStage?.classList.add('is-empty');
                    };

                    const showPreviewMedia = (url, type) => {
                        if (!url || !type) {
                            resetPreviewMedia();
                            return;
                        }

                        previewPlaceholder?.classList.add('d-none');
                        previewStage?.classList.remove('is-empty');

                        if (type === 'video') {
                            previewImage?.classList.add('d-none');
                            previewVideoSource?.setAttribute('src', url);
                            previewVideo?.classList.remove('d-none');
                            previewVideo?.load();
                            return;
                        }

                        previewVideo?.classList.add('d-none');
                        previewVideoSource?.removeAttribute('src');
                        previewVideo?.load();
                        previewImage?.setAttribute('src', url);
                        previewImage?.classList.remove('d-none');
                    };

                    const syncMedia = () => {
                        if (generatedMediaUrl) {
                            URL.revokeObjectURL(generatedMediaUrl);
                            generatedMediaUrl = null;
                        }

                        const removed = removeMediaInput?.checked;
                        const selectedFile = mediaInput?.files?.[0];

                        if (removed) {
                            mediaStatus.textContent = 'Current media will be removed when you save this post.';
                            resetPreviewMedia();
                            return;
                        }

                        if (selectedFile) {
                            generatedMediaUrl = URL.createObjectURL(selectedFile);
                            const mediaType = selectedFile.type.startsWith('video/') ? 'video' : 'image';
                            mediaStatus.textContent = `Selected: ${selectedFile.name}`;
                            showPreviewMedia(generatedMediaUrl, mediaType);
                            return;
                        }

                        if (existingMediaUrl && existingMediaType) {
                            mediaStatus.textContent = `Current ${existingMediaType} is loaded in the preview.`;
                            showPreviewMedia(existingMediaUrl, existingMediaType);
                            return;
                        }

                        mediaStatus.textContent = 'No photo or video selected yet.';
                        resetPreviewMedia();
                    };

                    const autoSize = () => {
                        if (!descriptionInput) {
                            return;
                        }

                        descriptionInput.style.height = 'auto';
                        descriptionInput.style.height = `${Math.max(descriptionInput.scrollHeight, 190)}px`;
                    };

                    const syncPreview = () => {
                        const title = titleInput?.value.trim() || '';
                        const description = descriptionInput?.value.trim() || '';
                        const theme = themeInput?.value.trim() || '';
                        const location = locationInput?.value.trim() || '';
                        const activityDate = formatDate(dateInput?.value || '');
                        const isPublished = publishInput?.checked ?? true;

                        setText(previewTitle, title, 'Your activity headline will appear here.');
                        setText(previewDescription, description, 'Share the details alumni should see about this activity.');
                        toggleChip(previewTheme, theme);
                        toggleChip(previewDate, activityDate);
                        toggleChip(previewLocation, location);
                        setText(previewAudience, isPublished ? 'Visible to alumni' : 'Draft only', 'Visible to alumni');
                        setText(previewStatus, isPublished ? 'Ready for the alumni feed and landing page.' : 'Saved as a draft until you publish it.', 'Ready for the alumni feed and landing page.');
                        autoSize();
                        syncMedia();
                    };

                    form.querySelectorAll('[data-trigger-file]').forEach((button) => {
                        button.addEventListener('click', () => mediaInput?.click());
                    });

                    form.querySelectorAll('[data-focus-target]').forEach((button) => {
                        button.addEventListener('click', () => {
                            const target = form.querySelector(button.dataset.focusTarget);
                            target?.focus();
                        });
                    });

                    [titleInput, descriptionInput, themeInput, dateInput, locationInput].forEach((input) => {
                        input?.addEventListener('input', syncPreview);
                    });

                    publishInput?.addEventListener('change', syncPreview);

                    mediaInput?.addEventListener('change', () => {
                        if (removeMediaInput) {
                            removeMediaInput.checked = false;
                        }

                        syncPreview();
                    });

                    removeMediaInput?.addEventListener('change', syncPreview);

                    syncPreview();
                });
            });
        </script>
    @endpush
@endonce

<div class="activity-composer-shell" data-existing-media-url="{{ $existingMediaUrl }}" data-existing-media-type="{{ $existingMediaType }}">
    <div class="activity-composer-card">
        <div class="composer-user-row">
            <div class="composer-avatar">
                @if ($composerUser?->profile_photo_url)
                    <img src="{{ $composerUser->profile_photo_url }}" alt="{{ $composerName }}">
                @else
                    <span>{{ $composerInitials }}</span>
                @endif
            </div>
            <div class="flex-grow-1">
                <div class="fw-semibold">{{ $composerName }}</div>
                <div class="small text-secondary">Posting to the alumni portal and landing page feed</div>
            </div>
            <div class="composer-privacy-badge">{{ $isPublished ? 'Visible to alumni' : 'Draft only' }}</div>
        </div>

        <div class="composer-surface mb-3">
            <label class="form-label small text-uppercase fw-semibold text-secondary mb-1" for="title">Post Headline</label>
            <input id="title" type="text" name="title" class="form-control composer-headline" value="{{ $previewHeadline }}" placeholder="Give this activity a short headline" required>

            <label class="form-label small text-uppercase fw-semibold text-secondary mb-1 mt-2" for="description">What's happening for alumni?</label>
            <textarea id="description" name="description" class="form-control composer-textarea" data-autosize rows="6" placeholder="Share the event details, who should join, what to bring, and why this matters." required>{{ $previewDescription }}</textarea>

            <div class="composer-tools">
                <button class="composer-tool" type="button" data-trigger-file>
                    <span class="composer-tool-dot composer-tool-dot-photo"></span>
                    Photo/Video
                </button>
                <button class="composer-tool" type="button" data-focus-target="#theme">
                    <span class="composer-tool-dot composer-tool-dot-theme"></span>
                    Theme
                </button>
                <button class="composer-tool" type="button" data-focus-target="#activity_date">
                    <span class="composer-tool-dot composer-tool-dot-date"></span>
                    Date
                </button>
                <button class="composer-tool" type="button" data-focus-target="#location">
                    <span class="composer-tool-dot composer-tool-dot-place"></span>
                    Location
                </button>
            </div>
        </div>

        <div class="composer-detail-card mb-3">
            <div class="small text-uppercase fw-semibold text-secondary mb-3">Post Details</div>
            <div class="composer-detail-grid">
                <div class="composer-detail-field">
                    <label for="theme">Theme</label>
                    <input id="theme" type="text" name="theme" class="form-control" value="{{ $previewTheme }}" placeholder="Service, Career, Reunion">
                </div>
                <div class="composer-detail-field">
                    <label for="activity_date">Activity Date</label>
                    <input id="activity_date" type="date" name="activity_date" class="form-control" value="{{ $previewDate }}">
                </div>
                <div class="composer-detail-field composer-detail-field-full">
                    <label for="location">Location</label>
                    <input id="location" type="text" name="location" class="form-control" value="{{ $previewLocation }}" placeholder="Campus venue, online room, or meetup place">
                </div>
            </div>
        </div>

        <div class="composer-upload-card mb-3">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <div class="fw-semibold">Add Photo or Video</div>
                    <div class="small text-secondary">Optional. Upload one image or short video up to 20 MB.</div>
                    <div class="composer-upload-status" data-media-status>
                        @if ($existingMediaUrl && $existingMediaType)
                            Current {{ $existingMediaType }} is loaded in the preview.
                        @else
                            No photo or video selected yet.
                        @endif
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <button class="composer-tool" type="button" data-trigger-file>
                        <span class="composer-tool-dot composer-tool-dot-photo"></span>
                        Choose Media
                    </button>
                    @if ($currentActivity?->media_path)
                        <div class="form-check ms-md-2">
                            <input class="form-check-input" type="checkbox" value="1" id="remove_media" name="remove_media" @checked(old('remove_media'))>
                            <label class="form-check-label" for="remove_media">Remove current media</label>
                        </div>
                    @endif
                </div>
            </div>
            <input id="media" type="file" name="media" class="visually-hidden" accept="image/*,video/mp4,video/webm,video/ogg,video/quicktime">
        </div>

        <div class="composer-visibility-row">
            <div class="composer-visibility-copy">
                <div class="fw-semibold">Publishing</div>
                <div class="small text-secondary">Turn this on when the activity post should appear for alumni on the landing page and in their dashboard feed.</div>
            </div>
            <div class="form-check form-switch m-0">
                <input class="form-check-input" type="checkbox" role="switch" value="1" id="is_published" name="is_published" @checked($isPublished)>
                <label class="form-check-label" for="is_published">Visible to alumni</label>
            </div>
        </div>

        <div class="activity-form-actions">
            <button class="btn btn-primary px-4" type="submit">{{ $submitLabel ?? 'Post Activity' }}</button>
            <a href="{{ route('activities.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </div>

    <div class="activity-preview-column">
        <div class="activity-preview-card">
            <div class="activity-preview-header">
                <div class="small text-uppercase fw-semibold text-secondary mb-3">Live Post Preview</div>
                <div class="d-flex align-items-center gap-3">
                    <div class="composer-avatar">
                        @if ($composerUser?->profile_photo_url)
                            <img src="{{ $composerUser->profile_photo_url }}" alt="{{ $composerName }}">
                        @else
                            <span>{{ $composerInitials }}</span>
                        @endif
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-semibold">{{ $composerName }}</div>
                        <div class="small text-secondary">
                            {{ now()->format('M d \a\t h:i A') }} | <span data-preview-audience>{{ $isPublished ? 'Visible to alumni' : 'Draft only' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="activity-preview-body">
                <div class="notice-label mb-2 {{ $previewTheme ? '' : 'd-none' }}" data-preview-theme>{{ $previewTheme }}</div>
                <h3 class="h5 mb-2" data-preview-title>{{ $previewHeadline ?: 'Your activity headline will appear here.' }}</h3>
                <p class="activity-preview-description text-secondary mb-0" data-preview-description>{{ $previewDescription ?: 'Share the details alumni should see about this activity.' }}</p>
            </div>

            <div class="activity-preview-stage {{ $existingMediaUrl && $existingMediaType ? '' : 'is-empty' }}" data-preview-media-stage>
                <div class="activity-preview-placeholder {{ $existingMediaUrl && $existingMediaType ? 'd-none' : '' }}" data-preview-placeholder>
                    Add a photo or video and it will appear here in the activity post preview.
                </div>
                <img
                    src="{{ $existingMediaType === 'image' ? $existingMediaUrl : '' }}"
                    alt="{{ $previewHeadline ?: 'Activity preview' }}"
                    class="activity-media {{ $existingMediaType === 'image' ? '' : 'd-none' }}"
                    data-preview-image
                >
                <video class="activity-media activity-media-video {{ $existingMediaType === 'video' ? '' : 'd-none' }}" controls preload="metadata" data-preview-video>
                    <source src="{{ $existingMediaType === 'video' ? $existingMediaUrl : '' }}" data-preview-video-source>
                    Your browser does not support the video tag.
                </video>
            </div>

            <div class="p-4 pt-3">
                <div class="d-flex flex-wrap gap-2">
                    <div class="preview-meta-chip {{ $previewDate ? '' : 'd-none' }}" data-preview-date>{{ $previewDate ? \Illuminate\Support\Carbon::parse($previewDate)->format('F d, Y') : '' }}</div>
                    <div class="preview-meta-chip {{ $previewLocation ? '' : 'd-none' }}" data-preview-location>{{ $previewLocation }}</div>
                </div>
                <div class="activity-preview-summary d-flex justify-content-between align-items-center small mt-3">
                    <span data-preview-status>{{ $isPublished ? 'Ready for the alumni feed and landing page.' : 'Saved as a draft until you publish it.' }}</span>
                    <span>Like | Comment | Share</span>
                </div>
            </div>

            <div class="activity-preview-actions">
                <div class="activity-preview-action">Like</div>
                <div class="activity-preview-action">Comment</div>
                <div class="activity-preview-action">Share</div>
            </div>
        </div>
    </div>
</div>
