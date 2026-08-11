<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Throwable;

class SiteSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
    ];

    /**
     * @param  mixed  $default
     * @return mixed
     */
    public static function getValue(string $key, $default = null)
    {
        try {
            if (! Schema::hasTable('site_settings')) {
                return $default;
            }

            return static::query()
                ->where('key', $key)
                ->value('value') ?? $default;
        } catch (Throwable) {
            return $default;
        }
    }

    /**
     * @param  array<string, mixed>  $pairs
     */
    public static function setMany(array $pairs): void
    {
        try {
            if (! Schema::hasTable('site_settings')) {
                return;
            }

            foreach ($pairs as $key => $value) {
                static::query()->updateOrCreate(
                    ['key' => $key],
                    ['value' => $value]
                );
            }
        } catch (Throwable) {
            return;
        }
    }
}
