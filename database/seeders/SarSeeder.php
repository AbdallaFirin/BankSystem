<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SarSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $records = [
            [
                'reference'      => 'SAR-' . strtoupper(uniqid()),
                'reported_by'    => 3,   // Ahmed Mahamed Faarah — Compliance Officer, Bosaso
                'branch_id'      => 3,
                'customer_id'    => null,
                'transaction_id' => 15,  // DEP $200,000 — branch 6
                'risk_level'     => 'critical',
                'type'           => 'money_laundering',
                'description'    => 'Customer deposited $200,000 in cash without any documented source of funds. Amount far exceeds the customer\'s declared income. No prior transaction history of similar scale. Immediate regulatory notification recommended.',
                'status'         => 'submitted',
                'notes'          => null,
                'reviewed_by'    => null,
                'reviewed_at'    => null,
                'created_at'     => $now->copy()->subDays(12),
                'updated_at'     => $now->copy()->subDays(12),
            ],
            [
                'reference'      => 'SAR-' . strtoupper(uniqid()),
                'reported_by'    => 3,
                'branch_id'      => 3,
                'customer_id'    => 7,
                'transaction_id' => 22,  // WD $10,500 — branch 3
                'risk_level'     => 'high',
                'type'           => 'structuring',
                'description'    => 'Customer made three separate withdrawals of $10,500, $5,000, and $2,500 within a 48-hour window. The pattern is consistent with structuring to stay below reporting thresholds. All withdrawals were in cash with no stated purpose.',
                'status'         => 'under_review',
                'notes'          => 'Branch manager notified. Awaiting customer interview scheduled for next week.',
                'reviewed_by'    => 9,   // Zakariye Aadan
                'reviewed_at'    => $now->copy()->subDays(3),
                'created_at'     => $now->copy()->subDays(8),
                'updated_at'     => $now->copy()->subDays(3),
            ],
            [
                'reference'      => 'SAR-' . strtoupper(uniqid()),
                'reported_by'    => 9,   // Zakariye Aadan
                'branch_id'      => 3,
                'customer_id'    => 7,
                'transaction_id' => 16,  // DEP $20,000 — branch 6
                'risk_level'     => 'high',
                'type'           => 'unusual_activity',
                'description'    => 'Customer received a $20,000 international wire transfer from an unverified overseas entity. The customer could not provide a satisfactory explanation for the origin of funds. Account opened less than 60 days ago.',
                'status'         => 'submitted',
                'notes'          => null,
                'reviewed_by'    => null,
                'reviewed_at'    => null,
                'created_at'     => $now->copy()->subDays(5),
                'updated_at'     => $now->copy()->subDays(5),
            ],
            [
                'reference'      => 'SAR-' . strtoupper(uniqid()),
                'reported_by'    => 3,
                'branch_id'      => 3,
                'customer_id'    => null,
                'transaction_id' => 25,  // TRF $5,000 — branch 3
                'risk_level'     => 'medium',
                'type'           => 'fraud',
                'description'    => 'Teller flagged a $5,000 transfer initiated by a third party claiming to act on behalf of the account holder. The account holder later denied authorizing any such transfer. A possible account takeover or social engineering incident is suspected.',
                'status'         => 'under_review',
                'notes'          => 'Fraud team contacted. Transaction temporarily frozen pending investigation.',
                'reviewed_by'    => 3,
                'reviewed_at'    => $now->copy()->subDays(1),
                'created_at'     => $now->copy()->subDays(6),
                'updated_at'     => $now->copy()->subDays(1),
            ],
            [
                'reference'      => 'SAR-' . strtoupper(uniqid()),
                'reported_by'    => 9,
                'branch_id'      => 3,
                'customer_id'    => 7,
                'transaction_id' => null,
                'risk_level'     => 'medium',
                'type'           => 'identity_theft',
                'description'    => 'Customer presented a national ID that appeared to have been altered. The photo and date of birth were inconsistent with the physical appearance of the individual. KYC documents submitted during account opening are under secondary review.',
                'status'         => 'submitted',
                'notes'          => null,
                'reviewed_by'    => null,
                'reviewed_at'    => null,
                'created_at'     => $now->copy()->subDays(4),
                'updated_at'     => $now->copy()->subDays(4),
            ],
            [
                'reference'      => 'SAR-' . strtoupper(uniqid()),
                'reported_by'    => 1,   // Super Admin, branch 4
                'branch_id'      => 4,
                'customer_id'    => 1,
                'transaction_id' => null,
                'risk_level'     => 'low',
                'type'           => 'unusual_activity',
                'description'    => 'Customer\'s account showed login access from two geographically distant IP addresses within a 2-hour window. No fraudulent transactions detected at this time. Customer confirmed one of the sessions but could not account for the other.',
                'status'         => 'draft',
                'notes'          => null,
                'reviewed_by'    => null,
                'reviewed_at'    => null,
                'created_at'     => $now->copy()->subDays(2),
                'updated_at'     => $now->copy()->subDays(2),
            ],
            [
                'reference'      => 'SAR-' . strtoupper(uniqid()),
                'reported_by'    => 3,
                'branch_id'      => 3,
                'customer_id'    => null,
                'transaction_id' => 32,  // TRF $4,000 — branch 3
                'risk_level'     => 'medium',
                'type'           => 'structuring',
                'description'    => 'Multiple transfers of $4,000, $3,500, and $2,000 were conducted on consecutive days to different recipient accounts, all originating from the same source account. The cumulative total of $9,500 appears designed to avoid the $10,000 reporting threshold.',
                'status'         => 'closed',
                'notes'          => 'After review, customer provided documentation showing legitimate business payments to three separate suppliers. Case closed — no further action required.',
                'reviewed_by'    => 9,
                'reviewed_at'    => $now->copy()->subDays(1),
                'created_at'     => $now->copy()->subDays(14),
                'updated_at'     => $now->copy()->subDays(1),
            ],
            [
                'reference'      => 'SAR-' . strtoupper(uniqid()),
                'reported_by'    => 9,
                'branch_id'      => 3,
                'customer_id'    => 7,
                'transaction_id' => 17,  // WD $10,000 — branch 6
                'risk_level'     => 'critical',
                'type'           => 'money_laundering',
                'description'    => 'Account received a series of high-value deposits from multiple anonymous sources over 10 days, followed by a single $10,000 cash withdrawal. The deposit-then-withdraw pattern with no apparent economic rationale is a strong indicator of layering activity consistent with money laundering.',
                'status'         => 'submitted',
                'notes'          => null,
                'reviewed_by'    => null,
                'reviewed_at'    => null,
                'created_at'     => $now->copy()->subDays(1),
                'updated_at'     => $now->copy()->subDays(1),
            ],
            [
                'reference'      => 'SAR-' . strtoupper(uniqid()),
                'reported_by'    => 3,
                'branch_id'      => 3,
                'customer_id'    => 2,
                'transaction_id' => null,
                'risk_level'     => 'low',
                'type'           => 'other',
                'description'    => 'Customer asked branch staff detailed questions about transaction reporting limits and which amounts trigger regulatory filings. While no specific transaction was flagged, the inquiry itself is atypical and has been noted for monitoring.',
                'status'         => 'closed',
                'notes'          => 'No suspicious transactions linked. Customer interactions will be monitored for 90 days. Report closed.',
                'reviewed_by'    => 3,
                'reviewed_at'    => $now->copy()->subDays(7),
                'created_at'     => $now->copy()->subDays(20),
                'updated_at'     => $now->copy()->subDays(7),
            ],
        ];

        DB::table('suspicious_activity_reports')->insert($records);
    }
}
