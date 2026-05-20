<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Reformat every customer account number to use the branch full_code as prefix.
     *
     * Old format: ACC-IHAZUYBC  (random)  or  GAR000120 (3-letter name prefix)
     * New format: BOS103000001  (full_code + 6-digit zero-padded sequence)
     *
     * VAULT-* and TILL-* system accounts are left untouched.
     * Accounts that already match the new pattern are also skipped.
     */
    public function up(): void
    {
        // Load branches with their codes and numbers.
        $branches = DB::table('branches')->orderBy('id')->get();

        foreach ($branches as $branch) {
            $fullCode = ($branch->branch_code ?? '') . ($branch->branch_number ?? '');

            if (empty($fullCode)) {
                // No code assigned yet — skip this branch.
                continue;
            }

            // Fetch all customer accounts for this branch, ordered by id (oldest first).
            $accounts = DB::table('accounts')
                ->where('home_branch_id', $branch->id)
                ->where('account_number', 'not like', 'VAULT-%')
                ->where('account_number', 'not like', 'TILL-%')
                ->orderBy('id')
                ->get(['id', 'account_number']);

            $seq = 1;
            foreach ($accounts as $account) {
                // Build the new number.
                $newNumber = $fullCode . str_pad($seq, 6, '0', STR_PAD_LEFT);

                // Skip if it already looks correct (idempotent re-runs).
                if ($account->account_number === $newNumber) {
                    $seq++;
                    continue;
                }

                // Avoid collisions (another branch may have generated the same string).
                while (DB::table('accounts')->where('account_number', $newNumber)->where('id', '!=', $account->id)->exists()) {
                    $seq++;
                    $newNumber = $fullCode . str_pad($seq, 6, '0', STR_PAD_LEFT);
                }

                DB::table('accounts')
                    ->where('id', $account->id)
                    ->update(['account_number' => $newNumber]);

                $seq++;
            }
        }
    }

    /**
     * Rollback is intentionally a no-op — the original account numbers
     * were random (ACC-XXXXX) and cannot be safely restored automatically.
     * If you need to roll back, restore from a DB backup taken before this migration.
     */
    public function down(): void
    {
        // no-op
    }
};
