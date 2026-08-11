@extends('layouts.app')

@section('title', 'School Administration')
@section('subtitle', 'Manage the campus slider plus the leadership profile media displayed on the landing page.')

@section('content')
    <div class="page-card p-4">
        <form method="POST" action="{{ route('admin.settings.landing-video.update') }}" enctype="multipart/form-data" class="row g-4">
            @csrf
            @method('PUT')

            <div class="col-12">
                <label for="photo_files" class="form-label">Upload Photos or Videos (Multiple)</label>
                <input id="photo_files" type="file" name="photo_files[]" class="form-control" accept="image/jpeg,image/png,image/webp,video/mp4,video/webm,video/ogg,video/quicktime,video/x-msvideo,video/x-matroska" multiple>
                <div class="form-text">Accepted: JPG, PNG, WEBP, MP4, WEBM, OGG, MOV, AVI, MKV. Max size per file: 50MB. You can select multiple files at once.</div>
            </div>

            <div class="col-12">
                <div id="new-slide-fields" class="row g-3"></div>
            </div>

            <div class="col-12 d-flex flex-wrap gap-2">
                <button type="submit" class="btn btn-primary">Save Landing Slider</button>
                <button
                    type="submit"
                    name="remove_photos"
                    value="1"
                    class="btn btn-outline-danger"
                    onclick="return confirm('Delete all current landing media?');">
                    Delete Landing Slider
                </button>
                <a href="{{ route('home', ['preview' => 1]) }}" target="_blank" rel="noopener" class="btn btn-outline-secondary">Preview Landing Page</a>
            </div>

            @if (! empty($photoGallery))
                <div class="col-12">
                    <hr class="my-0">
                    <h3 class="h6 mt-2 mb-3">Current Uploaded Slides</h3>
                    <div class="row g-3">
                        @foreach ($photoGallery as $slide)
                            <div class="col-lg-6">
                                <div class="landing-slide-editor h-100">
                                    <div class="form-check mb-3">
                                        <input
                                            class="form-check-input"
                                            type="checkbox"
                                            value="{{ $slide['index'] }}"
                                            id="remove_slide_index_{{ $slide['index'] }}"
                                            name="remove_slide_indexes[]">
                                        <label class="form-check-label" for="remove_slide_index_{{ $slide['index'] }}">
                                            Remove this slide
                                        </label>
                                    </div>

                                    @if (($slide['type'] ?? 'photo') === 'video')
                                        <video src="{{ $slide['url'] }}" controls preload="metadata" playsinline class="landing-slide-thumb landing-slide-video mb-3"></video>
                                    @else
                                        <img src="{{ $slide['url'] }}" alt="{{ $slide['title'] ?: 'Landing slide' }}" class="landing-slide-thumb mb-3">
                                    @endif

                                    <div class="row g-3">
                                        <div class="col-12">
                                            <label for="existing_slide_title_{{ $slide['index'] }}" class="form-label">Slide Title</label>
                                            <input
                                                id="existing_slide_title_{{ $slide['index'] }}"
                                                type="text"
                                                name="existing_slide_titles[{{ $slide['index'] }}]"
                                                class="form-control"
                                                maxlength="120"
                                                value="{{ old('existing_slide_titles.'.$slide['index'], $slide['title']) }}"
                                                placeholder="Example: Bridgetine Campus Welcome">
                                        </div>
                                        <div class="col-12">
                                            <label for="existing_slide_detail_{{ $slide['index'] }}" class="form-label">Slide Detail</label>
                                            <textarea
                                                id="existing_slide_detail_{{ $slide['index'] }}"
                                                name="existing_slide_details[{{ $slide['index'] }}]"
                                                class="form-control"
                                                rows="3"
                                                maxlength="280"
                                                placeholder="Short detail about the photo">{{ old('existing_slide_details.'.$slide['index'], $slide['detail']) }}</textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </form>
    </div>

    <div class="page-card p-4 mt-4">
        <form method="POST" action="{{ route('admin.settings.landing-profiles.update') }}" enctype="multipart/form-data" class="row g-4">
            @csrf
            @method('PUT')

            <div class="col-12">
                <div class="d-flex flex-column flex-lg-row justify-content-between gap-2 align-items-lg-center">
                    <div>
                        <h3 class="h5 mb-1">Leadership Profiles</h3>
                        <div class="text-secondary small">Only admins can update these public profile photos, names, and details.</div>
                    </div>
                    <a href="{{ route('home', ['preview' => 1]) }}" target="_blank" rel="noopener" class="btn btn-outline-secondary">Preview Landing Page</a>
                </div>
            </div>

            <div class="col-12">
                <hr class="my-0">
            </div>

            <div class="col-12">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
                    <div>
                        <h4 class="h6 mb-1">Board of Trustees</h4>
                        <div class="text-secondary small">Upload a portrait for each board member and keep their published title current.</div>
                    </div>
                </div>

                <div class="row g-3">
                    @foreach ($boardMembers as $member)
                        <div class="col-md-6 col-xl-4">
                            <div class="landing-profile-editor h-100">
                                <div class="landing-profile-photo mb-3">
                                    @if (! empty($member['photo_path']))
                                        <img src="{{ $member['photo_url'] }}" alt="{{ $member['name'] }}">
                                    @else
                                        <div class="landing-profile-placeholder" aria-hidden="true">
                                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true" focusable="false">
                                                <circle cx="12" cy="8" r="4" fill="currentColor"></circle>
                                                <path d="M4 21v-1c0-4.418 3.582-8 8-8s8 3.582 8 8v1" fill="currentColor"></path>
                                            </svg>
                                        </div>
                                    @endif
                                </div>

                                <div class="row g-3">
                                    <div class="col-12">
                                        <label for="board_member_name_{{ $member['key'] }}" class="form-label">Name</label>
                                        <input
                                            id="board_member_name_{{ $member['key'] }}"
                                            type="text"
                                            name="board_members[{{ $member['key'] }}][name]"
                                            class="form-control"
                                            maxlength="255"
                                            value="{{ old('board_members.'.$member['key'].'.name', $member['name']) }}"
                                            required>
                                    </div>
                                    <div class="col-12">
                                        <label for="board_member_role_{{ $member['key'] }}" class="form-label">Role</label>
                                        <input
                                            id="board_member_role_{{ $member['key'] }}"
                                            type="text"
                                            name="board_members[{{ $member['key'] }}][role]"
                                            class="form-control"
                                            maxlength="255"
                                            value="{{ old('board_members.'.$member['key'].'.role', $member['role']) }}"
                                            required>
                                    </div>
                                    <div class="col-12">
                                        <label for="board_member_photo_{{ $member['key'] }}" class="form-label">Profile Picture</label>
                                        <input
                                            id="board_member_photo_{{ $member['key'] }}"
                                            type="file"
                                            name="board_member_photos[{{ $member['key'] }}]"
                                            class="form-control"
                                            accept="image/jpeg,image/png,image/webp">
                                    </div>
                                    <div class="col-12">
                                        <div class="form-check">
                                            <input
                                                class="form-check-input"
                                                type="checkbox"
                                                value="1"
                                                id="remove_board_member_photo_{{ $member['key'] }}"
                                                name="remove_board_member_photos[{{ $member['key'] }}]">
                                            <label class="form-check-label" for="remove_board_member_photo_{{ $member['key'] }}">
                                                Remove current photo
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="col-12">
                <hr class="my-0">
            </div>

            <div class="col-12">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
                    <div>
                        <h4 class="h6 mb-1">Alumni Office Team</h4>
                        <div class="text-secondary small">Edit the public contact information and upload staff profile pictures.</div>
                    </div>
                </div>

                <div class="row g-3">
                    @foreach ($alumniOfficeTeam as $member)
                        <div class="col-md-6">
                            <div class="landing-profile-editor h-100">
                                <div class="landing-profile-photo mb-3">
                                    @if (! empty($member['photo_path']))
                                        <img src="{{ $member['photo_url'] }}" alt="{{ $member['name'] }}">
                                    @else
                                        <div class="landing-profile-placeholder" aria-hidden="true">
                                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true" focusable="false">
                                                <circle cx="12" cy="8" r="4" fill="currentColor"></circle>
                                                <path d="M4 21v-1c0-4.418 3.582-8 8-8s8 3.582 8 8v1" fill="currentColor"></path>
                                            </svg>
                                        </div>
                                    @endif
                                </div>

                                <div class="row g-3">
                                    <div class="col-12">
                                        <label for="alumni_office_name_{{ $member['key'] }}" class="form-label">Name</label>
                                        <input
                                            id="alumni_office_name_{{ $member['key'] }}"
                                            type="text"
                                            name="alumni_office_team[{{ $member['key'] }}][name]"
                                            class="form-control"
                                            maxlength="255"
                                            value="{{ old('alumni_office_team.'.$member['key'].'.name', $member['name']) }}"
                                            required>
                                    </div>
                                    <div class="col-12">
                                        <label for="alumni_office_role_{{ $member['key'] }}" class="form-label">Role</label>
                                        <input
                                            id="alumni_office_role_{{ $member['key'] }}"
                                            type="text"
                                            name="alumni_office_team[{{ $member['key'] }}][role]"
                                            class="form-control"
                                            maxlength="255"
                                            value="{{ old('alumni_office_team.'.$member['key'].'.role', $member['role']) }}"
                                            required>
                                    </div>
                                    <div class="col-12">
                                        <label for="alumni_office_details_{{ $member['key'] }}" class="form-label">Details</label>
                                        <textarea
                                            id="alumni_office_details_{{ $member['key'] }}"
                                            name="alumni_office_team[{{ $member['key'] }}][details]"
                                            class="form-control"
                                            rows="3"
                                            maxlength="280"
                                            placeholder="Short description for the landing page">{{ old('alumni_office_team.'.$member['key'].'.details', $member['details'] ?? '') }}</textarea>
                                    </div>
                                    <div class="col-12">
                                        <label for="alumni_office_photo_{{ $member['key'] }}" class="form-label">Profile Picture</label>
                                        <input
                                            id="alumni_office_photo_{{ $member['key'] }}"
                                            type="file"
                                            name="alumni_office_team_photos[{{ $member['key'] }}]"
                                            class="form-control"
                                            accept="image/jpeg,image/png,image/webp">
                                    </div>
                                    <div class="col-12">
                                        <div class="form-check">
                                            <input
                                                class="form-check-input"
                                                type="checkbox"
                                                value="1"
                                                id="remove_alumni_office_photo_{{ $member['key'] }}"
                                                name="remove_alumni_office_team_photos[{{ $member['key'] }}]">
                                            <label class="form-check-label" for="remove_alumni_office_photo_{{ $member['key'] }}">
                                                Remove current photo
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="col-12 d-flex flex-wrap gap-2">
                <button type="submit" class="btn btn-primary">Save Leadership Profiles</button>
                <a href="{{ route('home', ['preview' => 1]) }}" target="_blank" rel="noopener" class="btn btn-outline-secondary">Preview Landing Page</a>
            </div>
        </form>
    </div>

    <div class="row g-3 mt-1">
        <div class="col-12">
            <div class="page-card p-4 h-100">
                <div class="d-flex flex-column flex-md-row justify-content-between gap-2 mb-3">
                    <div>
                        <h3 class="h6 mb-1">Landing Slider Preview</h3>
                        <div class="text-secondary small">Automatic scrolling with fade transition and caption overlay.</div>
                    </div>
                </div>

                @if (! empty($photoGallery))
                    <div id="adminLandingPhotoCarousel" class="carousel slide carousel-fade" data-bs-ride="carousel" data-bs-interval="4200">
                        <div class="carousel-indicators mb-0">
                            @foreach ($photoGallery as $slide)
                                <button
                                    type="button"
                                    data-bs-target="#adminLandingPhotoCarousel"
                                    data-bs-slide-to="{{ $loop->index }}"
                                    class="{{ $loop->first ? 'active' : '' }}"
                                    aria-current="{{ $loop->first ? 'true' : 'false' }}"
                                    aria-label="Slide {{ $loop->iteration }}"></button>
                            @endforeach
                        </div>
                        <div class="carousel-inner rounded overflow-hidden admin-photo-preview-shell">
                            @foreach ($photoGallery as $slide)
                                <div class="carousel-item {{ $loop->first ? 'active' : '' }}">
                                    @if (($slide['type'] ?? 'photo') === 'video')
                                        <video src="{{ $slide['url'] }}" class="admin-photo-preview-image admin-photo-preview-video" controls preload="metadata" playsinline></video>
                                    @else
                                        <img src="{{ $slide['url'] }}" class="admin-photo-preview-image" alt="{{ $slide['title'] ?: 'Landing media slide' }}">
                                    @endif
                                    <div class="admin-photo-preview-caption">
                                        <div class="admin-photo-preview-kicker">{{ $schoolAd['eyebrow'] ?? 'Campus Story' }}</div>
                                        <h4>{{ $slide['title'] ?: ($schoolAd['title'] ?? 'St. Bridget College Batangas') }}</h4>
                                        <p class="mb-0">{{ $slide['detail'] ?: ($schoolAd['summary'] ?? 'Upload campus media with captions to highlight the school.') }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        @if (count($photoGallery) > 1)
                            <button class="carousel-control-prev" type="button" data-bs-target="#adminLandingPhotoCarousel" data-bs-slide="prev">
                                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Previous</span>
                            </button>
                            <button class="carousel-control-next" type="button" data-bs-target="#adminLandingPhotoCarousel" data-bs-slide="next">
                                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Next</span>
                            </button>
                        @endif
                    </div>
                @else
                    <div class="text-secondary">No landing media configured yet.</div>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .landing-slide-editor {
            border: 1px solid rgba(4, 0, 120, 0.14);
            border-radius: 1.15rem;
            background: rgba(255, 255, 255, 0.92);
            box-shadow: 0 14px 24px rgba(4, 0, 120, 0.08);
            padding: 1rem;
        }

        .landing-slide-thumb {
            width: 100%;
            aspect-ratio: 16 / 10;
            object-fit: cover;
            border-radius: 1rem;
            border: 1px solid rgba(4, 0, 120, 0.14);
        }

        .landing-slide-video {
            background: #02083f;
        }

        .new-slide-card {
            border: 1px dashed rgba(4, 0, 120, 0.28);
            border-radius: 1.15rem;
            background: #aee8f3;
            padding: 1rem;
        }

        .new-slide-thumb {
            width: 100%;
            aspect-ratio: 16 / 10;
            object-fit: cover;
            border-radius: 1rem;
            border: 1px solid rgba(4, 0, 120, 0.14);
            margin-bottom: 1rem;
        }

        .landing-profile-editor {
            border: 1px solid rgba(4, 0, 120, 0.14);
            border-radius: 1.15rem;
            background: rgba(255, 255, 255, 0.94);
            box-shadow: 0 14px 24px rgba(4, 0, 120, 0.08);
            padding: 1rem;
        }

        .landing-profile-photo {
            width: 100%;
            aspect-ratio: 1 / 1;
            border-radius: 1rem;
            overflow: hidden;
            border: 1px solid rgba(4, 0, 120, 0.14);
            background: #aee8f3;
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.3);
        }

        .landing-profile-photo img {
            width: 100%;
            height: 100%;
            display: block;
            object-fit: cover;
        }

        .landing-profile-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #0b45b8;
            background: #fff;
        }

        .landing-profile-placeholder svg {
            width: 42%;
            height: 42%;
            min-width: 2.2rem;
            min-height: 2.2rem;
        }

        /* Make Alumni Office profile editors use a small circular avatar like leadership */
        .row > .col-md-6 .landing-profile-editor .landing-profile-photo {
            width: 96px;
            height: 96px;
            aspect-ratio: auto;
            border-radius: 50%;
            overflow: hidden;
            border: 1px solid rgba(4, 0, 120, 0.08);
            background: transparent;
            box-shadow: none;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .row > .col-md-6 .landing-profile-editor .landing-profile-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            border-radius: 50%;
        }

        .row > .col-md-6 .landing-profile-editor .landing-profile-placeholder {
            width: 96px;
            height: 96px;
            border-radius: 50%;
            background: transparent;
            color: #0b45b8;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .row > .col-md-6 .landing-profile-editor .landing-profile-placeholder svg {
            width: 54%;
            height: 54%;
            min-width: 1.2rem;
            min-height: 1.2rem;
        }

        .admin-photo-preview-shell {
            min-height: 360px;
            background: #07116f;
        }

        .admin-photo-preview-image {
            width: 100%;
            min-height: 360px;
            object-fit: cover;
            filter: saturate(1.04);
        }

        .admin-photo-preview-video {
            background: #02083f;
        }

        .admin-photo-preview-caption {
            position: absolute;
            inset: auto 1rem 1rem 1rem;
            max-width: 38rem;
            color: #fff;
            background: rgba(6, 25, 43, 0.76);
            border: 1px solid rgba(255, 255, 255, 0.16);
            border-radius: 1.15rem;
            padding: 1rem 1.1rem;
        }

        .admin-photo-preview-kicker {
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: rgba(191, 236, 255, 0.92);
            margin-bottom: 0.5rem;
        }

        .admin-photo-preview-caption h4 {
            margin-bottom: 0.5rem;
        }

        .admin-photo-preview-caption p {
            color: rgba(255, 255, 255, 0.84);
        }
    </style>
@endpush

@push('scripts')
    <script>
        (function () {
            const input = document.getElementById('photo_files');
            const target = document.getElementById('new-slide-fields');

            if (!input || !target) {
                return;
            }

            const escapeHtml = (value) => String(value)
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#39;');

            input.addEventListener('change', () => {
                const files = input.files ? Array.from(input.files) : [];
                target.innerHTML = '';

                files.forEach((file, index) => {
                    const objectUrl = URL.createObjectURL(file);
                    const safeName = escapeHtml(file.name || `Photo ${index + 1}`);
                    const isVideo = (file.type || '').startsWith('video/');
                    const previewMarkup = isVideo
                        ? `<video src="${objectUrl}" controls preload="metadata" playsinline class="new-slide-thumb landing-slide-video" aria-label="${safeName}"></video>`
                        : `<img src="${objectUrl}" alt="${safeName}" class="new-slide-thumb">`;

                    const wrapper = document.createElement('div');
                    wrapper.className = 'col-lg-6';
                    wrapper.innerHTML = `
                        <div class="new-slide-card h-100">
                            ${previewMarkup}
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label">New Slide Title</label>
                                    <input type="text" name="new_slide_titles[]" class="form-control" maxlength="120" placeholder="Example: ${safeName}">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">New Slide Detail</label>
                                    <textarea name="new_slide_details[]" class="form-control" rows="3" maxlength="280" placeholder="Write a short detail about this media"></textarea>
                                </div>
                            </div>
                        </div>
                    `;

                    target.appendChild(wrapper);
                });
            });
        })();
    </script>
@endpush
