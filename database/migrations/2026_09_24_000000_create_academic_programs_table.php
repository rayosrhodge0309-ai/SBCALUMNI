<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_programs', function (Blueprint $table) {
            $table->id();
            $table->string('education_level', 100);
            $table->string('name', 150);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['education_level', 'name']);
            $table->index(['education_level', 'sort_order']);
        });

        $programsByLevel = require config_path('sbc_programs.php');
        $timestamp = now();
        $rows = [];

        foreach ($programsByLevel as $level => $programs) {
            foreach ($programs as $position => $program) {
                $rows[] = [
                    'education_level' => $level,
                    'name' => $program,
                    'sort_order' => $position + 1,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
            }
        }

        DB::table('academic_programs')->insert($rows);
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_programs');
    }
};
