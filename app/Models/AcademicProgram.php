<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcademicProgram extends Model
{
    protected $fillable = [
        'education_level',
        'name',
        'sort_order',
    ];

    /**
     * @return array<int, string>
     */
    public static function educationLevels(): array
    {
        return array_keys(config('sbc_programs', []));
    }

    /**
     * @return array<string, array<int, string>>
     */
    public static function groupedByLevel(): array
    {
        $programs = static::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->groupBy('education_level');

        $grouped = [];

        foreach (static::educationLevels() as $level) {
            $grouped[$level] = $programs
                ->get($level, collect())
                ->pluck('name')
                ->values()
                ->all();
        }

        return $grouped;
    }
}
