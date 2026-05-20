@extends('mail.layout')
@php
  $isPwReset  = ($purpose ?? 'reset') === 'reset';
  $headerTag  = $isPwReset ? 'PASSWORD RESET CODE' : 'LOGIN VERIFICATION CODE';
  $subject    = $isPwReset ? 'Reset Your Password' : 'Your Login Verification Code';
@endphp
@section('content')
<p class="greeting">Dear <strong>{{ $name ?? 'Customer' }}</strong>,</p>
<p style="font-size:14px;color:#374151;line-height:1.6;margin-bottom:4px">
  @if($isPwReset)
    We received a request to reset the password for your Gobaad Bank account.
  @else
    A verification code is required to complete your login.
  @endif
</p>

<div style="text-align:center;margin:28px 0;">
  <p style="font-size:12px;color:#6b7a8d;margin-bottom:10px;text-transform:uppercase;letter-spacing:.08em;">Your one-time code</p>
  <div style="display:inline-block;background:#0B1929;color:#C9A84C;font-size:36px;font-weight:700;letter-spacing:.25em;padding:16px 32px;border-radius:12px;font-family:'Courier New',monospace;">
    {{ $code }}
  </div>
  <p style="font-size:11px;color:#6b7a8d;margin-top:12px;">
    This code expires in <strong>10 minutes</strong>.
  </p>
</div>

<div class="card" style="background:#fff8e7;border-color:#f3d87a;">
  <p style="font-size:12px;color:#92400e;line-height:1.6;">
    <strong>⚠ Security Notice:</strong>
    Never share this code with anyone. Gobaad Bank staff will <strong>never</strong> ask for this code.
    If you did not request this, please ignore this email and your account will remain secure.
  </p>
</div>
@endsection