<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('account_types', function (Blueprint $table) {
            $table->decimal('overdraft_limit', 15, 2)
                  ->nullable()
                  ->after('overdraft_allowed')
                  ->comment('Maximum amount balance may go negative. NULL = unlimited when overdraft_allowed = true.');
        });
    }

    public function down(): void
    {
        Schema::table('account_types', function (Blueprint $table) {
            $table->dropColumn('overdraft_limit');
        });
    }
};
