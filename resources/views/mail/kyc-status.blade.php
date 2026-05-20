@extends('mail.layout')
@php
  $approved  = ($status ?? 'approved') === 'approved';
  $headerTag = $approved ? 'KYC APPROVED' : 'KYC REJECTED';
  $subject   = $subject ?? ($approved ? 'KYC Application Approved' : 'KYC Application Rejected');
@endphp
@section('content')
<p class="greeting">Dear <strong>{{ $customerName }}</strong>,</p>
<p style="font-size:14px;color:#374151;line-height:1.6;margin-bottom:4px">
  @if($approved)
    Your identity verification (KYC) has been <strong style="color:#065f46">approved</strong>.
    Your account is now fully activated and ready to use.
  @else
    Your identity verification (KYC) application has been <strong style="color:#991b1b">rejected</strong>.
    Please visit your nearest branch to resubmit the required documents.
  @endif
</p>

<div class="card">
  <div class="row"><span class="lbl">Customer ID</span><span class="val">{{ $customerId }}</span></div>
  <div class="row"><span class="lbl">Full Name</span><span class="val">{{ $customerName }}</span></div>
  <div class="row"><span class="lbl">Phone</span><span class="val">{{ $phone }}</span></div>
  <div class="row"><span class="lbl">Decision Date</span><span class="val">{{ $decidedAt }}</span></div>
  @if(!$approved && !empty($rejectReason))
  <div class="row"><span class="lbl">Reason</span><span class="val" style="font-family:Georgia">{{ $rejectReason }}</span></div>
  @endif
</div>

<div style="text-align:center;margin:20px 0">
  <span class="badge {{ $approved ? 'badge-success' : 'badge-danger' }}">
    {{ $approved ? '✓ KYC Approved' : '✗ KYC Rejected' }}
  </span>
</div>

@if($approved && !empty($tempPassword))
<div style="background:#fefce8;border:1px solid #fde68a;border-radius:8px;padding:16px 20px;margin:16px 0">
  <p style="font-size:12px;font-weight:700;color:#92400e;text-transform:uppercase;letter-spacing:.06em;margin-bottom:8px">
    Temporary Login Password
  </p>
  <p style="font-family:'Courier New',monospace;font-size:20px;font-weight:700;color:#1a2233;letter-spacing:.12em;text-align:center;margin:8px 0">
    {{ $tempPassword }}
  </p>
  <p style="font-size:11px;color:#78350f;margin-top:8px">
    Use your registered phone number and this temporary password to log in to the customer portal.
    You will be required to change this password immediately upon first login.
  </p>
</div>
@endif
@endsection
