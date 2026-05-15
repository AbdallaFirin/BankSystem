<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kyc_documents', function (Blueprint $table) {
            $table->string('file_hash', 64)->nullable()->after('file_path')->comment('SHA-256 hash for duplicate detection');
            $table->boolean('is_duplicate_flagged')->default(false)->after('file_hash');
        });
    }

    public function down(): void
    {
        Schema::table('kyc_documents', function (Blueprint $table) {
            $table->dropColumn(['file_hash', 'is_duplicate_flagged']);
        });
    }
};
