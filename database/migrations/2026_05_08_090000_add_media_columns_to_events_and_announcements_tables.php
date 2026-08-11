<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table): void {
            $table->string('media_path')->nullable()->after('location');
            $table->string('media_type', 20)->nullable()->after('media_path');
        });

        Schema::table('announcements', function (Blueprint $table): void {
            $table->string('media_path')->nullable()->after('content');
            $table->string('media_type', 20)->nullable()->after('media_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table): void {
            $table->dropColumn(['media_path', 'media_type']);
        });

        Schema::table('announcements', function (Blueprint $table): void {
            $table->dropColumn(['media_path', 'media_type']);
        });
    }
};

