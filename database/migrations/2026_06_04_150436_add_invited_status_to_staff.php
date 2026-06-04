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
        // Extend ENUM to include 'invited' for the staff invite flow.
        // Raw SQL is used because Laravel's Blueprint enum() replaces
        // the entire column definition, which is safer with ALTER COLUMN.
        DB::statement("ALTER TABLE staff MODIFY COLUMN status ENUM('active','inactive','invited') NOT NULL DEFAULT 'active'");
    }

    public function down(): void
    {
        // Remove 'invited' — any invited staff will be set to 'inactive' first
        DB::statement("UPDATE staff SET status = 'inactive' WHERE status = 'invited'");
        DB::statement("ALTER TABLE staff MODIFY COLUMN status ENUM('active','inactive') NOT NULL DEFAULT 'active'");
    }
};
