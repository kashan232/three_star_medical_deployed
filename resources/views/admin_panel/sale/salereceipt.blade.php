<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt - {{ $sale->id }}</title>
    <style>
        @media print {
            body {
                width: 72mm;
                margin: 0;
                padding: 0;
                font-family: 'Courier New', Courier, monospace;
                /* Monospaced fonts often look sharper on thermal */
            }

            .no-print {
                display: none;
            }

            @page {
                size: 72mm auto;
                margin: 0;
            }
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 11px;
            color: #000;
            background: #fff;
            width: 72mm;
            /* Preview width */
            margin: 0 auto;
            padding: 5px;
        }

        .header {
            text-align: center;
            margin-bottom: 10px;
        }

        .company-name {
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .sub-header {
            font-size: 10px;
            margin-bottom: 5px;
        }

        .divider {
            border-top: 1px dashed #000;
            margin: 5px 0;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2px;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }

        .items-table th,
        .items-table td {
            text-align: left;
            vertical-align: top;
            padding: 2px 0;
        }

        .items-table th {
            border-bottom: 1px solid #000;
            font-size: 10px;
        }

        .text-end {
            text-align: right !important;
        }

        .text-center {
            text-align: center !important;
        }

        .totals-section {
            margin-top: 10px;
            border-top: 1px dashed #000;
            padding-top: 5px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2px;
            font-weight: bold;
        }

        .footer {
            text-align: center;
            margin-top: 15px;
            font-size: 10px;
        }

        .btn-print {
            padding: 10px;
            text-align: center;
            background: #eee;
            margin-bottom: 10px;
            cursor: pointer;
            display: block;
            text-decoration: none;
            color: #333;
            font-weight: bold;
            border: 1px solid #ccc;
        }
    </style>
</head>

<body>

    <a href="javascript:window.print()" class="btn-print no-print">PRINT RECEIPT</a>

    <div class="header">
        <div class="company-name">THREE STARS MEDICAL</div>
        <div class="sub-header">Seth Centre, Edward Road, Lahore</div>
        <div class="sub-header">Ph: 0092-42-37353433</div>
    </div>

    <div class="divider"></div>

    <div class="info-row">
        <span>Inv #: {{ $sale->invoice_no ?? $sale->id }}</span>
        <span>{{ $sale->created_at->format('d-m-Y h:i A') }}</span>
    </div>
    <div class="info-row">
        <span>Date: {{ $sale->sale_date ? \Carbon\Carbon::parse($sale->sale_date)->format('d-m-Y') : $sale->created_at->format('d-m-Y') }}</span>
    </div>
    <div class="info-row">
        <span>Cust: {{ Str::limit($sale->customer_relation->customer_name ?? 'Walking', 18) }}</span>
    </div>
    @if (auth()->check())
        <div class="info-row">
            <span>User: {{ auth()->user()->name }}</span>
        </div>
    @endif

    <div class="divider"></div>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 45%;">Item</th>
                <th style="width: 20%;" class="text-center">Qty</th>
                <th style="width: 35%;" class="text-end">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($saleItems as $item)
                @php
                    // Size mode logic for display
                    $sizeMode = $item['size_mode'] ?? 'std';
                    $totalPieces = (int) $item['total_pieces'];

                    // Display quantity string
                    $qtyDisplay = $totalPieces;
                    if ($sizeMode == 'by_cartons' || $sizeMode == 'by_size') {
                        $piecesPerBox = (int) ($item['pieces_per_box'] ?? 1);
                        $boxes = floor($totalPieces / $piecesPerBox);
                        $loose = $totalPieces % $piecesPerBox;

                        if ($boxes > 0 && $loose > 0) {
                            $qtyDisplay = "$boxes.$loose";
                        } elseif ($boxes > 0) {
                            $qtyDisplay = $boxes;
                        } else {
                            $qtyDisplay = $loose;
                        }
                    }
                @endphp
                <tr>
                    <td colspan="3" style="font-weight: 600;">
                        {{ \Illuminate\Support\Str::limit($item['item_name'], 25) }}
                        @if(!empty($sale->enable_hs_code) && !empty($item['hs_code']))
                            <br><span style="font-size:9px; font-weight:normal; color:#555;">HS: {{ $item['hs_code'] }}</span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td style="font-size: 10px; padding-left: 5px;">
                        {{ number_format($item['price'], 2) }} x
                    </td>
                    <td class="text-center">{{ $qtyDisplay }}</td>
                    <td class="text-end">{{ number_format($item['total'], 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals-section">
        @php 
            $totalItemDisc = $sale->items->sum('item_discount');
        @endphp
        <div class="info-row">
            <span>Sub Total:</span>
            <span>{{ number_format((float)$sale->total_bill_amount + $totalItemDisc, 2) }}</span>
        </div>

        @if ($totalItemDisc > 0)
            <div class="info-row">
                <span>Product Discount:</span>
                <span>-{{ number_format($totalItemDisc, 2) }}</span>
            </div>
        @endif

        @if ($sale->total_extradiscount > 0)
            <div class="info-row">
                <span>Bill Discount:</span>
                <span>-{{ number_format($sale->total_extradiscount, 2) }}</span>
            </div>
        @endif

        @if ((float)($sale->total_gst ?? 0) > 0)
            <div class="info-row">
                <span>Total GST:</span>
                <span>{{ number_format($sale->total_gst, 2) }}</span>
            </div>
        @endif

        @if ((float)($sale->total_inc_tax ?? 0) > 0)
            <div class="info-row">
                <span>Inc Tax:</span>
                <span>{{ number_format($sale->total_inc_tax, 2) }}</span>
            </div>
        @endif

        @if ((float)($sale->total_adv_tax ?? 0) > 0)
            <div class="info-row">
                <span>Adv Tax:</span>
                <span>{{ number_format($sale->total_adv_tax, 2) }}</span>
            </div>
        @endif

        <div class="info-row">
            <span>Freight Charges:</span>
            <span>{{ number_format($sale->total_freight ?? 0, 2) }}</span>
        </div>

        <div class="info-row">
            <span>Bilti Expenses:</span>
            <span>{{ number_format($sale->expense ?? $sale->total_expense ?? 0, 2) }}</span>
        </div>

        <div class="total-row" style="font-size: 14px; border-top: 1px solid #000; margin-top: 5px; padding-top: 2px;">
            <span>NET PAYABLE:</span>
            <span>{{ number_format($sale->total_net, 2) }}</span>
        </div>

        <div class="divider"></div>

        <div class="info-row">
            <span>Prev Bal:</span>
            <span>{{ number_format(abs($previousBalance), 2) }} {{ $previousBalance >= 0 ? 'Dr' : 'Cr' }}</span>
        </div>
        <div class="info-row">
            <span>Paid:</span>
            <span>{{ number_format($sale->cash, 2) }}</span>
        </div>

        @php
            $finalBalance = $previousBalance + $sale->total_net - $sale->cash;
        @endphp
        <div class="info-row" style="font-weight: bold;">
            <span>Closing Bal:</span>
            <span>{{ number_format(abs($finalBalance), 2) }} {{ $finalBalance >= 0 ? 'Dr' : 'Cr' }}</span>
        </div>
    </div>

    <div class="divider"></div>

    <div class="footer">
        <p>Thank you for shopping!</p>
        <p style="font-size: 9px;">Software by: Antigravity AI</p>
    </div>

</body>

</html>
