<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->string('purpose')->nullable()->after('term_months');
            $table->text('notes')->nullable()->after('purpose');
            $table->text('rejection_reason')->nullable()->after('notes');
            $table->unsignedBigInteger('reviewed_by')->nullable()->after('approved_by');
            $table->foreign('reviewed_by')->references('id')->on('staff');
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            $table->timestamp('approved_at')->nullable()->after('reviewed_at');
        });
    }

    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->dropForeign(['reviewed_by']);
            $table->dropColumn(['purpose', 'notes', 'rejection_reason', 'reviewed_by', 'reviewed_at', 'approved_at']);
        });
    }
};
