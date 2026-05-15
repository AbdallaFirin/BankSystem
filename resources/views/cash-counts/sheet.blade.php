<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<title>Cash Count Sheet — #{{ $count->id }}</title>
<style>
@page { margin: 12mm 14mm; }
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 10px; color: #1a1a1a; background: #fff; }

/* ── Header ── */
.hdr { border-bottom: 3px solid #0F1C2E; padding-bottom: 10px; margin-bottom: 14px; }
.hdr-inner { width: 100%; border-collapse: collapse; }
.hdr-inner td { vertical-align: middle; }
.bank-name  { font-size: 18px; font-weight: bold; color: #0F1C2E; }
.bank-sub   { font-size: 9px; color: #555; margin-top: 2px; letter-spacing: 0.5px; }
.doc-title  { font-size: 14px; font-weight: bold; color: #0F1C2E; text-align: right; }
.doc-sub    { font-size: 9px; color: #555; text-align: right; margin-top: 2px; }

/* ── Status badge ── */
.badge {
    display: inline-block; font-size: 9px; font-weight: bold; text-transform: uppercase;
    letter-spacing: 0.5px; padding: 2px 8px; border-radius: 3px;
}
.badge.balanced { background: #d4f4e4; color: #1a7a45; border: 1px solid #aaddc4; }
.badge.overage  { background: #fef3d0; color: #9a6200; border: 1px solid #f0d080; }
.badge.shortage { background: #fde8e8; color: #b32020; border: 1px solid #f5c6c3; }

/* ── Meta info table ── */
.meta { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
.meta td { padding: 5px 10px; font-size: 10px; border-bottom: 1px solid #eee; }
.meta td.lbl { color: #555; width: 130px; }
.meta td.val { font-weight: bold; color: #1a1a1a; }
.meta tr:last-child td { border-bottom: none; }

/* ── Denomination table ── */
table.denom { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
table.denom thead th {
    background: #0F1C2E; color: #E6F1FB;
    font-size: 9px; font-weight: bold;
    padding: 7px 10px; text-align: left; letter-spacing: 0.5px;
}
table.denom thead th.r { text-align: right; }
table.denom thead th:first-child { padding-left: 16px; }
table.denom thead th:last-child  { padding-right: 16px; }
table.denom tbody td {
    padding: 6px 10px; font-size: 10px;
    border-bottom: 1px solid #eeeeee; color: #1a1a1a;
}
table.denom tbody td:first-child { padding-left: 16px; }
table.denom tbody td:last-child  { padding-right: 16px; text-align: right; font-family: "Courier New", monospace; font-weight: bold; }
table.denom tbody td.r { text-align: right; font-family: "Courier New", monospace; }
table.denom tbody tr:nth-child(even) td { background: #fafafa; }
table.denom tfoot td {
    background: #f0f4f8; padding: 7px 10px; font-size: 10px; font-weight: bold;
    border-top: 2px solid #0F1C2E; color: #1a1a1a;
}
table.denom tfoot td:first-child { padding-left: 16px; }
table.denom tfoot td:last-child  { padding-right: 16px; text-align: right; font-family: "Courier New", monospace; font-size: 12px; }

/* ── Reconciliation box ── */
.recon { width: 55%; margin-left: 45%; border-collapse: collapse; margin-bottom: 18px; border: 1px solid #ddd; }
.recon td { padding: 6px 12px; font-size: 10px; border-bottom: 1px solid #eee; }
.recon td.lbl { color: #555; }
.recon td.val { text-align: right; font-family: "Courier New", monospace; font-weight: bold; }
.recon tr.diff td { border-top: 2px solid #0F1C2E; border-bottom: none; }
.diff-balanced { color: #1a7a45; }
.diff-overage  { color: #9a6200; }
.diff-shortage { color: #b32020; }

/* ── Notes ── */
.notes {
    margin-bottom: 20px; padding: 10px 14px;
    background: #f2f4f7; border-left: 3px solid #1D6FA4;
    font-size: 9px; color: #555; line-height: 1.6;
}

/* ── Signature block ── */
.sig-table { width: 100%; border-collapse: collapse; margin-top: 22px; }
.sig-table td { width: 50%; padding: 0 10px; vertical-align: top; }
.sig-table td:first-child { padding-left: 0; }
.sig-table td:last-child  { padding-right: 0; text-align: right; }
.sig-lbl  { font-size: 8px; color: #888; text-transform: uppercase; letter-spacing: 1px; }
.sig-name { font-size: 10px; font-weight: bold; color: #1a1a1a; margin-top: 3px; }
.sig-role { font-size: 9px; color: #555; margin-top: 1px; }
.sig-line-wrap { margin-top: 14px; border-bottom: 1px solid #bbb; height: 28px; }
.sig-date { font-size: 8px; color: #888; margin-top: 3px; }

/* ── Footer ── */
.footer { background: #0F1C2E; padding: 8px 0; margin-top: 20px; }
.footer table { width: 100%; border-collapse: collapse; }
.footer td { font-size: 9px; color: #85B7EB; padding: 0 4px; }
.footer td.right { text-align: right; }
</style>
</head>
<body>

@php
    $statusColor = match($count->status) {
        'balanced' => '#1a7a45',
        'overage'  => '#9a6200',
        default    => '#b32020',
    };
    $diffSign  = $count->difference >= 0 ? '+' : '';
    $diffClass = 'diff-' . $count->status;
    $printDate = now()->format('d M Y');
@endphp

{{-- ── Header ── --}}
<div class="hdr">
    <table class="hdr-inner">
        <tr>
            <td>
                <div class="bank-name">Gobaad Bank</div>
                <div class="bank-sub">Cash Count Sheet</div>
            </td>
            <td style="text-align:right;">
                <div class="doc-title">Cash Count #{{ $count->id }}</div>
                <div class="doc-sub">
                    {{ ucfirst($count->session_type) }} Count &nbsp;&bull;&nbsp;
                    {{ $count->counted_at->format('d M Y, H:i') }}
                </div>
            </td>
        </tr>
    </table>
</div>

{{-- ── Meta info ── --}}
<table class="meta">
    <tr>
        <td class="lbl">Status</td>
        <td class="val">
            <span class="badge {{ $count->status }}">{{ strtoupper($count->status) }}</span>
        </td>
    </tr>
    <tr>
        <td class="lbl">Session Type</td>
        <td class="val">{{ ucfirst($count->session_type) }} Count</td>
    </tr>
    <tr>
        <td class="lbl">Date &amp; Time</td>
        <td class="val">{{ $count->counted_at->format('d M Y, H:i:s') }}</td>
    </tr>
    <tr>
        <td class="lbl">Teller</td>
        <td class="val">{{ $count->staff?->full_name ?? '—' }}</td>
    </tr>
    <tr>
        <td class="lbl">Branch</td>
        <td class="val">{{ $count->branch?->branch_name ?? '—' }}</td>
    </tr>
</table>

{{-- ── Denomination table ── --}}
<table class="denom">
    <thead>
        <tr>
            <th style="width:35%">Denomination</th>
            <th style="width:20%">Type</th>
            <th class="r" style="width:20%">Quantity</th>
            <th class="r" style="width:25%">Subtotal</th>
        </tr>
    </thead>
    <tbody>
        @forelse($count->denominations as $d)
            @php
                $isNote   = $d->denomination >= 1;
                $label    = $isNote
                    ? '$' . (fmod($d->denomination, 1) == 0 ? (int)$d->denomination : $d->denomination) . ' Bill'
                    : round($d->denomination * 100) . '¢ Coin';
                $typeLabel = $isNote ? 'Bill' : 'Coin';
            @endphp
            <tr>
                <td>{{ $label }}</td>
                <td style="color:#555;">{{ $typeLabel }}</td>
                <td class="r">{{ number_format($d->quantity) }}</td>
                <td>${{ number_format($d->subtotal, 2) }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="4" style="text-align:center; color:#888; padding:16px;">
                    No denominations recorded.
                </td>
            </tr>
        @endforelse
    </tbody>
    <tfoot>
        <tr>
            <td colspan="3">Physical Total</td>
            <td>${{ number_format($count->physical_total, 2) }}</td>
        </tr>
    </tfoot>
</table>

{{-- ── Reconciliation ── --}}
<table class="recon">
    <tr>
        <td class="lbl">Physical Count</td>
        <td class="val">${{ number_format($count->physical_total, 2) }}</td>
    </tr>
    <tr>
        <td class="lbl">System / Vault Balance</td>
        <td class="val">${{ number_format($count->system_balance, 2) }}</td>
    </tr>
    <tr class="diff">
        <td class="lbl {{ $diffClass }}" style="font-weight:bold;">Difference</td>
        <td class="val {{ $diffClass }}">
            {{ $diffSign }}${{ number_format(abs($count->difference), 2) }}
        </td>
    </tr>
</table>

{{-- ── Notes ── --}}
@if($count->notes)
<div class="notes">
    <strong>Notes:</strong> {{ $count->notes }}
</div>
@endif

{{-- ── Signature block ── --}}
<table class="sig-table">
    <tr>
        <td>
            <div class="sig-lbl">Counted by (Teller)</div>
            <div class="sig-name">{{ $count->staff?->full_name ?? '____________________' }}</div>
            <div class="sig-role">{{ $count->staff?->role?->role_name ?? 'Bank Teller' }}</div>
            <div class="sig-line-wrap"></div>
            <div class="sig-date">Signature &nbsp;&middot;&nbsp; {{ $printDate }}</div>
        </td>
        <td>
            <div class="sig-lbl">Verified by (Supervisor)</div>
            <div class="sig-name" style="color:#ccc;">____________________</div>
            <div class="sig-role">Branch Supervisor / Manager</div>
            <div class="sig-line-wrap"></div>
            <div class="sig-date" style="text-align:right;">Signature &nbsp;&middot;&nbsp; {{ $printDate }}</div>
        </td>
    </tr>
</table>

{{-- ── Footer ── --}}
<div class="footer">
    <table>
        <tr>
            <td>
                Cash Count #{{ $count->id }} &nbsp;&bull;&nbsp;
                {{ $count->branch?->branch_name ?? '' }} &nbsp;&bull;&nbsp; Confidential
            </td>
            <td class="right">
                Gobaad Bank &nbsp;&bull;&nbsp; {{ $printDate }}
            </td>
        </tr>
    </table>
</div>

</body>
</html>
