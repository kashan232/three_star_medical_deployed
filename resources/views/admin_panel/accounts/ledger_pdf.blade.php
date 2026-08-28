<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>General Ledger - {{ $account->title }} ({{ $account->account_code }})</title>
    <style>
        @page {
            margin: 25px 25px 35px 25px;
        }
        body {
            font-family: DejaVu Sans, Arial, Helvetica, sans-serif;
            font-size: 10px;
            color: #1e293b;
            line-height: 1.3;
            margin: 0;
            padding: 0;
        }
        .header-container {
            border-bottom: 2px solid #3b82f6;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .company-name {
            font-size: 18px;
            font-weight: bold;
            color: #1e3a8a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            text-align: center;
            margin-bottom: 2px;
        }
        .report-title {
            font-size: 13px;
            font-weight: bold;
            color: #475569;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 4px;
        }
        .meta-card {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
        }
        .meta-card td {
            padding: 5px 8px;
            font-size: 9.5px;
            vertical-align: middle;
        }
        .meta-label {
            font-weight: bold;
            color: #475569;
            text-transform: uppercase;
            font-size: 8.5px;
        }
        .meta-value {
            color: #0f172a;
            font-weight: 600;
        }
        .balance-box {
            text-align: right;
            padding-right: 10px !important;
        }
        .balance-amount {
            font-size: 13px;
            font-weight: bold;
            color: #1e40af;
        }
        .ledger-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .ledger-table th {
            background-color: #1e293b;
            color: #ffffff;
            font-weight: bold;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            padding: 6px 5px;
            border: 1px solid #1e293b;
        }
        .ledger-table td {
            padding: 5px 6px;
            border: 1px solid #cbd5e1;
            font-size: 9px;
            vertical-align: middle;
        }
        .ledger-table tr:nth-child(even):not(.opening-row):not(.totals-row) {
            background-color: #f8fafc;
        }
        .opening-row {
            background-color: #f1f5f9;
            font-weight: bold;
        }
        .totals-row {
            background-color: #e2e8f0;
            font-weight: bold;
            border-top: 2px solid #0f172a;
            border-bottom: 2px solid #0f172a;
        }
        .totals-row td {
            font-size: 9.5px;
            border-color: #94a3b8;
        }
        .text-center { text-align: center; }
        .text-start { text-align: left; }
        .text-end { text-align: right; }
        .text-success { color: #16a34a; font-weight: 600; }
        .text-danger { color: #dc2626; font-weight: 600; }
        .text-muted { color: #64748b; }
        .badge-voucher {
            display: inline-block;
            padding: 1px 4px;
            background-color: #f1f5f9;
            border: 1px solid #cbd5e1;
            border-radius: 3px;
            font-family: monospace;
            font-size: 8.5px;
            color: #334155;
        }
        .party-tag {
            font-size: 8px;
            color: #2563eb;
            font-weight: 600;
        }
        .signatures {
            width: 100%;
            margin-top: 30px;
            border-collapse: collapse;
        }
        .signatures td {
            width: 33.33%;
            text-align: center;
            padding-top: 40px;
            font-size: 9px;
            font-weight: 600;
            color: #475569;
        }
        .signatures .sig-line {
            border-top: 1px dashed #94a3b8;
            width: 80%;
            margin: 0 auto;
            padding-top: 4px;
        }
        .footer-note {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            font-size: 8px;
            color: #94a3b8;
            text-align: center;
            border-top: 1px solid #e2e8f0;
            padding-top: 3px;
        }
    </style>
</head>
<body>
    {{-- Header --}}
    <div class="header-container">
        <div class="company-name">THREE STARS MEDICAL SUPPLIES</div>
        <div class="report-title">General Ledger Statement</div>
    </div>

    {{-- Meta Information Card --}}
    <table class="meta-card">
        <tr>
            <td width="55%">
                <span class="meta-label">Account:</span> 
                <span class="meta-value">{{ $account->title }} ({{ $account->account_code }})</span><br>
                <span class="meta-label">Head / Category:</span> 
                <span class="meta-value">{{ $account->head->name ?? 'N/A' }}</span> &nbsp;|&nbsp;
                <span class="meta-label">Type:</span> 
                <span class="meta-value">{{ $account->type }}</span>
            </td>
            <td width="45%" class="balance-box">
                <span class="meta-label">Current Balance:</span><br>
                <span class="balance-amount">
                    {{ number_format(abs($account->calculated_balance ?? $account->current_balance), 2) }}
                    <small style="font-size: 10px; color: #475569;">{{ ($account->type === 'Credit' ? ($account->calculated_balance >= 0 ? 'Cr' : 'Dr') : ($account->calculated_balance >= 0 ? 'Dr' : 'Cr')) }}</small>
                </span>
            </td>
        </tr>
        <tr style="border-top: 1px solid #e2e8f0;">
            <td>
                <span class="meta-label">Statement Period:</span> 
                <span class="meta-value">
                    {{ $fromDate ? date('d-M-Y', strtotime($fromDate)) : 'Beginning' }} 
                    to 
                    {{ $toDate ? date('d-M-Y', strtotime($toDate)) : date('d-M-Y') }}
                </span>
            </td>
            <td class="text-end">
                <span class="meta-label">Generated On:</span> 
                <span class="meta-value">{{ now()->format('d-M-Y h:i A') }}</span>
            </td>
        </tr>
    </table>

    {{-- Ledger Table --}}
    <table class="ledger-table">
        <thead>
            <tr>
                <th width="12%" class="text-center">Date</th>
                <th width="14%" class="text-center">Voucher / Ref</th>
                <th width="36%" class="text-start">Description / Narration</th>
                <th width="12%" class="text-end">Debit (PKR)</th>
                <th width="12%" class="text-end">Credit (PKR)</th>
                <th width="14%" class="text-end">Balance (PKR)</th>
            </tr>
        </thead>
        <tbody>
            {{-- Opening Balance Row --}}
            <tr class="opening-row">
                <td colspan="5" class="text-end">Opening Balance</td>
                <td class="text-end">
                    {{ number_format(abs($openingBalance), 2) }}
                    <small class="text-muted">{{ $openingBalanceType }}</small>
                </td>
            </tr>

            @forelse($entries as $entry)
                @php
                    $debit = (float)($entry->debit ?? 0);
                    $credit = (float)($entry->credit ?? 0);
                @endphp
                <tr>
                    <td class="text-center">{{ $entry->entry_date ? $entry->entry_date->format('d-M-Y') : '-' }}</td>
                    <td class="text-center">
                        <span class="badge-voucher">{{ $entry->computed_voucher_no }}</span>
                    </td>
                    <td class="text-start">
                        {{ $entry->description }}
                        @if($entry->computed_party_name)
                            <div class="party-tag">Party: {{ $entry->computed_party_name }}</div>
                        @endif
                    </td>
                    <td class="text-end text-success">
                        {{ $debit > 0 ? number_format($debit, 2) : '-' }}
                    </td>
                    <td class="text-end text-danger">
                        {{ $credit > 0 ? number_format($credit, 2) : '-' }}
                    </td>
                    <td class="text-end" style="font-weight: 600;">
                        {{ number_format(abs($entry->computed_running_balance), 2) }}
                        <small class="text-muted">{{ $entry->computed_balance_type }}</small>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center text-muted" style="padding: 15px;">
                        No transactions found for this account in the selected period.
                    </td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="totals-row">
                <td colspan="3" class="text-end">Total Period</td>
                <td class="text-end">{{ number_format($totalDebit, 2) }}</td>
                <td class="text-end">{{ number_format($totalCredit, 2) }}</td>
                <td class="text-end">
                    {{ number_format(abs($closingBalance), 2) }}
                    <small>{{ $closingBalanceType }}</small>
                </td>
            </tr>
        </tfoot>
    </table>

    {{-- Signatures --}}
    <table class="signatures">
        <tr>
            <td>
                <div class="sig-line">Prepared By</div>
            </td>
            <td>
                <div class="sig-line">Checked By</div>
            </td>
            <td>
                <div class="sig-line">Authorized Signature</div>
            </td>
        </tr>
    </table>

    {{-- Footer note --}}
    <div class="footer-note">
        This is a computer-generated general ledger statement from Three Star Medical ERP system. Printed: {{ now()->format('d-M-Y h:i:s A') }}
    </div>
</body>
</html>
