@extends('layouts.app')

@section('title', $activity->title.' | SBC Alumni Feed')
@section('full_guest', true)

@section('content')
    <div class="activity-show-page">
        <div class="main-wrapper py-4 py-lg-5">
            <div class="activity-show-header mb-4">
                <a href="{{ route('home') }}#alumni-feed" class="btn btn-outline-primary btn-sm mb-3">Back to Alumni Feed</a>
                <div class="d-flex flex-wrap align-items-end justify-content-between gap-3">
                    <div class="min-w-0">
                        <div class="section-eyebrow">SBC Alumni Post</div>
                        <h1 class="section-title mb-0">{{ $activity->title }}</h1>
                    </div>
                    <div class="activity-show-stats">
                        <div class="activity-show-stat">
                            <div class="activity-show-stat-value">{{ number_format($activity->views_count ?? 0) }}</div>
                            <div class="activity-show-stat-label">Views</div>
                        </div>
                        <div class="activity-show-stat">
                            <div class="activity-show-stat-value">{{ $activity->activity_date?->format('d') ?: 'TBA' }}</div>
                            <div class="activity-show-stat-label">Date</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4 align-items-start">
                <div class="col-lg-8">
                    <article class="page-card overflow-hidden">
                        @if ($activity->media_url)
                            <div class="activity-show-media">
                                @if ($activity->isImageMedia())
                                    <img src="{{ $activity->media_url }}" alt="{{ $activity->title }}" class="activity-show-media-asset">
                                @elseif ($activity->isVideoMedia())
                                    <video class="activity-show-media-asset" controls preload="metadata" playsinline>
                                        <source src="{{ $activity->media_url }}">
                                        Your browser does not support the video tag.
                                    </video>
                                @endif
                            </div>
                        @endif

                        <div class="p-4 p-lg-5">
                            <div class="d-flex flex-wrap gap-2 mb-3">
                                <span class="activity-show-pill">{{ $activity->theme ?: 'SBC Alumni Story' }}</span>
                                <span class="activity-show-pill activity-show-pill-soft">
                                    {{ $activity->activity_date?->format('F d, Y') ?: 'Date TBA' }}
                                </span>
                                @php
                                    $activityViewCount = (int) ($activity->views_count ?? 0);
                                @endphp
                                <span class="activity-show-pill activity-show-pill-soft">
                                    {{ number_format($activityViewCount) }} {{ $activityViewCount === 1 ? 'view' : 'views' }}
                                </span>
                            </div>

                            @if ($activity->location)
                                <div class="small text-secondary mb-3">{{ $activity->location }}</div>
                            @endif

                            <div class="activity-show-copy">
                                {!! nl2br(e($activity->description)) !!}
                            </div>
                        </div>
                    </article>
                </div>

                <div class="col-lg-4">
                    <aside class="d-grid gap-3">
                        <div class="page-card p-4">
                            <div class="section-eyebrow mb-2">About this post</div>
                            <p class="text-secondary mb-0">
                                This SBC alumni post was published for the Bridgetine community and can be updated in the admin activity manager.
                            </p>
                        </div>

                        <div class="page-card p-4">
                            <div class="section-eyebrow mb-2">More SBC Alumni Posts</div>
                            <div class="d-grid gap-3">
                                @forelse ($relatedActivities as $related)
                                    <a href="{{ route('activities.show', $related) }}" class="activity-sidebar-card text-decoration-none">
                                        <div class="activity-sidebar-media">
                                            @if ($related->media_url)
                                                @if ($related->isImageMedia())
                                                    <img src="{{ $related->media_url }}" alt="{{ $related->title }}" class="activity-sidebar-media-asset">
                                                @elseif ($related->isVideoMedia())
                                                    <video class="activity-sidebar-media-asset" muted playsinline preload="metadata">
                                                        <source src="{{ $related->media_url }}">
                                                        Your browser does not support the video tag.
                                                    </video>
                                                @endif
                                            @endif
                                        </div>
                                        <div class="activity-sidebar-copy">
                                            <div class="activity-sidebar-title">{{ $related->title }}</div>
                                            <div class="activity-sidebar-meta">
                                                {{ $related->activity_date?->format('M d, Y') ?: 'Date TBA' }}
                                                @php
                                                    $relatedViews = (int) ($related->views_count ?? 0);
                                                @endphp
                                                <span>{{ number_format($relatedViews) }} {{ $relatedViews === 1 ? 'view' : 'views' }}</span>
                                            </div>
                                        </div>
                                    </a>
                                @empty
                                    <div class="text-secondary small">No other posts yet.</div>
                                @endforelse
                            </div>
                        </div>
                    </aside>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .activity-show-page .section-title {
            font-size: clamp(2rem, 3vw, 3rem);
        }

        .activity-show-stats {
            display: flex;
            gap: 0.75rem;
        }

        .activity-show-stat {
            min-width: 6rem;
            padding: 0.8rem 0.95rem;
            border-radius: 0.9rem;
            background: #f7fbff;
            border: 1px solid rgba(7, 17, 111, 0.12);
            text-align: center;
        }

        .activity-show-stat-value {
            color: #07116f;
            font-size: 1.3rem;
            font-weight: 800;
            line-height: 1;
        }

        .activity-show-stat-label {
            margin-top: 0.3rem;
            color: #6c6f77;
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }

        .activity-show-media {
            aspect-ratio: 16 / 9;
            background: #eaf2ff;
        }

        .activity-show-media-asset {
            width: 100%;
            height: 100%;
            display: block;
            object-fit: cover;
            background: #02083f;
        }

        .activity-show-pill {
            display: inline-flex;
            align-items: center;
            padding: 0.35rem 0.65rem;
            border-radius: 999px;
            background: rgba(11, 69, 184, 0.08);
            color: #0b45b8;
            font-size: 0.74rem;
            font-weight: 800;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .activity-show-pill-soft {
            background: rgba(7, 17, 111, 0.06);
            color: #07116f;
        }

        .activity-show-copy {
            color: #1f2330;
            font-size: 1.03rem;
            line-height: 1.8;
            white-space: pre-wrap;
        }

        .activity-sidebar-card {
            display: grid;
            grid-template-columns: 4rem minmax(0, 1fr);
            gap: 0.75rem;
            align-items: center;
            padding: 0.6rem;
            border-radius: 0.85rem;
            border: 1px solid rgba(7, 17, 111, 0.1);
            background: #fff;
        }

        .activity-sidebar-card:hover,
        .activity-sidebar-card:focus-visible {
            border-color: rgba(11, 69, 184, 0.24);
            box-shadow: 0 12px 24px rgba(7, 17, 111, 0.08);
            outline: none;
        }

        .activity-sidebar-media {
            aspect-ratio: 1 / 1;
            border-radius: 0.7rem;
            overflow: hidden;
            background: #eef4ff;
        }

        .activity-sidebar-media-asset {
            width: 100%;
            height: 100%;
            display: block;
            object-fit: cover;
            background: #02083f;
        }

        .activity-sidebar-title {
            color: #07116f;
            font-weight: 700;
            line-height: 1.2;
        }

        .activity-sidebar-meta {
            margin-top: 0.3rem;
            color: #6c6f77;
            font-size: 0.78rem;
        }

        .activity-sidebar-meta span::before {
            content: "•";
            margin: 0 0.35rem;
            color: rgba(7, 17, 111, 0.5);
        }

        @media (max-width: 767.98px) {
            .activity-show-stats {
                width: 100%;
                flex-wrap: wrap;
            }

            .activity-show-stat {
                flex: 1 1 7rem;
            }
        }
    </style>
@endpush
