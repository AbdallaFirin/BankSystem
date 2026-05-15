<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->string('email')->nullable()->after('phone');
            $table->string('swift_code')->nullable()->after('email');
            $table->string('routing_number')->nullable()->after('swift_code');
            $table->text('description')->nullable()->after('routing_number');
            $table->string('working_hours_start', 10)->default('08:00')->after('description');
            $table->string('working_hours_end', 10)->default('17:00')->after('working_hours_start');
            $table->json('working_days')->nullable()->after('working_hours_end'); // ["Mon","Tue","Wed","Thu","Fri"]
            $table->decimal('vault_min_threshold', 15, 2)->default(0)->after('working_days');
            $table->string('currency_code', 10)->default('USD')->after('vault_min_threshold');
            $table->string('timezone')->default('Africa/Nairobi')->after('currency_code');
        });
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn([
                'email', 'swift_code', 'routing_number', 'description',
                'working_hours_start', 'working_hours_end', 'working_days',
                'vault_min_threshold', 'currency_code', 'timezone',
            ]);
        });
    }
};
