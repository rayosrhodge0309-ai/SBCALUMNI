<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->text('admin_notes')->nullable()->after('status');
            $table->foreignId('processed_by')->nullable()->after('admin_notes')->constrained('users')->nullOnDelete();
            $table->timestamp('processed_at')->nullable()->after('processed_by');
        });

        DB::table('requests')->where('status', 'approved')->update(['status' => 'ready_for_pickup']);
        DB::table('requests')->where('status', 'released')->update(['status' => 'completed']);
    }

    public function down(): void
    {
        DB::table('requests')->where('status', 'ready_for_pickup')->update(['status' => 'approved']);
        DB::table('requests')->where('status', 'completed')->update(['status' => 'released']);

        Schema::table('requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('processed_by');
            $table->dropColumn(['admin_notes', 'processed_at']);
        });
    }
};
