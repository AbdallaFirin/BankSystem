@extends('mail.layout')
@php
  $frozen    = ($action ?? 'freeze') === 'freeze';
  $headerTag = $frozen ? 'ACCOUNT FROZEN' : 'ACCOUNT UNFROZEN';
  $subject   = $subject ?? ($frozen ? 'Your Account Has Been Frozen' : 'Your Account Has Been Unfrozen');
@endphp
@section('content')
<p class="greeting">Dear <strong>{{ $customerName }}</strong>,</p>
<p style="font-size:14px;color:#374151;line-height:1.6;margin-bottom:4px">
  @if($frozen)
    Your account has been <strong style="color:#991b1b">frozen</strong> by our Customer Care team.
    All transactions on this account are suspended until further notice.
  @else
    Your account has been <strong style="color:#065f46">unfrozen</strong> by our Customer Care team.
    All banking services on this account have been restored.
  @endif
</p>

<div class="card">
  <div class="row"><span class="lbl">Account Number</span><span class="val">{{ $accountNumber }}</span></div>
  <div class="row"><span class="lbl">Account Type</span><span class="val">{{ $accountType }}</span></div>
  <div class="row"><span class="lbl">Action</span>
    <span class="val">
      <span class="badge {{ $frozen ? 'badge-danger' : 'badge-success' }}">
        {{ $frozen ? 'Frozen' : 'Unfrozen' }}
      </span>
    </span>
  </div>
  <div class="row"><span class="lbl">Date</span><span class="val">{{ $actionAt }}</span></div>
  @if($frozen && !empty($reason))
  <div class="row"><span class="lbl">Reason</span><span class="val" style="font-family:Georgia">{{ $reason }}</span></div>
  @endif
  @if(!$frozen && !empty($notes))
  <div class="row"><span class="lbl">Notes</span><span class="val" style="font-family:Georgia">{{ $notes }}</span></div>
  @endif
</div>

@if($frozen)
<p class="note" style="color:#991b1b;margin-top:8px">
  If you believe this action was taken in error, please contact your nearest Gobaad Bank branch immediately with a valid ID.
</p>
@endif
@endsection
