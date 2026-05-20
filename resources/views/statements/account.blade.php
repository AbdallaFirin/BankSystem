<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<title>Account Statement — {{ $account->account_number }}</title>
<style>
@page { margin: 10mm 12mm; }

* { margin: 0; padding: 0; box-sizing: border-box; }

body {
    font-family: DejaVu Sans, Arial, sans-serif;
    font-size: 10px;
    color: #1a1a1a;
    background: #ffffff;
    width: 100%;
}

.page { background: #ffffff; width: 100%; }

/* ── Field label / value pairs ── */
.fl { font-size: 9px; color: #555555; white-space: nowrap; padding-right: 6px; vertical-align: bottom; }
.fv { font-size: 10px; font-weight: bold; color: #1a1a1a; border-bottom: 1px solid #d0d0d0;
      font-family: "Courier New", monospace; padding-bottom: 1px; min-width: 110px; }

.il { font-size: 9px; color: #555555; white-space: nowrap; padding-right: 6px; vertical-align: bottom; }
.iv { font-size: 10px; font-weight: bold; color: #1a1a1a; border-bottom: 1px solid #d0d0d0;
      font-family: "Courier New", monospace; padding-bottom: 1px; min-width: 120px; }

/* ── Dividers & label ── */
.rule-thin  { height: 1px;  background: #d0d0d0; margin: 0 24px; }
.rule-thick { height: 3px;  background: #0F1C2E; margin: 0 24px; }
.stmt-label {
    text-align: center; padding: 6px 24px;
    font-size: 11px; font-weight: bold; color: #1a1a1a;
    letter-spacing: 1px; text-transform: uppercase;
    border-bottom: 1px solid #d0d0d0;
}

/* ── Transaction table ── */
table.txn { width: 100%; border-collapse: collapse; table-layout: fixed; }
table.txn thead th {
    background: #0F1C2E; color: #E6F1FB;
    font-size: 9px; font-weight: bold;
    padding: 8px 6px; text-align: left;
    letter-spacing: 0.5px;
}
table.txn thead th.r { text-align: right; }

table.txn tbody td {
    padding: 6px 6px;
    border-bottom: 1px solid #e8e8e8;
    font-size: 9px;
    color: #1a1a1a;
    vertical-align: top;
}
table.txn tbody tr:nth-child(even) td { background: #fafafa; }
table.txn tbody td.mono { font-family: "Courier New", monospace; font-size: 8.5px; }
table.txn tbody td.dim  { color: #888888; font-size: 8.5px; font-family: "Courier New", monospace; }
table.txn tbody td.r    { text-align: right; font-family: "Courier New", monospace; }
table.txn tbody td.cr   { color: #1a7a45; font-weight: bold; text-align: right; font-family: "Courier New", monospace; }
table.txn tbody td.dr   { color: #b32020; font-weight: bold; text-align: right; font-family: "Courier New", monospace; }
table.txn tbody td.dash { color: #aaaaaa; text-align: right; }
table.txn tbody td.bal  { color: #1a1a1a; font-weight: bold; text-align: right; font-family: "Courier New", monospace; }

/* Month divider row */
.mhdr td {
    background: #e8f0f7; color: #0F1C2E;
    font-weight: bold; font-size: 8.5px;
    padding: 5px 6px 5px 20px;
    border-top: 1px solid #b8cfe0;
    border-bottom: 1px solid #b8cfe0;
}

/* Reversed rows */
tr.rev td { color: #b0b8c4; text-decoration: line-through; }
.badge-r {
    display: inline-block; font-size: 7px; font-weight: bold;
    background: #fde8e8; color: #b32020; border: 1px solid #f5c6c3;
    border-radius: 2px; padding: 1px 3px; margin-left: 3px; text-decoration: none;
}
.badge-rs {
    display: inline-block; font-size: 7px; font-weight: bold;
    background: #e8eaf8; color: #3a4ab5; border: 1px solid #c0c8f0;
    border-radius: 2px; padding: 1px 3px; margin-left: 3px; text-decoration: none;
}

/* ── Totals bar ── */
.tl { font-size: 8px; color: #555555; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 2px; }
.tv { font-size: 11px; font-weight: bold; color: #1a1a1a; font-family: "Courier New", monospace; }
.tv.cr { color: #1a7a45; }
.tv.dr { color: #b32020; }

/* ── Signature block ── */
.slbl { font-size: 8px; color: #555555; text-transform: uppercase; letter-spacing: 1px; }
.snam { font-size: 10px; font-weight: bold; color: #1a1a1a; margin-top: 3px; }
.srol { font-size: 9px;  color: #555555; margin-top: 1px; }
.scurs { font-family: "DejaVu Serif", serif; font-style: italic; font-size: 12px; color: #666666; }
.sdat { font-size: 8px; color: #888888; margin-top: 3px; }

/* ── Notice ── */
.notice {
    margin: 0 24px 20px;
    padding: 14px 16px;
    background: #f2f4f7;
    border-left: 3px solid #1D6FA4;
    font-size: 9px; color: #555555;
    line-height: 1.7;
}

.no-data { text-align: center; padding: 40px; color: #888888; font-size: 11px; }
</style>
</head>
<body>

@php
    $branchName = $account->homeBranch->branch_name ?? 'Main Branch';
    $printDate  = now()->format('d M Y');
    $printTime  = now()->format('H:i');

    // Officer & manager come from the SERVING branch (where customer walked in)
    $officerName = $customerOfficer?->full_name ?? '';
    $officerRole = $customerOfficer?->role?->role_name ?? 'Customer Relations Officer';

    $managerName = $branchManager?->full_name ?? '';
    $managerRole = ($branchManager?->role?->role_name ?? 'Branch Manager')
                   . ' — ' . $servingBranchName;

    // Printed-by note (the staff who clicked the button — for audit footer)
    $printedByNote = $printedBy
        ? 'Printed by: ' . $printedBy->full_name . ' (' . ($printedBy->role?->role_name ?? '—') . ')'
        : '';
@endphp

<div class="page">

{{-- ══════════════════════════════════════════
     TOP META  (3-column: left | logo | mirror)
     ══════════════════════════════════════════ --}}
<table width="100%" style="border-collapse:collapse;">
    <tr>
        {{-- Left: date / time / branch --}}
        <td width="33%" valign="top" style="padding: 14px 0 10px 24px;">
            <table style="border-collapse:collapse;">
                <tr>
                    <td class="fl" style="padding-bottom:5px;">Date:</td>
                    <td class="fv" style="padding-bottom:5px;">{{ $printDate }}</td>
                </tr>
                <tr>
                    <td class="fl" style="padding-bottom:5px;">Time:</td>
                    <td class="fv" style="padding-bottom:5px;">{{ $printTime }}</td>
                </tr>
                <tr>
                    <td class="fl">Branch:</td>
                    <td class="fv">{{ $branchName }}</td>
                </tr>
            </table>
        </td>

        {{-- Centre: bank logo image --}}
        <td width="34%" align="center" valign="middle" style="padding: 8px 0;">
            <img src="{{ public_path('images/MAin Logo.png') }}"
                 alt="Gobaad Bank"
                 style="height:50px; width:auto; display:block; margin:0 auto 4px auto;" />
            <div style="text-align:center; font-size:9px; color:#555555;">Official account statement</div>
        </td>

        {{-- Right: invisible mirror (keeps logo centred) --}}
        <td width="33%" valign="top" style="padding: 14px 24px 10px 0;">
            <table style="border-collapse:collapse;">
                <tr>
                    <td style="font-size:9px; color:#ffffff; padding-right:6px; padding-bottom:5px;">Date:</td>
                    <td style="font-size:10px; font-family:'Courier New',monospace; border-bottom:1px solid #ffffff;
                                min-width:110px; color:#ffffff; padding-bottom:5px;">placeholder</td>
                </tr>
                <tr>
                    <td style="font-size:9px; color:#ffffff; padding-right:6px; padding-bottom:5px;">Time:</td>
                    <td style="font-size:10px; font-family:'Courier New',monospace; border-bottom:1px solid #ffffff;
                                min-width:110px; color:#ffffff; padding-bottom:5px;">placeholder</td>
                </tr>
                <tr>
                    <td style="font-size:9px; color:#ffffff; padding-right:6px;">Branch:</td>
                    <td style="font-size:10px; font-family:'Courier New',monospace; border-bottom:1px solid #ffffff;
                                min-width:110px; color:#ffffff;">placeholder</td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<div class="rule-thin"></div>
<div class="rule-thick"></div>
<div class="stmt-label">Account Statement</div>

{{-- ══════════════════════════════════════════
     INFO GRID  (2 columns)
     ══════════════════════════════════════════ --}}
<table width="100%" style="border-collapse:collapse; border-bottom:1px solid #d0d0d0;">
    <tr>
        <td width="50%" valign="top" style="padding: 10px 14px 10px 24px; border-right:1px solid #d0d0d0;">
            <table style="border-collapse:collapse; width:100%;">
                <tr>
                    <td class="il" style="padding-bottom:6px;">From date:</td>
                    <td class="iv" style="padding-bottom:6px;">{{ $dateFrom->format('d M Y') }}</td>
                </tr>
                <tr>
                    <td class="il" style="padding-bottom:6px;">Acc number:</td>
                    <td class="iv" style="padding-bottom:6px;">{{ $account->account_number }}</td>
                </tr>
                <tr>
                    <td class="il">Acc name:</td>
                    <td class="iv">{{ $account->customer->full_name }}</td>
                </tr>
            </table>
        </td>
        <td width="50%" valign="top" style="padding: 10px 24px 10px 14px;">
            <table style="border-collapse:collapse; width:100%;">
                <tr>
                    <td class="il" style="padding-bottom:6px;">To date:</td>
                    <td class="iv" style="padding-bottom:6px;">{{ $dateTo->format('d M Y') }}</td>
                </tr>
                <tr>
                    <td class="il" style="padding-bottom:6px;">Acc type:</td>
                    <td class="iv" style="padding-bottom:6px;">{{ $account->accountType->type_name ?? '—' }}</td>
                </tr>
                <tr>
                    <td class="il">Currency:</td>
                    <td class="iv">USD</td>
                </tr>
            </table>
        </td>
    </tr>
</table>

{{-- ══════════════════════════════════════════
     CUSTOMER NAME BAR
     ══════════════════════════════════════════ --}}
<table width="100%" style="border-collapse:collapse; border-bottom:1px solid #d0d0d0; background:#fafafa;">
    <tr>
        <td style="padding: 7px 24px;">
            <span style="font-size:9px; color:#555555;">Customer name:</span>
            <span style="font-size:12px; font-weight:bold; color:#1a1a1a; margin-left:6px;">{{ $account->customer->full_name }}</span>
        </td>
    </tr>
</table>

{{-- ══════════════════════════════════════════
     TRANSACTION TABLE
     ══════════════════════════════════════════ --}}
@if($entries->count() > 0)
<table class="txn">
    <colgroup>
        <col style="width:13%;">{{-- Trans date   --}}
        <col style="width:13%;">{{-- Trans no.    --}}
        <col style="width:27%;">{{-- Description  --}}
        <col style="width:12%;">{{-- Trans branch --}}
        <col style="width:12%;">{{-- Deposit      --}}
        <col style="width:12%;">{{-- Withdrawal   --}}
        <col style="width:11%;">{{-- Balance      --}}
    </colgroup>
    <thead>
        <tr>
            <th style="padding-left:20px;">Trans date</th>
            <th>Trans no.</th>
            <th>Description</th>
            <th>Trans branch</th>
            <th class="r">Deposit</th>
            <th class="r">Withdrawal</th>
            <th class="r" style="padding-right:20px;">Balance after</th>
        </tr>
    </thead>
    <tbody>
        @php $rowNum = 0; @endphp

        @foreach($grouped as $monthKey => $monthEntries)
            @php
                $monthLabel = \Carbon\Carbon::createFromFormat('Y-m', $monthKey)->format('F Y');
            @endphp

            {{-- Month divider --}}
            <tr class="mhdr">
                <td colspan="7">
                    &#9658; {{ $monthLabel }}
                    &nbsp;&nbsp;<span style="font-weight:normal; font-size:8px; color:#4a6a8a;">{{ $monthEntries->count() }} transaction(s)</span>
                </td>
            </tr>

            @foreach($monthEntries as $entry)
                @php
                    $rowNum++;
                    $txn         = $entry->transaction;
                    $ref         = $txn?->reference ?? '—';
                    $isReversed  = (bool)($txn?->is_reversed ?? false);
                    $isReversal  = !is_null($txn?->reversal_of ?? null);

                    $typeMap = [
                        'deposit'    => 'Cash Deposit',
                        'withdrawal' => 'Cash Withdrawal',
                        'transfer'   => 'Inter-Account Transfer',
                    ];
                    $baseDesc    = $typeMap[$txn?->type ?? ''] ?? ucfirst($txn?->type ?? $entry->entry_type);
                    $description = ($txn?->description ?? '') ?: $baseDesc;
                    $teller      = $txn?->initiator?->full_name ?? '';
                    $txnBranch   = $txn?->initiator?->branch?->branch_name ?? $branchName;
                @endphp

                <tr class="{{ $isReversed ? 'rev' : '' }}">
                    {{-- Trans date --}}
                    <td class="dim" style="padding-left:20px;">
                        {{ \Carbon\Carbon::parse($entry->created_at)->format('d M Y') }}<br>
                        <span style="font-size:7.5px;">{{ \Carbon\Carbon::parse($entry->created_at)->format('H:i') }}</span>
                    </td>

                    {{-- Trans no. --}}
                    <td class="mono">
                        {{ $ref }}
                        @if($isReversed)<span class="badge-r">REVERSED</span>
                        @elseif($isReversal)<span class="badge-rs">REVERSAL</span>
                        @endif
                    </td>

                    {{-- Description --}}
                    <td>
                        <strong style="font-size:9px;">{{ $description }}</strong>
                        @if($teller)
                            <br><span style="font-size:7.5px; color:#888888;">via {{ $teller }}</span>
                        @endif
                    </td>

                    {{-- Trans branch --}}
                    <td style="font-size:8.5px; color:#555555;">{{ $txnBranch }}</td>

                    {{-- Deposit --}}
                    <td class="{{ $entry->entry_type === 'credit' ? 'cr' : 'dash' }}">
                        @if($entry->entry_type === 'credit')
                            ${{ number_format($entry->amount, 2) }}
                        @else
                            &mdash;
                        @endif
                    </td>

                    {{-- Withdrawal --}}
                    <td class="{{ $entry->entry_type === 'debit' ? 'dr' : 'dash' }}">
                        @if($entry->entry_type === 'debit')
                            ${{ number_format($entry->amount, 2) }}
                        @else
                            &mdash;
                        @endif
                    </td>

                    {{-- Balance after --}}
                    <td class="bal" style="padding-right:20px;">
                        ${{ number_format($entry->balance_after ?? 0, 2) }}
                    </td>
                </tr>
            @endforeach
        @endforeach
    </tbody>
</table>
@else
<div class="no-data">No transactions found for this period.</div>
@endif

{{-- ══════════════════════════════════════════
     TOTALS BAR
     ══════════════════════════════════════════ --}}
<table width="100%" style="border-collapse:collapse; border-top:1px solid #d0d0d0; border-bottom:1px solid #d0d0d0;">
    <tr>
        <td style="padding: 8px 16px 8px 24px; font-size:10px; color:#555555;">
            {{ $entries->count() }} transaction(s) in this period
        </td>
        <td style="padding: 7px 10px; border-left:1px solid #d0d0d0; width:80px;">
            <div class="tl">Opening</div>
            <div class="tv">${{ number_format($openingBalance, 2) }}</div>
        </td>
        <td style="padding: 7px 10px; border-left:1px solid #d0d0d0; width:90px;">
            <div class="tl">Total deposit</div>
            <div class="tv cr">+${{ number_format($totalCredits, 2) }}</div>
        </td>
        <td style="padding: 7px 10px; border-left:1px solid #d0d0d0; width:100px;">
            <div class="tl">Total withdrawal</div>
            <div class="tv dr">-${{ number_format($totalDebits, 2) }}</div>
        </td>
        <td style="padding: 7px 14px 7px 10px; border-left:1px solid #d0d0d0; width:90px;">
            <div class="tl">Closing balance</div>
            <div class="tv">${{ number_format($closingBalance, 2) }}</div>
        </td>
    </tr>
</table>

{{-- ══════════════════════════════════════════
     SIGNATURES  (Officer | Stamp | Manager)
     ══════════════════════════════════════════ --}}
<table width="100%" style="border-collapse:collapse;">
    <tr>
        {{-- Left: Customer Relations Officer --}}
        <td width="38%" valign="top" style="padding: 30px 12px 24px 24px;">
            <div class="slbl">Customer Relations Officer</div>
            @if($officerName)
                <div class="snam">{{ $officerName }}</div>
                <div class="srol">{{ $officerRole }}</div>
            @else
                <div class="snam" style="color:#cccccc;">____________________</div>
                <div class="srol">Customer Relations Officer</div>
            @endif
            <table style="border-collapse:collapse; margin-top:12px; width:100%;">
                <tr>
                    <td style="border-bottom:1px solid #d0d0d0; height:36px;"></td>
                </tr>
            </table>
            <div class="sdat">Signature &nbsp;&middot;&nbsp; {{ $printDate }}</div>
        </td>

        {{-- Centre: empty stamp circle — physical bank stamp applied here --}}
        <td width="24%" align="center" valign="middle" style="padding: 20px 0;">
            <svg width="130" height="130" viewBox="0 0 130 130" xmlns="http://www.w3.org/2000/svg">
                {{-- Outer ring --}}
                <circle cx="65" cy="65" r="62" fill="none" stroke="#0F1C2E" stroke-width="2.5"/>
                {{-- Inner ring --}}
                <circle cx="65" cy="65" r="53" fill="none" stroke="#0F1C2E" stroke-width="1"/>
            </svg>
        </td>

        {{-- Right: Branch Manager --}}
        <td width="38%" valign="top" style="padding: 30px 24px 24px 12px; text-align:right;">
            <div class="slbl">Branch Manager</div>
            @if($managerName)
                <div class="snam">{{ $managerName }}</div>
                <div class="srol">{{ $managerRole }}</div>
            @else
                <div class="snam" style="color:#cccccc;">____________________</div>
                <div class="srol">{{ $managerRole }}</div>
            @endif
            <table style="border-collapse:collapse; margin-top:12px; width:100%;">
                <tr>
                    <td style="border-bottom:1px solid #d0d0d0; height:36px;"></td>
                </tr>
            </table>
            <div class="sdat" style="text-align:right;">Signature &nbsp;&middot;&nbsp; {{ $printDate }}</div>
        </td>
    </tr>
</table>

{{-- ══════════════════════════════════════════
     LEGAL NOTICE
     ══════════════════════════════════════════ --}}
<div class="notice">
    This is an official certified statement issued by Gobaad Bank. For queries or discrepancies,
    please contact your branch within 30 days of the statement date. All amounts are in USD.
    Valid only with official bank stamp and authorized signatures.<br><br>
    Gobaad Bank is a regulated financial institution. Unauthorised reproduction or alteration of
    this document is strictly prohibited and may be subject to legal action.
</div>

{{-- ══════════════════════════════════════════
     FOOTER BAR
     ══════════════════════════════════════════ --}}
<div style="background:#0F1C2E; padding: 9px 24px;">
    <table width="100%" style="border-collapse:collapse;">
        <tr>
            <td style="font-size:9px; color:#85B7EB; font-family:'Courier New',monospace;">
                Ref: {{ $stmtRef }} &nbsp;&middot;&nbsp; {{ $account->account_number }} &nbsp;&middot;&nbsp; Confidential
                @if($printedByNote)
                    &nbsp;&middot;&nbsp; {{ $printedByNote }}
                @endif
            </td>
            <td align="right" style="font-size:9px; color:#85B7EB;">
                Gobaad Bank &nbsp;&middot;&nbsp; {{ $branchName }}@if(!empty($account->homeBranch->swift_code)) &nbsp;&middot;&nbsp; SWIFT: {{ $account->homeBranch->swift_code }}@endif
                @if(!empty($account->homeBranch->phone)) &nbsp;&middot;&nbsp; {{ $account->homeBranch->phone }}@endif
            </td>
        </tr>
    </table>
</div>

</div><!-- /.page -->
</body>
</html>
