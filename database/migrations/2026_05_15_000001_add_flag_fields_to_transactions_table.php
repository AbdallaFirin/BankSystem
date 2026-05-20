<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->boolean('is_flagged')->default(false)->after('description');
            $table->text('flag_reason')->nullable()->after('is_flagged');
            $table->foreignId('flagged_by')->nullable()->constrained('staff')->nullOnDelete()->after('flag_reason');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('flagged_by');
            $table->dropColumn(['is_flagged', 'flag_reason']);
        });
    }
};
