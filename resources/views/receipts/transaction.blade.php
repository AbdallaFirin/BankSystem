<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<title>Receipt — {{ $transaction->reference }}</title>
<style>
@page { margin: 6mm 5mm; }
* { margin: 0; padding: 0; box-sizing: border-box; }

body {
    font-family: DejaVu Sans, Arial, sans-serif;
    font-size: 9px;
    color: #1a1a1a;
    background: #fff;
    width: 100%;
}

/* ── Header ── */
.header {
    text-align: center;
    padding: 10px 0 8px;
    border-bottom: 2px solid #0F1C2E;
}
.logo { height: 38px; width: auto; display: block; margin: 0 auto 4px; }
.bank-name  { font-size: 12px; font-weight: bold; color: #0F1C2E; letter-spacing: 0.5px; }
.bank-sub   { font-size: 8px;  color: #555555; margin-top: 1px; }

/* ── Receipt type label ── */
.receipt-label {
    text-align: center;
    padding: 5px 0;
    font-size: 9px;
    font-weight: bold;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    color: #0F1C2E;
    border-bottom: 1px dashed #cccccc;
}

/* ── Field rows ── */
.field-row {
    display: flex;
    justify-content: space-between;
    padding: 4px 8px;
    border-bottom: 1px solid #f0f0f0;
}
.field-row:nth-child(even) { background: #fafafa; }
.field-label { font-size: 8px; color: #888888; text-transform: uppercase; letter-spacing: 0.5px; }
.field-value { font-size: 9px; color: #1a1a1a; font-weight: bold; text-align: right; max-width: 60%; word-break: break-word; }
.field-value.mono { font-family: "Courier New", monospace; font-size: 8.5px; }

/* ── Amount box ── */
.amount-box {
    margin: 8px;
    padding: 8px;
    border: 2px solid #0F1C2E;
    border-radius: 3px;
    text-align: center;
}
.amount-label { font-size: 8px; color: #555555; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 2px; }
.amount-value { font-size: 18px; font-weight: bold; font-family: "Courier New", monospace; color: #0F1C2E; }
.amount-type  { font-size: 8px; margin-top: 2px; }
.amount-type.cr { color: #1a7a45; }
.amount-type.dr { color: #b32020; }

/* ── Status badge ── */
.status-badge {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 7.5px;
    font-weight: bold;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.status-completed { background: #e8f5ec; color: #1a7a45; border: 1px solid #a8d5b5; }
.status-pending   { background: #fff8e6; color: #b36a00; border: 1px solid #f5d98a; }
.status-rejected  { background: #fde8e8; color: #b32020; border: 1px solid #f5c6c3; }

/* ── Divider ── */
.dashed { border-top: 1px dashed #cccccc; margin: 5px 0; }

/* ── Footer ── */
.footer {
    text-align: center;
    padding: 6px 8px 4px;
    border-top: 2px solid #0F1C2E;
    margin-top: 8px;
}
.footer-ref  { font-size: 7px; color: #888888; font-family: "Courier New", monospace; }
.footer-note { font-size: 7px; color: #aaaaaa; margin-top: 3px; }

/* ── Served by ── */
.served-by {
    padding: 5px 8px;
    background: #f2f4f7;
    border-top: 1px solid #d0d0d0;
    font-size: 8px;
    color: #555555;
}
.served-by strong { color: #1a1a1a; }
</style>
</head>
<body>

@php
    $txn         = $transaction;
    $isPending   = in_array($txn->status, ['pending_approval', 'pending']);
    $isRejected  = $txn->status === 'rejected';
    $isCompleted = $txn->status === 'completed';

    $typeLabels = [
        'deposit'    => 'Cash Deposit',
        'withdrawal' => 'Cash Withdrawal',
        'transfer'   => 'Inter-Account Transfer',
    ];
    $typeLabel = $typeLabels[$txn->type] ?? ucfirst($txn->type ?? 'Transaction');

    $fromAcc = $txn->primaryAccount;
    $toAcc   = $txn->secondaryAccount;

    $customerName = match($txn->type) {
        'deposit'    => $toAcc?->customer?->full_name   ?? '—',
        'withdrawal' => $fromAcc?->customer?->full_name ?? '—',
        'transfer'   => $fromAcc?->customer?->full_name ?? '—',
        default      => $fromAcc?->customer?->full_name ?? '—',
    };

    $accountNumber = match($txn->type) {
        'deposit'    => $toAcc?->account_number   ?? '—',
        'withdrawal' => $fromAcc?->account_number ?? '—',
        'transfer'   => $fromAcc?->account_number ?? '—',
        default      => $fromAcc?->account_number ?? '—',
    };

    $statusClass = $isRejected ? 'status-rejected'
                 : ($isPending ? 'status-pending' : 'status-completed');
    $statusLabel = match($txn->status) {
        'completed'        => 'Completed',
        'pending_approval' => 'Pending Approval',
        'pending'          => 'Pending',
        'rejected'         => 'Rejected',
        default            => ucfirst($txn->status ?? '—'),
    };
@endphp

<!-- Header -->
<div class="header">
    <img src="{{ storage_path('app/public/images/MAin Logo.png') }}" class="logo" alt="Gobaad Bank" />
    <div class="bank-name">Gobaad Bank</div>
    <div class="bank-sub">Official Transaction Receipt</div>
</div>

<!-- Receipt type label -->
<div class="receipt-label">{{ $typeLabel }}</div>

<!-- Amount box -->
<div class="amount-box">
    <div class="amount-label">Amount</div>
    <div class="amount-value">${{ number_format($txn->amount, 2) }}</div>
    <div class="amount-type {{ $txn->type === 'deposit' ? 'cr' : 'dr' }}">
        {{ $txn->type === 'deposit' ? '▲ Credit' : '▼ Debit' }}
    </div>
</div>

<!-- Status -->
<div style="text-align:center; padding: 3px 0 6px;">
    <span class="status-badge {{ $statusClass }}">{{ $statusLabel }}</span>
</div>

<div class="dashed"></div>

<!-- Transaction details -->
<div class="field-row">
    <span class="field-label">Reference</span>
    <span class="field-value mono">{{ $txn->reference }}</span>
</div>
<div class="field-row">
    <span class="field-label">Date & Time</span>
    <span class="field-value mono">{{ \Carbon\Carbon::parse($txn->created_at)->format('d M Y  H:i') }}</span>
</div>
<div class="field-row">
    <span class="field-label">Type</span>
    <span class="field-value">{{ $typeLabel }}</span>
</div>

<div class="dashed"></div>

<!-- Account details -->
<div class="field-row">
    <span class="field-label">Customer</span>
    <span class="field-value">{{ $customerName }}</span>
</div>
<div class="field-row">
    <span class="field-label">Account</span>
    <span class="field-value mono">{{ $accountNumber }}</span>
</div>

@if($txn->type === 'transfer' && $toAcc)
<div class="field-row">
    <span class="field-label">To Customer</span>
    <span class="field-value">{{ $toAcc->customer?->full_name ?? '—' }}</span>
</div>
<div class="field-row">
    <span class="field-label">To Account</span>
    <span class="field-value mono">{{ $toAcc->account_number }}</span>
</div>
@endif

@if($txn->description)
<div class="field-row">
    <span class="field-label">Note</span>
    <span class="field-value">{{ $txn->description }}</span>
</div>
@endif

@if($isRejected && $txn->rejection_reason)
<div class="field-row" style="background:#fde8e8;">
    <span class="field-label" style="color:#b32020;">Rejection Reason</span>
    <span class="field-value" style="color:#b32020;">{{ $txn->rejection_reason }}</span>
</div>
@endif

<div class="dashed"></div>

<!-- Branch & teller -->
<div class="field-row">
    <span class="field-label">Branch</span>
    <span class="field-value">{{ $txn->initiator?->branch?->branch_name ?? '—' }}</span>
</div>
<div class="field-row">
    <span class="field-label">Printed</span>
    <span class="field-value mono">{{ $printDate }} {{ $printTime }}</span>
</div>
<div class="field-row">
    <span class="field-label">Printed by</span>
    <span class="field-value">{{ $staff->full_name }}</span>
</div>

<!-- Served by -->
<div class="served-by">
    Served by: <strong>{{ $txn->initiator?->full_name ?? '—' }}</strong>
    &nbsp;·&nbsp;{{ $txn->initiator?->role?->role_name ?? '—' }}
</div>

<!-- Footer -->
<div class="footer">
    <div class="footer-ref">Ref: {{ $txn->reference }}</div>
    <div class="footer-note">Please retain this receipt for your records.</div>
    <div class="footer-note">Gobaad Bank · All amounts in USD</div>
</div>

</body>
</html>
