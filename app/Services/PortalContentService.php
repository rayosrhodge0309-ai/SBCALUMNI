<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\Announcement;
use App\Models\Event;
use Illuminate\Support\Collection;
use Throwable;

class PortalContentService
{
    public function announcementsCount(): int
    {
        try {
            return Announcement::query()
                ->published()
                ->count();
        } catch (Throwable) {
            return 0;
        }
    }

    public function announcements(?int $limit = 3): Collection
    {
        try {
            $query = Announcement::query()
                ->published()
                ->orderByDesc('published_at')
                ->latest('id');

            if ($limit !== null) {
                $query->take($limit);
            }

            return $query
                ->get()
                ->map(fn (Announcement $announcement) => [
                    'label' => $announcement->label ?: 'Announcement',
                    'title' => $announcement->title,
                    'description' => $announcement->content,
                    'media_url' => $announcement->media_url,
                    'media_type' => $announcement->media_type,
                    'published_at' => $announcement->published_at,
                ]);
        } catch (Throwable) {
            return collect();
        }
    }

    public function activities(?int $limit = 3): Collection
    {
        try {
            $query = Activity::query()
                ->published()
                ->orderByDesc('activity_date')
                ->latest('id');

            if ($limit !== null) {
                $query->take($limit);
            }

            return $query
                ->get()
                ->map(fn (Activity $activity) => [
                    'id' => $activity->id,
                    'theme' => $activity->theme ?: 'Activity',
                    'title' => $activity->title,
                    'description' => $activity->description,
                    'activity_date' => $activity->activity_date,
                    'location' => $activity->location,
                    'media_url' => $activity->media_url,
                    'media_type' => $activity->media_type,
                    'views_count' => $activity->views_count ?? 0,
                    'show_url' => $activity->show_url,
                ]);
        } catch (Throwable) {
            return collect();
        }
    }

    public function activitiesCount(): int
    {
        try {
            return Activity::query()
                ->published()
                ->count();
        } catch (Throwable) {
            return 0;
        }
    }

    public function upcomingEventsCount(): int
    {
        try {
            return Event::query()
                ->published()
                ->whereDate('event_date', '>=', today())
                ->count();
        } catch (Throwable) {
            return 0;
        }
    }

    public function events(?int $limit = 3): Collection
    {
        try {
            $query = Event::query()
                ->published()
                ->whereDate('event_date', '>=', today())
                ->orderBy('event_date');

            if ($limit !== null) {
                $query->take($limit);
            }

            return $query->get();
        } catch (Throwable) {
            return collect();
        }
    }
}
