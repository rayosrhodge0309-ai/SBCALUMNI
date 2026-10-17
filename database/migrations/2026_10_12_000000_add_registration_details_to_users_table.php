<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'registration_details')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->json('registration_details')->nullable();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('users', 'registration_details')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('registration_details');
        });
    }
};
