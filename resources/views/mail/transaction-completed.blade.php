@extends('mail.layout')
@php
  // Derive a lowercase key from whatever $type string is passed (e.g. "Transfer" → "transfer")
  $typeKey       = strtolower($type ?? 'transfer');
  $headerTag     = strtoupper($type ?? 'Transaction') . ' CONFIRMATION';
  $subject       = $subject ?? 'Transaction Confirmed';
  $colors        = ['deposit' => '#065f46', 'withdrawal' => '#92400e', 'transfer' => '#1e40af'];
  $color         = $colors[$typeKey] ?? '#1e40af';
  $sign          = $typeKey === 'deposit' ? '+' : ($typeKey === 'withdrawal' ? '-' : '');

  // Optional variables — default to null so template doesn't crash
  $accountNumber   = $accountNumber   ?? ($account_number   ?? null);
  $toAccountNumber = $toAccountNumber ?? ($to_account_number ?? null);
  $processedAt     = $processedAt     ?? ($processed_at      ?? null);
  $teller          = $teller          ?? null;
  $remarks         = $remarks         ?? ($description       ?? null);
  $balanceAfter    = $balanceAfter    ?? null;
@endphp
@section('content')
<p class="greeting">Dear <strong>{{ $customerName ?? $customer_name ?? 'Customer' }}</strong>,</p>
<p style="font-size:14px;color:#374151;line-height:1.6;margin-bottom:4px">
  Your <strong>{{ $type }}</strong> has been processed successfully.
</p>

<div class="card">
  @if(!empty($reference))
  <div class="row"><span class="lbl">Reference</span><span class="val">{{ $reference }}</span></div>
  @endif
  @if(!empty($accountNumber))
  <div class="row"><span class="lbl">Account</span><span class="val">{{ $accountNumber }}</span></div>
  @endif
  @if(!empty($toAccountNumber))
  <div class="row"><span class="lbl">To Account</span><span class="val">{{ $toAccountNumber }}</span></div>
  @endif
  @if(!empty($processedAt))
  <div class="row"><span class="lbl">Date &amp; Time</span><span class="val">{{ $processedAt }}</span></div>
  @endif
  @if(!empty($teller))
  <div class="row"><span class="lbl">Processed By</span><span class="val">{{ $teller }}</span></div>
  @endif
  @if(!empty($remarks))
  <div class="row"><span class="lbl">Remarks</span><span class="val" style="font-family:Georgia">{{ $remarks }}</span></div>
  @endif
</div>

<div class="amount" style="color:{{ $color }}">
  {{ $sign }}${{ number_format((float)($amount ?? 0), 2) }}
</div>
@if(!empty($balanceAfter))
<p style="text-align:center;font-size:12px;color:#6b7a8d">Available Balance: <strong style="color:#1a2233">${{ number_format((float)$balanceAfter, 2) }}</strong></p>
@endif
@endsection
