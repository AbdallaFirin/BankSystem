<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Add branch_number — a unique 3-digit integer auto-assigned per branch,
     * starting from 101. Combined with branch_code prefix it forms the full
     * routing code, e.g. "BOS" + 101 = "BOS101".
     */
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->unsignedSmallInteger('branch_number')
                  ->nullable()
                  ->unique()
                  ->after('branch_code')
                  ->comment('Auto-generated sequential number starting at 101');
        });

        // Auto-assign sequential numbers to existing branches, ordered by id.
        $branches = DB::table('branches')->orderBy('id')->get(['id']);
        $next = 101;
        foreach ($branches as $branch) {
            DB::table('branches')
                ->where('id', $branch->id)
                ->update(['branch_number' => $next++]);
        }
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropUnique(['branch_number']);
            $table->dropColumn('branch_number');
        });
    }
};
