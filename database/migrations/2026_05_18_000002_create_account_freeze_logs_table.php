<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_freeze_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();
            $table->enum('action', ['freeze', 'unfreeze']);
            $table->foreignId('performed_by')->constrained('staff')->restrictOnDelete();
            $table->text('reason')->nullable()->comment('Reason provided when freezing');
            $table->text('notes')->nullable()->comment('Notes provided when unfreezing');
            $table->timestamps();

            $table->index(['account_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_freeze_logs');
    }
};
