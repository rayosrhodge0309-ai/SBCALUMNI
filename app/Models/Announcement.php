<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Announcement extends Model
{
    protected $fillable = [
        'label',
        'title',
        'content',
        'media_path',
        'media_type',
        'is_published',
        'views_count',
        'published_at',
    ];

    protected static function booted(): void
    {
        static::deleting(function (Announcement $announcement): void {
            if ($announcement->media_path) {
                Storage::disk('public')->delete($announcement->media_path);
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'views_count' => 'integer',
            'published_at' => 'datetime',
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

        return route('announcements.media', $this);
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
