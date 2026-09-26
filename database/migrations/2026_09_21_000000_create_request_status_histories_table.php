<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('request_status_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('request_id')->constrained('requests')->cascadeOnDelete();
            $table->string('status');
            $table->text('admin_notes')->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['request_id', 'created_at']);
        });

        DB::table('requests')
            ->orderBy('id')
            ->chunkById(100, function ($requests): void {
                foreach ($requests as $request) {
                    $submittedAt = $request->created_at ?? now();

                    DB::table('request_status_histories')->insert([
                        'request_id' => $request->id,
                        'status' => 'pending',
                        'admin_notes' => null,
                        'changed_by' => null,
                        'created_at' => $submittedAt,
                        'updated_at' => $submittedAt,
                    ]);

                    if ($request->status === 'pending') {
                        continue;
                    }

                    $changedAt = $request->processed_at
                        ?? $request->admin_replied_at
                        ?? $request->updated_at
                        ?? now();

                    DB::table('request_status_histories')->insert([
                        'request_id' => $request->id,
                        'status' => $request->status,
                        'admin_notes' => $request->admin_notes,
                        'changed_by' => $request->processed_by,
                        'created_at' => $changedAt,
                        'updated_at' => $changedAt,
                    ]);
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('request_status_histories');
    }
};
