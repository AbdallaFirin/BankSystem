@extends('mail.layout')
@php
  $approved  = ($decision ?? 'approved') === 'approved';
  $headerTag = $approved ? 'TRANSACTION APPROVED' : 'TRANSACTION REJECTED';
  $subject   = $subject ?? ($approved ? 'Transaction Approved' : 'Transaction Rejected');
@endphp
@section('content')
<p class="greeting">Dear <strong>{{ $customerName }}</strong>,</p>
<p style="font-size:14px;color:#374151;line-height:1.6;margin-bottom:4px">
  @if($approved)
    Your transaction has been <strong style="color:#065f46">approved</strong> and funds have been processed.
  @else
    Your transaction has been <strong style="color:#991b1b">rejected</strong> by a supervisor.
  @endif
</p>

<div class="card">
  <div class="row"><span class="lbl">Reference</span><span class="val">{{ $reference }}</span></div>
  <div class="row"><span class="lbl">Type</span><span class="val">{{ $type }}</span></div>
  <div class="row"><span class="lbl">Amount</span><span class="val">${{ number_format($amount, 2) }}</span></div>
  <div class="row"><span class="lbl">Account</span><span class="val">{{ $accountNumber }}</span></div>
  <div class="row"><span class="lbl">Decision Date</span><span class="val">{{ $decidedAt }}</span></div>
  @if(!$approved && !empty($decisionNote))
  <div class="row"><span class="lbl">Reason</span><span class="val" style="font-family:Georgia">{{ $decisionNote }}</span></div>
  @endif
</div>

<div style="text-align:center;margin:20px 0">
  <span class="badge {{ $approved ? 'badge-success' : 'badge-danger' }}">
    {{ $approved ? '✓ Approved' : '✗ Rejected' }}
  </span>
</div>
@endsection
