<?php

namespace App\Support;

use Illuminate\Support\Str;

final class StudentIdFormatter
{
    public static function normalize(string $studentId): string
    {
        $studentId = trim($studentId);

        if ($studentId === '') {
            return $studentId;
        }

        if (! preg_match('/^\d[\d\s-]*\d$/', $studentId)) {
            return $studentId;
        }

        return preg_replace('/[\s-]+/', '', $studentId) ?? $studentId;
    }

    public static function display(string $studentId): string
    {
        $studentId = trim($studentId);

        if ($studentId === '' || Str::startsWith($studentId, 'TEMP-')) {
            return '-';
        }

        return $studentId;
    }

    /**
     * @return array<int, string>
     */
    public static function variants(string $studentId): array
    {
        $studentId = trim($studentId);

        if ($studentId === '') {
            return [];
        }

        $variants = [$studentId];
        $normalized = self::normalize($studentId);

        if ($normalized !== $studentId) {
            $variants[] = $normalized;
        }

        if (preg_match('/^\d{9}$/', $normalized)) {
            $variants[] = substr($normalized, 0, 2)
                .'-'
                .substr($normalized, 2, 4)
                .'-'
                .substr($normalized, 6, 3);
        }

        return array_values(array_unique($variants));
    }
}
