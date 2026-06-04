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
        Schema::table('staff', function (Blueprint $table) {
            $table->unsignedTinyInteger('failed_attempts')->default(0)->after('temp_password');
            $table->timestamp('locked_until')->nullable()->after('failed_attempts');
            $table->timestamp('password_changed_at')->nullable()->after('locked_until');
        });
    }

    public function down(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            $table->dropColumn(['failed_attempts', 'locked_until', 'password_changed_at']);
        });
    }
};
