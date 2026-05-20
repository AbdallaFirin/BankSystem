<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Models\LedgerEntry;
use App\Models\Transaction;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class AccrueInterest extends Command
{
    protected $signature   = 'bank:accrue-interest
                                {--dry-run : Show calculations without applying any changes}
                                {--month=  : Target month in YYYY-MM format (default: current month)}';

    protected $description = 'Credit monthly interest to all active savings/interest-bearing accounts';

    public function handle(NotificationService $notifications): int
    {
        $dryRun      = $this->option('dry-run');
        $monthOption = $this->option('month') ?: now()->format('Y-m');

        [$year, $month] = explode('-', $monthOption);
        $periodLabel    = \Carbon\Carbon::createFromDate($year, $month, 1)->format('F Y');
        $referenceMonth = "{$year}-{$month}";

        $this->info(($dryRun ? '[DRY RUN] ' : '') . "Accruing monthly interest for {$periodLabel}…");
        $this->newLine();

        // Load all active, non-frozen accounts that have a positive interest rate
        $accounts = Account::with(['accountType', 'customer'])
            ->where('status', 'active')
            ->where('is_frozen', false)
            ->get()
            ->filter(fn($acc) =>
                $acc->accountType &&
                (float) $acc->accountType->interest_rate > 0
            );

        if ($accounts->isEmpty()) {
            $this->warn('No interest-bearing accounts found.');
            return self::SUCCESS;
        }

        $processed    = 0;
        $skipped      = 0;
        $totalInterest = 0.0;

        $headers = ['Account', 'Balance', 'Rate %', 'Monthly Interest', 'Status'];
        $rows    = [];

        foreach ($accounts as $account) {
            // Idempotency guard — skip if we already ran interest for this month
            $alreadyDone = Transaction::where('account_id', $account->id)
                ->where('type', 'interest')
                ->where('reference', 'like', "INT-{$referenceMonth}-%")
                ->exists();

            if ($alreadyDone) {
                $rows[] = [
                    $account->account_number,
                    number_format($account->balance, 2),
                    $account->accountType->interest_rate,
                    '—',
                    'Already accrued',
                ];
                $skipped++;
                continue;
            }

            $balance  = $account->balance;
            $rate     = (float) $account->accountType->interest_rate; // annual %
            $interest = round($balance * ($rate / 100) / 12, 2);      // monthly

            if ($interest <= 0) {
                $rows[] = [
                    $account->account_number,
                    number_format($balance, 2),
                    $rate,
                    '0.00',
                    'Skipped (zero interest)',
                ];
                $skipped++;
                continue;
            }

            $totalInterest += $interest;
            $reference      = "INT-{$referenceMonth}-" . str_pad($account->id, 6, '0', STR_PAD_LEFT);

            if (!$dryRun) {
                try {
                    DB::transaction(function () use ($account, $interest, $reference, $referenceMonth) {
                        $transaction = Transaction::create([
                            'reference'    => $reference,
                            'type'         => 'interest',
                            'status'       => 'completed',
                            'amount'       => $interest,
                            'account_id'   => $account->id,
                            'to_account_id'=> null,
                            'initiated_by' => null,
                            'branch_id'    => $account->home_branch_id ?? 1,
                            'description'  => 'Monthly Interest Credit — ' .
                                              \Carbon\Carbon::createFromFormat('Y-m', $referenceMonth)->format('F Y'),
                        ]);

                        LedgerEntry::create([
                            'transaction_id' => $transaction->id,
                            'account_id'     => $account->id,
                            'branch_id'      => $account->home_branch_id ?? 1,
                            'entry_type'     => 'credit',
                            'amount'         => $interest,
                            'balance_after'  => $account->balance + $interest,
                        ]);
                    });

                    // Notify the customer
                    if ($account->customer) {
                        $notifications->send(
                            recipientId:   $account->customer->id,
                            recipientType: 'customer',
                            message:       "Interest of \${$interest} has been credited to account {$account->account_number} for {$referenceMonth}.",
                            subject:       "Interest Credited — {$referenceMonth}",
                            mailView:      '',   // in-app only
                            mailData:      []
                        );
                    }

                    $rows[] = [
                        $account->account_number,
                        number_format($balance, 2),
                        $rate,
                        number_format($interest, 2),
                        '✓ Credited',
                    ];
                    $processed++;
                } catch (Throwable $e) {
                    $rows[] = [
                        $account->account_number,
                        number_format($balance, 2),
                        $rate,
                        number_format($interest, 2),
                        'ERROR: ' . $e->getMessage(),
                    ];
                    $this->error("  Failed for {$account->account_number}: " . $e->getMessage());
                }
            } else {
                $rows[] = [
                    $account->account_number,
                    number_format($balance, 2),
                    $rate,
                    number_format($interest, 2),
                    '[Would credit]',
                ];
                $processed++;
            }
        }

        $this->table($headers, $rows);
        $this->newLine();

        $label = $dryRun ? '[DRY RUN] Would credit' : 'Credited';
        $this->info("{$label} interest to {$processed} account(s). Total: \$" . number_format($totalInterest, 2));
        if ($skipped) {
            $this->line("  {$skipped} account(s) skipped (already accrued or zero-interest balance).");
        }

        return self::SUCCESS;
    }
}
