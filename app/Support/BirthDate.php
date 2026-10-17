<?php

namespace App\Support;

use Carbon\CarbonInterface;

class BirthDate
{
    public const MAXIMUM_DATE = '2026-12-31';

    public static function maximumDate(?string $today = null): string
    {
        return min($today ?? now()->toDateString(), self::MAXIMUM_DATE);
    }

    public static function age(?CarbonInterface $birthday): ?int
    {
        $today = today();

        if ($birthday === null || $birthday->toDateString() > $today->toDateString()) {
            return null;
        }

        $age = $today->year - $birthday->year;

        return $age - ($today->format('md') < $birthday->format('md') ? 1 : 0);
    }
}
