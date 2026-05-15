<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cash_counts', function (Blueprint $table) {
            // 'pending' | 'verified' | 'flagged'
            $table->string('approval_status')->default('pending')->after('status');

            $table->foreignId('verified_by')
                  ->nullable()
                  ->constrained('staff')
                  ->nullOnDelete()
                  ->after('approval_status');

            $table->timestamp('verified_at')->nullable()->after('verified_by');
            $table->text('flag_reason')->nullable()->after('verified_at');
        });
    }

    public function down(): void
    {
        Schema::table('cash_counts', function (Blueprint $table) {
            $table->dropForeign(['verified_by']);
            $table->dropColumn(['approval_status', 'verified_by', 'verified_at', 'flag_reason']);
        });
    }
};
