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
        Schema::table('branches', function (Blueprint $table) {
            // Short uppercase routing code used to identify branches in inter-branch
            // transfers. e.g. "BOS" = Bosaso, "GAR" = Garowe, "MOG" = Mogadishu.
            // Tellers and managers see this code on clearing entries — more readable
            // than a numeric branch_id.
            $table->string('branch_code', 10)
                  ->nullable()
                  ->unique()
                  ->after('branch_name')
                  ->comment('Short routing code, e.g. BOS, GAR, MOG');
        });
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropUnique(['branch_code']);
            $table->dropColumn('branch_code');
        });
    }
};
