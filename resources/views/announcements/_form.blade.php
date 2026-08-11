@php
    $currentAnnouncement = $announcement ?? null;
@endphp

<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label" for="label">Label</label>
        <input id="label" type="text" name="label" class="form-control" value="{{ old('label', $currentAnnouncement?->label) }}" placeholder="Records, Community, Notice">
    </div>
    <div class="col-md-8">
        <label class="form-label" for="title">Title</label>
        <input id="title" type="text" name="title" class="form-control" value="{{ old('title', $currentAnnouncement?->title) }}" required>
    </div>
    <div class="col-12">
        <label class="form-label" for="content">Announcement Details</label>
        <textarea id="content" name="content" class="form-control" rows="6" required>{{ old('content', $currentAnnouncement?->content) }}</textarea>
    </div>
    <div class="col-12">
        <label class="form-label" for="media">Photo or Video (Optional)</label>
        <input id="media" type="file" name="media" class="form-control" accept="image/jpeg,image/png,image/webp,image/gif,video/mp4,video/webm,video/ogg,video/quicktime">
        <div class="form-text">Accepted: JPG, PNG, WEBP, GIF, MP4, WEBM, OGG, MOV. Max size: 20MB.</div>
    </div>
    @if ($currentAnnouncement?->media_url)
        <div class="col-12">
            @if ($currentAnnouncement->isImageMedia())
                <img src="{{ $currentAnnouncement->media_url }}" alt="{{ $currentAnnouncement->title }}" class="activity-media mb-2">
            @elseif ($currentAnnouncement->isVideoMedia())
                <video class="activity-media activity-media-video mb-2" controls preload="metadata">
                    <source src="{{ $currentAnnouncement->media_url }}">
                    Your browser does not support the video tag.
                </video>
            @endif
            <div class="form-check">
                <input class="form-check-input" type="checkbox" value="1" id="remove_media" name="remove_media">
                <label class="form-check-label" for="remove_media">Remove current media</label>
            </div>
        </div>
    @endif
    <div class="col-md-6">
        <label class="form-label" for="published_at">Publish Date and Time</label>
        <input id="published_at" type="datetime-local" name="published_at" class="form-control" value="{{ old('published_at', $currentAnnouncement?->published_at?->format('Y-m-d\TH:i')) }}">
    </div>
    <div class="col-md-6 d-flex align-items-end">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" value="1" id="is_published" name="is_published" @checked(old('is_published', $currentAnnouncement?->is_published ?? true))>
            <label class="form-check-label" for="is_published">Visible to alumni and on the landing page</label>
        </div>
    </div>
</div>

<div class="d-flex gap-2 mt-4">
    <button class="btn btn-primary" type="submit">{{ $submitLabel ?? 'Save Announcement' }}</button>
    <a href="{{ route('announcements.index') }}" class="btn btn-outline-secondary">Cancel</a>
</div>
