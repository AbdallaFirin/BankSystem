<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Staff Invite — Gobaad Bank</title>
<style>
  body { font-family: Arial, sans-serif; background: #f4f6f8; margin: 0; padding: 0; }
  .wrapper { max-width: 580px; margin: 40px auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
  .header  { background: #0B1929; padding: 32px 40px; text-align: center; }
  .header img { height: 48px; width: auto; }
  .header h1  { color: #C9A84C; font-size: 20px; margin: 12px 0 0; letter-spacing: 1px; }
  .body    { padding: 36px 40px; color: #333; }
  .body h2 { color: #0B1929; font-size: 18px; margin-top: 0; }
  .body p  { line-height: 1.7; font-size: 15px; color: #555; }
  .info-box { background: #f2f4f7; border-left: 4px solid #C9A84C; padding: 14px 18px; border-radius: 4px; margin: 20px 0; }
  .info-box p { margin: 4px 0; font-size: 14px; color: #444; }
  .info-box strong { color: #0B1929; }
  .btn-wrap { text-align: center; margin: 32px 0; }
  .btn { display: inline-block; background: #C9A84C; color: #0B1929; font-weight: bold; font-size: 15px;
         padding: 14px 36px; border-radius: 6px; text-decoration: none; letter-spacing: 0.5px; }
  .btn:hover { background: #b8973d; }
  .note { font-size: 12px; color: #999; text-align: center; margin-top: 8px; }
  .footer { background: #0B1929; padding: 18px 40px; text-align: center; }
  .footer p { color: #7a9ab5; font-size: 12px; margin: 0; }
</style>
</head>
<body>
<div class="wrapper">

  <div class="header">
    <img src="{{ asset('images/MAin Logo.png') }}" alt="Gobaad Bank" />
    <h1>GOBAAD BANK</h1>
  </div>

  <div class="body">
    <h2>Welcome to Gobaad Bank Staff Portal</h2>
    <p>Hello <strong>{{ $staff->full_name }}</strong>,</p>
    <p>
      You have been registered as a staff member at Gobaad Bank.
      Click the button below to accept your invitation and set up your password.
      Your account will remain <strong>inactive</strong> until you accept.
    </p>

    <div class="info-box">
      <p><strong>Staff ID:</strong> {{ $staff->ident_number }}</p>
      <p><strong>Role:</strong> {{ $staff->role?->role_name ?? '—' }}</p>
      <p><strong>Branch:</strong> {{ $staff->branch?->branch_name ?? '—' }}</p>
    </div>

    <div class="btn-wrap">
      <a href="{{ $acceptUrl }}" class="btn">Accept Invitation &amp; Set Password</a>
    </div>
    <p class="note">This link expires in 48 hours. If you did not expect this email, please ignore it.</p>

    <p style="font-size:13px; color:#aaa; word-break:break-all;">
      Or copy this link: {{ $acceptUrl }}
    </p>
  </div>

  <div class="footer">
    <p>Gobaad Bank &mdash; Secure Staff Portal &mdash; Do not share this link with anyone.</p>
  </div>

</div>
</body>
</html>
