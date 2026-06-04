<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    /**
     * All RESTRICT foreign keys referencing staff(id) are changed to SET NULL.
     *
     * Banking systems should never hard-delete staff records — staff have
     * initiated transactions, decided approvals, filed SARs, and generated
     * audit trails. SET NULL preserves all history while allowing the staff
     * row to be removed if absolutely necessary (e.g. test-data cleanup).
     *
     * Normal deactivation (status = 'inactive') is always preferred over deletion.
     */
    public function up(): void
    {
        // On a fresh database the original migrations already created these
        // columns as nullable with no FK, or with a different constraint name.
        // We use try/catch so the migration is idempotent: if the FK doesn't
        // exist (fresh install) we skip the drop and just re-declare it as
        // SET NULL; if it does exist (existing install) we replace it.
        $changes = [
            'staff_audit_logs'   => 'staff_id',
            'transactions'       => 'initiated_by',
            'account_freeze_logs'=> 'performed_by',
            'customers'          => 'registered_by',
            'kyc_documents'      => 'verified_by',
        ];

        foreach ($changes as $tableName => $column) {
            Schema::table($tableName, function (Blueprint $table) use ($column) {
                // Drop existing FK if present (ignore if it doesn't exist)
                try { $table->dropForeign([$column]); } catch (\Throwable $e) {}
                $table->unsignedBigInteger($column)->nullable()->change();
                // Re-add with SET NULL so staff deletion doesn't crash
                try {
                    $table->foreign($column)->references('id')->on('staff')->nullOnDelete();
                } catch (\Throwable $e) {}
            });
        }

        // pending_approvals — two columns
        Schema::table('pending_approvals', function (Blueprint $table) {
            foreach (['requested_by', 'decided_by'] as $col) {
                try { $table->dropForeign([$col]); } catch (\Throwable $e) {}
                $table->unsignedBigInteger($col)->nullable()->change();
                try {
                    $table->foreign($col)->references('id')->on('staff')->nullOnDelete();
                } catch (\Throwable $e) {}
            }
        });

        // loans — two columns
        Schema::table('loans', function (Blueprint $table) {
            foreach (['approved_by', 'reviewed_by'] as $col) {
                try { $table->dropForeign([$col]); } catch (\Throwable $e) {}
                $table->unsignedBigInteger($col)->nullable()->change();
                try {
                    $table->foreign($col)->references('id')->on('staff')->nullOnDelete();
                } catch (\Throwable $e) {}
            }
        });
    }

    public function down(): void
    {
        // Revert to RESTRICT (original behaviour)
        $tables = [
            'staff_audit_logs' => ['staff_id',   'staff'],
            'transactions'     => ['initiated_by','staff'],
            'account_freeze_logs' => ['performed_by','staff'],
            'customers'        => ['registered_by','staff'],
            'kyc_documents'    => ['verified_by', 'staff'],
        ];

        foreach ($tables as $table => [$col, $ref]) {
            Schema::table($table, function (Blueprint $t) use ($col, $ref) {
                $t->dropForeign([$col]);
                $t->foreign($col)->references('id')->on($ref)->restrictOnDelete();
            });
        }

        Schema::table('pending_approvals', function (Blueprint $table) {
            $table->dropForeign(['requested_by']);
            $table->dropForeign(['decided_by']);
            $table->foreign('requested_by')->references('id')->on('staff')->restrictOnDelete();
            $table->foreign('decided_by')->references('id')->on('staff')->restrictOnDelete();
        });

        Schema::table('loans', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropForeign(['reviewed_by']);
            $table->foreign('approved_by')->references('id')->on('staff')->restrictOnDelete();
            $table->foreign('reviewed_by')->references('id')->on('staff')->restrictOnDelete();
        });
    }
};
