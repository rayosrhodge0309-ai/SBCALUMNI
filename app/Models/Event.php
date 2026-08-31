<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Event extends Model
{
    protected $table = 'events';

    protected $fillable = [
        'title',
        'description',
        'event_date',
        'location',
        'media_path',
        'media_type',
        'is_published',
        'views_count',
    ];

    protected static function booted(): void
    {
        static::deleting(function (Event $event): void {
            if ($event->media_path) {
                Storage::disk('public')->delete($event->media_path);
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'event_date' => 'date',
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

        return route('events.media', $this);
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
