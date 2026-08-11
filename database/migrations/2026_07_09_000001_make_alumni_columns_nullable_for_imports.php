<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('ALTER TABLE `alumni`
            MODIFY `student_id` VARCHAR(255) NULL,
            MODIFY `first_name` VARCHAR(255) NULL,
            MODIFY `last_name` VARCHAR(255) NULL,
            MODIFY `education_level` VARCHAR(255) NULL DEFAULT NULL,
            MODIFY `course` VARCHAR(255) NULL,
            MODIFY `year_graduated` INT NULL');
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('ALTER TABLE `alumni`
            MODIFY `student_id` VARCHAR(255) NOT NULL,
            MODIFY `first_name` VARCHAR(255) NOT NULL,
            MODIFY `last_name` VARCHAR(255) NOT NULL,
            MODIFY `education_level` VARCHAR(255) NOT NULL DEFAULT \'College\',
            MODIFY `course` VARCHAR(255) NOT NULL,
            MODIFY `year_graduated` INT NOT NULL');
    }
};
