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
        Schema::table('inter_branch_clearing', function (Blueprint $table) {
            $table->unsignedBigInteger('settled_by')->nullable()->after('settled_at');
            $table->foreign('settled_by')->references('id')->on('staff')->nullOnDelete();
            $table->text('notes')->nullable()->after('settled_by');
        });
    }

    public function down(): void
    {
        Schema::table('inter_branch_clearing', function (Blueprint $table) {
            $table->dropForeign(['settled_by']);
            $table->dropColumn(['settled_by', 'notes']);
        });
    }
};
