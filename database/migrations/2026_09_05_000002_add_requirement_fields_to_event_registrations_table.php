<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_registrations', function (Blueprint $table): void {
            $table->string('attendee_name')->nullable();
            $table->string('attendee_email')->nullable();
            $table->string('attendee_student_id')->nullable();
            $table->string('attendee_course')->nullable();
            $table->unsignedSmallInteger('attendee_year_graduated')->nullable();
            $table->string('attendee_contact_number')->nullable();
            $table->text('attendee_note')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('event_registrations', function (Blueprint $table): void {
            $table->dropColumn([
                'attendee_name',
                'attendee_email',
                'attendee_student_id',
                'attendee_course',
                'attendee_year_graduated',
                'attendee_contact_number',
                'attendee_note',
            ]);
        });
    }
};
