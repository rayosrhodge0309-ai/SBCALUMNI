@php
    $currentEvent = $event ?? null;
    $isPublished = (bool) old('is_published', $currentEvent?->is_published ?? true);
@endphp

<div class="row g-3">
    <div class="col-md-8">
        <label class="form-label" for="title">Title</label>
        <input id="title" type="text" name="title" class="form-control" value="{{ old('title', $currentEvent?->title) }}" required>
    </div>
    <div class="col-md-4">
        <label class="form-label" for="event_date">Event Date</label>
        <input id="event_date" type="date" name="event_date" class="form-control" value="{{ old('event_date', $currentEvent?->event_date?->format('Y-m-d')) }}" required>
    </div>
    <div class="col-12">
        <label class="form-label" for="location">Location</label>
        <input id="location" type="text" name="location" class="form-control" value="{{ old('location', $currentEvent?->location) }}">
    </div>
    <div class="col-12">
        <label class="form-label" for="description">Description</label>
        <textarea id="description" name="description" class="form-control" rows="5" required>{{ old('description', $currentEvent?->description) }}</textarea>
    </div>
    <div class="col-12">
        <label class="form-label" for="media">Photo or Video (Optional)</label>
        <input id="media" type="file" name="media" class="form-control" accept="image/jpeg,image/png,image/webp,image/gif,video/mp4,video/webm,video/ogg,video/quicktime">
        <div class="form-text">Accepted: JPG, PNG, WEBP, GIF, MP4, WEBM, OGG, MOV. Max size: 20MB.</div>
    </div>
    @if ($currentEvent?->media_url)
        <div class="col-12">
            @if ($currentEvent->isImageMedia())
                <img src="{{ $currentEvent->media_url }}" alt="{{ $currentEvent->title }}" class="activity-media mb-2">
            @elseif ($currentEvent->isVideoMedia())
                <video class="activity-media activity-media-video mb-2" controls preload="metadata">
                    <source src="{{ $currentEvent->media_url }}">
                    Your browser does not support the video tag.
                </video>
            @endif
            <div class="form-check">
                <input class="form-check-input" type="checkbox" value="1" id="remove_media" name="remove_media">
                <label class="form-check-label" for="remove_media">Remove current media</label>
            </div>
        </div>
    @endif
    <div class="col-12">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" value="1" id="is_published" name="is_published" @checked($isPublished)>
            <label class="form-check-label" for="is_published">Visible to alumni and on the landing page</label>
        </div>
    </div>
</div>

<div class="d-flex gap-2 mt-4">
    <button class="btn btn-primary" type="submit">{{ $submitLabel ?? 'Save Event' }}</button>
    <a href="{{ route('events.index') }}" class="btn btn-outline-secondary">Cancel</a>
</div>
