<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('alumni', 'maiden_name')) {
            return;
        }

        Schema::table('alumni', function (Blueprint $table) {
            $table->string('maiden_name', 100)->nullable()->after('last_name');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('alumni', 'maiden_name')) {
            return;
        }

        Schema::table('alumni', function (Blueprint $table) {
            $table->dropColumn('maiden_name');
        });
    }
};
