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
        // staff_audit_logs.staff_id
        Schema::table('staff_audit_logs', function (Blueprint $table) {
            $table->dropForeign(['staff_id']);
            $table->unsignedBigInteger('staff_id')->nullable()->change();
            $table->foreign('staff_id')->references('id')->on('staff')->nullOnDelete();
        });

        // transactions.initiated_by
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['initiated_by']);
            $table->unsignedBigInteger('initiated_by')->nullable()->change();
            $table->foreign('initiated_by')->references('id')->on('staff')->nullOnDelete();
        });

        // pending_approvals.requested_by
        Schema::table('pending_approvals', function (Blueprint $table) {
            $table->dropForeign(['requested_by']);
            $table->unsignedBigInteger('requested_by')->nullable()->change();
            $table->foreign('requested_by')->references('id')->on('staff')->nullOnDelete();
        });

        // pending_approvals.decided_by
        Schema::table('pending_approvals', function (Blueprint $table) {
            $table->dropForeign(['decided_by']);
            $table->unsignedBigInteger('decided_by')->nullable()->change();
            $table->foreign('decided_by')->references('id')->on('staff')->nullOnDelete();
        });

        // account_freeze_logs.performed_by
        Schema::table('account_freeze_logs', function (Blueprint $table) {
            $table->dropForeign(['performed_by']);
            $table->unsignedBigInteger('performed_by')->nullable()->change();
            $table->foreign('performed_by')->references('id')->on('staff')->nullOnDelete();
        });

        // customers.registered_by
        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['registered_by']);
            $table->unsignedBigInteger('registered_by')->nullable()->change();
            $table->foreign('registered_by')->references('id')->on('staff')->nullOnDelete();
        });

        // kyc_documents.verified_by
        Schema::table('kyc_documents', function (Blueprint $table) {
            $table->dropForeign(['verified_by']);
            $table->unsignedBigInteger('verified_by')->nullable()->change();
            $table->foreign('verified_by')->references('id')->on('staff')->nullOnDelete();
        });

        // loans.approved_by
        Schema::table('loans', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->unsignedBigInteger('approved_by')->nullable()->change();
            $table->foreign('approved_by')->references('id')->on('staff')->nullOnDelete();
        });

        // loans.reviewed_by
        Schema::table('loans', function (Blueprint $table) {
            $table->dropForeign(['reviewed_by']);
            $table->unsignedBigInteger('reviewed_by')->nullable()->change();
            $table->foreign('reviewed_by')->references('id')->on('staff')->nullOnDelete();
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
