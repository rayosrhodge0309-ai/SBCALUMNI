<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const PROGRAMS = [
        'Elementary' => ['Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5'],
        'Junior High School' => ['Grade 7', 'Grade 8', 'Grade 9'],
        'Senior High School' => ['Grade 11 - STEM', 'Grade 11 - ABM', 'Grade 11 - HUMSS'],
    ];

    public function up(): void
    {
        DB::transaction(function (): void {
            foreach (self::PROGRAMS as $level => $names) {
                DB::table('academic_programs')
                    ->where('education_level', $level)
                    ->whereIn('name', $names)
                    ->delete();
            }
        });
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            $timestamp = now();

            foreach (self::PROGRAMS as $level => $names) {
                foreach ($names as $position => $name) {
                    DB::table('academic_programs')->insertOrIgnore([
                        'education_level' => $level,
                        'name' => $name,
                        'sort_order' => $position + 1,
                        'created_at' => $timestamp,
                        'updated_at' => $timestamp,
                    ]);
                }
            }
        });
    }
};
