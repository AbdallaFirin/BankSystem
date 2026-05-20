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

/* ── Divider ── */
.dashed { border-top: 1px dashed #cccccc; margin: 5px 0; }

/* ── Remarks ── */
.remarks-box {
    margin: 6px 8px;
    padding: 5px 8px;
    background: #fafafa;
    border: 1px dashed #d8d8d8;
    border-radius: 3px;
}
.remarks-label { font-size: 7.5px; color: #999999; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 2px; }
.remarks-text  { font-size: 8.5px; color: #444444; font-style: italic; }

/* ── Footer ── */
.footer {
    text-align: center;
    padding: 6px 8px 4px;
    border-top: 2px solid #0F1C2E;
    margin-top: 8px;
}
.footer-ref  { font-size: 7px; color: #888888; font-family: "Courier New", monospace; }
.footer-note { font-size: 7px; color: #aaaaaa; margin-top: 3px; }

.info-bar {
    padding: 4px 8px;
    background: #f2f4f7;
    border-top: 1px solid #d0d0d0;
    font-size: 8px;
    color: #555555;
    text-align: center;
}
.info-bar strong { color: #1a1a1a; }
</style>
</head>
<body>

@php
    $txn         = $transaction;
    $isCompleted = in_array($txn->status, ['completed', 'approved']);

    $typeLabels = [
        'deposit'    => 'Cash Deposit',
        'withdrawal' => 'Cash Withdrawal',
        'transfer'   => 'Inter-Account Transfer',
        'interest'   => 'Interest Credit',
    ];
    $typeLabel = $typeLabels[$txn->type] ?? ucfirst($txn->type ?? 'Transaction');

    $fromAcc = $txn->primaryAccount;
    $toAcc   = $txn->secondaryAccount;

    // Determine the customer-facing account number and direction
    $isIncoming = ($txn->type === 'deposit' || $txn->type === 'interest')
               || ($txn->type === 'transfer' && $toAcc && in_array($toAcc->id, array_keys($customer->accounts->keyBy('id')->toArray() ?? [])));

    $displayAccount = match($txn->type) {
        'deposit'    => $toAcc?->account_number   ?? $fromAcc?->account_number ?? '—',
        'withdrawal' => $fromAcc?->account_number ?? '—',
        'transfer'   => $fromAcc?->account_number ?? '—',
        'interest'   => $fromAcc?->account_number ?? '—',
        default      => $fromAcc?->account_number ?? '—',
    };

    $directionClass = in_array($txn->type, ['deposit', 'interest']) ? 'cr' : 'dr';
    $directionLabel = in_array($txn->type, ['deposit', 'interest']) ? '▲ Credit' : '▼ Debit';

    $statusClass = 'status-completed';
    $statusLabel = 'Completed';
    $custReceiptSwift = $txn->initiator?->branch?->swift_code ?? '';
@endphp

<!-- Header -->
<div class="header">
    <img src="{{ public_path('images/MAin Logo.png') }}" class="logo" alt="Gobaad Bank" />
    <div class="bank-name">Gobaad Bank</div>
    <div class="bank-sub">Customer Transaction Receipt</div>
</div>

<!-- Receipt type label -->
<div class="receipt-label">{{ $typeLabel }}</div>

<!-- Amount box -->
<div class="amount-box">
    <div class="amount-label">Amount</div>
    <div class="amount-value">${{ number_format($txn->amount, 2) }}</div>
    <div class="amount-type {{ $directionClass }}">{{ $directionLabel }}</div>
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
    <span class="field-label">Date &amp; Time</span>
    <span class="field-value mono">{{ \Carbon\Carbon::parse($txn->created_at)->format('d M Y  H:i') }}</span>
</div>
<div class="field-row">
    <span class="field-label">Type</span>
    <span class="field-value">{{ $typeLabel }}</span>
</div>

<div class="dashed"></div>

<!-- Account details -->
<div class="field-row">
    <span class="field-label">Account Holder</span>
    <span class="field-value">{{ $customer->full_name }}</span>
</div>
<div class="field-row">
    <span class="field-label">Account Number</span>
    <span class="field-value mono">{{ $displayAccount }}</span>
</div>

@if($txn->type === 'transfer')
    @if($toAcc)
    <div class="field-row">
        <span class="field-label">To Account</span>
        <span class="field-value mono">{{ $toAcc->account_number }}</span>
    </div>
    <div class="field-row">
        <span class="field-label">Beneficiary</span>
        <span class="field-value">{{ $toAcc->customer?->full_name ?? '—' }}</span>
    </div>
    @endif
@endif

@if($txn->description && !in_array($txn->description, ['Cash Deposit','Cash Withdrawal','Inter-Account Transfer','Deposit','Withdrawal']))
<div class="remarks-box">
    <div class="remarks-label">Remarks</div>
    <div class="remarks-text">{{ $txn->description }}</div>
</div>
@endif

<div class="dashed"></div>

<!-- Meta -->
<div class="field-row">
    <span class="field-label">Branch</span>
    <span class="field-value">{{ $txn->initiator?->branch?->branch_name ?? 'Gobaad Bank' }}</span>
</div>
@if($custReceiptSwift)
<div class="field-row">
    <span class="field-label">SWIFT Code</span>
    <span class="field-value mono">{{ $custReceiptSwift }}</span>
</div>
@endif
@if($txn->initiator)
<div class="field-row">
    <span class="field-label">Processed by</span>
    <span class="field-value">{{ $txn->initiator->full_name }}</span>
</div>
@endif
<div class="field-row">
    <span class="field-label">Downloaded</span>
    <span class="field-value mono">{{ $printDate }} {{ $printTime }}</span>
</div>

<!-- Info bar -->
<div class="info-bar">
    Downloaded by: <strong>{{ $customer->full_name }}</strong> via Customer Portal
</div>

<!-- Footer -->
<div class="footer">
    <div class="footer-ref">Ref: {{ $txn->reference }}</div>
    <div class="footer-note">Please retain this receipt for your records.</div>
    <div class="footer-note">Gobaad Bank · All amounts in USD{{ $custReceiptSwift ? ' · SWIFT: '.$custReceiptSwift : '' }}</div>
</div>

</body>
</html>
