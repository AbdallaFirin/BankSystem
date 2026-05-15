<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            $table->string('avatar')->nullable()->after('full_name');          // profile photo path
            $table->string('gender', 20)->nullable()->after('phone');
            $table->date('date_of_birth')->nullable()->after('gender');
            $table->string('address')->nullable()->after('date_of_birth');
            $table->string('emergency_contact')->nullable()->after('address'); // name + phone
            $table->date('hired_at')->nullable()->after('emergency_contact');
        });
    }

    public function down(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            $table->dropColumn([
                'avatar', 'gender', 'date_of_birth',
                'address', 'emergency_contact', 'hired_at',
            ]);
        });
    }
};
