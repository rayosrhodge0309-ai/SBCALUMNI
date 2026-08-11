<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Activity extends Model
{
    protected $fillable = [
        'theme',
        'title',
        'description',
        'activity_date',
        'location',
        'media_path',
        'media_type',
        'is_published',
        'views_count',
    ];

    protected static function booted(): void
    {
        static::deleting(function (Activity $activity): void {
            if ($activity->media_path) {
                Storage::disk('public')->delete($activity->media_path);
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'activity_date' => 'date',
            'is_published' => 'boolean',
            'views_count' => 'integer',
        ];
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function getMediaUrlAttribute(): ?string
    {
        if (! $this->media_path) {
            return null;
        }

        return route('activities.media', $this);
    }

    public function getShowUrlAttribute(): string
    {
        return route('activities.show', $this);
    }

    public function isImageMedia(): bool
    {
        return $this->media_type === 'image';
    }

    public function isVideoMedia(): bool
    {
        return $this->media_type === 'video';
    }
}
