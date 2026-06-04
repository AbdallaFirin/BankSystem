<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ $subject ?? 'Gobaad Bank Notification' }}</title>
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: Georgia, 'Times New Roman', serif; background: #f4f6f9; color: #1a2233; }
  .wrapper { max-width: 580px; margin: 32px auto; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 16px rgba(0,0,0,.08); }
  .header { background: #0B1929; padding: 28px 32px 22px; text-align: center; }
  .header img { height: 48px; margin: 0 auto 10px; display: block; }
  .header h1 { color: #C9A84C; font-size: 20px; letter-spacing: .04em; font-weight: 700; }
  .header p { color: #7a9ab5; font-size: 11px; text-transform: uppercase; letter-spacing: .12em; margin-top: 4px; }
  .body { padding: 32px; }
  .greeting { font-size: 15px; color: #1a2233; margin-bottom: 18px; }
  .card { background: #f8f9fb; border: 1px solid #e4e8ef; border-radius: 8px; padding: 18px 20px; margin: 18px 0; }
  .row { display: flex; justify-content: space-between; align-items: baseline; padding: 7px 0; border-bottom: 1px solid #edf0f4; font-size: 13px; }
  .row:last-child { border-bottom: none; }
  .lbl { color: #6b7a8d; font-size: 11px; }
  .val { font-weight: 600; color: #1a2233; font-family: 'Courier New', monospace; }
  .amount { font-size: 26px; font-weight: 700; text-align: center; margin: 20px 0 8px; }
  .footer { background: #0B1929; padding: 18px 32px; text-align: center; }
  .footer p { color: #4a6070; font-size: 10px; text-transform: uppercase; letter-spacing: .09em; }
  .footer p + p { margin-top: 4px; color: #3a5060; }
  .badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; }
  .badge-success { background: #d1fae5; color: #065f46; }
  .badge-warning { background: #fef3c7; color: #92400e; }
  .badge-danger  { background: #fee2e2; color: #991b1b; }
  .badge-info    { background: #dbeafe; color: #1e40af; }
  .note { font-size: 12px; color: #6b7a8d; line-height: 1.6; margin-top: 16px; }
</style>
</head>
<body>
<div class="wrapper">
  <div class="header">
    @php
      $logoPath = public_path('images/MAin Logo.png');
      $logoSrc  = file_exists($logoPath)
          ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath))
          : '';
    @endphp
    @if($logoSrc)
      <img src="{{ $logoSrc }}" alt="Gobaad Bank" />
    @endif
    <h1>Gobaad Bank</h1>
    <p>{{ $headerTag ?? 'Official Notification' }}</p>
  </div>
  <div class="body">
    @yield('content')
    <p class="note" style="margin-top:24px">
      If you did not initiate this activity or have questions, please contact your nearest Gobaad Bank branch immediately.
    </p>
  </div>
  <div class="footer">
    <p>Gobaad Bank — Secure Banking Notifications</p>
    <p>Do not reply to this email · This is an automated message</p>
  </div>
</div>
</body>
</html>
