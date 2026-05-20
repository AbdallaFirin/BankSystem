@extends('mail.layout')

@section('content')

<p class="greeting" style="margin-bottom:8px;">
  <span style="display:inline-block;background:#0B1929;color:#C9A84C;font-size:10px;font-weight:700;letter-spacing:2px;text-transform:uppercase;padding:4px 12px;border-radius:4px;">
    {{ strtoupper($headerTag ?? 'Staff Notification') }}
  </span>
</p>

<p class="greeting">{{ $greeting ?? 'Hello,' }}</p>

<p style="font-size:15px; color:#4a5568; line-height:1.7; margin-bottom:20px;">
  {!! nl2br(e($body ?? '')) !!}
</p>

@if(!empty($details))
<div class="card">
  @foreach($details as $label => $value)
  <div class="row">
    <span class="lbl">{{ $label }}</span>
    <span class="val">{{ $value }}</span>
  </div>
  @endforeach
</div>
@endif

@if(!empty($actionNote))
<div style="background:#FFF3CD; border:1px solid #FFC107; border-radius:8px; padding:14px 18px; margin-top:16px;">
  <p style="margin:0; font-size:13px; color:#856404; font-family:Georgia,serif;">
    <strong>Action Required:</strong> {{ $actionNote }}
  </p>
</div>
@endif

@endsection
