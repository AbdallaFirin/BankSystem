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
        Schema::table('accounts', function (Blueprint $table) {
            $table->boolean('is_frozen')->default(false)->after('status');
            $table->string('frozen_reason', 500)->nullable()->after('is_frozen');
            $table->unsignedBigInteger('frozen_by')->nullable()->after('frozen_reason');
            $table->timestamp('frozen_at')->nullable()->after('frozen_by');
            $table->foreign('frozen_by')->references('id')->on('staff')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropForeign(['frozen_by']);
            $table->dropColumn(['is_frozen', 'frozen_reason', 'frozen_by', 'frozen_at']);
        });
    }
};
