<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Goods Receipt Note - {{ $purchase->invoice_no }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        @page { size: A4; margin: 0; }
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            background: #f0f0f0;
            color: #000;
        }

        .print-bar {
            background: #2c3e50;
            padding: 10px 20px;
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .action-btn {
            color: #fff;
            border: none;
            padding: 8px 22px;
            font-size: 13px;
            font-weight: bold;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            transition: 0.2s;
        }
        .print-btn { background: #27ae60; }
        .print-btn:hover { background: #1e8449; }
        .dl-btn { background: #2980b9; }
        .dl-btn:hover { background: #2471a3; }

        .page-wrapper {
            max-width: 210mm;
            min-height: 297mm;
            margin: 20px auto;
            background: #fff;
            padding: 15mm;
            box-shadow: 0 5px 25px rgba(0,0,0,0.2);
            position: relative;
        }

        .duplicate-stamp {
            position: absolute;
            top: 55px;
            right: 60px;
            font-size: 45px;
            font-weight: 950;
            color: rgba(180,180,180,0.18);
            transform: rotate(-10deg);
            letter-spacing: 3px;
            pointer-events: none;
            user-select: none;
            z-index: 0;
        }

        .head-addr {
            font-size: 10px;
            line-height:      1.4;
            margin-bottom: 5px;
        }

        .title-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 2.2px solid #000;
            border-bottom: 2.2px solid #000;
            padding: 4px 0;
            margin-bottom: 10px;
        }

        .grn-title { font-size: 18px; font-weight: bold; }
        .gst-inv   { font-size: 17px; font-weight: bold; }

        .top-fields {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            position: relative;
            z-index: 1;
        }

        .field-block { font-size: 10.5px; line-height: 1.8; }
        .field-row { display: flex; gap: 8px; }
        .field-label { min-width: 65px; font-weight: bold; }

        .barcode-area {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 5px;
            padding-top: 5px;
        }
        .barcode-img {
            max-width: 150px;
            height: 45px;
        }
        .posted-btn {
            border: 2px solid #000;
            font-weight: 900;
            font-size: 14px;
            padding: 4px 20px;
            background: #fff;
            display: inline-block;
            margin-top: 5px;
        }

        .right-info { font-size: 10.5px; line-height: 1.8; }

        .ntn-margin-row {
            display: flex;
            justify-content: space-between;
            margin-top: 5px;
            font-size: 10.5px;
            font-weight: bold;
        }

        .meta-row {
            display: flex;
            gap: 15px;
            font-size: 10.5px;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding: 4px 5px;
            margin-bottom: 5px;
            align-items: center;
        }
        .meta-row span { font-weight: bold; }
        .meta-row .page-num { margin-left: auto; font-size: 9px; }

        table.items {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
        }
        table.items th {
            background: #e0e0e0;
            border: 1px solid #000;
            padding: 5px 4px;
            text-align: center;
            font-weight: 800;
            color: maroon;
        }
        table.items td {
            border: 0.5px solid #666;
            padding: 4px 5px;
            vertical-align: top;
        }
        .c { text-align: center; }
        .r { text-align: right; }

        .bottom-section {
            display: flex;
            border: 1px solid #000;
            border-top: none;
        }
        .remarks-box {
            flex: 1;
            border-right: 1px solid #000;
            padding: 8px;
            font-size: 10.5px;
            min-height: 80px;
        }
        .totals-side { min-width: 350px; }
        .totals-table { width: 100%; border-collapse: collapse; }
        .totals-table td { padding: 3px 8px; font-size: 10.5px; }
        .totals-table td.lbl { font-weight: bold; width: 35%; }
        .totals-table td.val { text-align: right; font-weight: bold; width: 15%; }
        .totals-table tr.divider td { border-top: 1px solid #000; }

        .qty-margin-row {
            display: flex;
            justify-content: space-between;
            padding: 4px 8px;
            border: 1px solid #000;
            border-top: none;
            background: #fff;
            font-weight: 900;
            font-size: 11px;
            margin-bottom: 10px;
        }

        .footer-audit {
            display: flex;
            justify-content: space-between;
            font-size: 10.5px;
            margin-bottom: 15px;
        }
        .audit-col { width: 48%; line-height: 1.6; }

        .sig-block {
            margin-top: 20px;
            font-size: 11px;
        }
        .sig-name { font-weight: bold; font-size: 14px; margin: 5px 0; color: red; }
        .sig-line { border-bottom: 1px solid #000; width: 250px; margin-top: 30px; }

        .page-footer {
            border-top: 1px solid #888;
            margin-top: 30px;
            padding-top: 5px;
            display: flex;
            justify-content: space-between;
            font-size: 9px;
            color: #444;
        }

        @media print {
            body { background: #fff; }
            .print-bar { display: none !important; }
            .page-wrapper {
                box-shadow: none;
                margin: 0;
                padding: 10mm;
                max-width: 100%;
            }
        }
    </style>
</head>
<body>

<div class="print-bar">
    <a href="{{ route('purchase.grn_download', $purchase->id) }}" class="action-btn dl-btn">
        <svg style="width:16px; height:16px; fill:currentColor" viewBox="0 0 24 24"><path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/></svg>
        Download PDF
    </a>
    <button class="action-btn print-btn" onclick="window.print()">
        <svg style="width:16px; height:16px; fill:currentColor" viewBox="0 0 24 24"><path d="M19 8H5c-1.66 0-3 1.34-3 3v6h4v4h12v-4h4v-6c0-1.66-1.34-3-3-3zm-3 11H8v-5h8v5zm3-7c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1zm-1-9H6v4h12V3z"/></svg>
        Print
    </button>
</div>

<div class="page-wrapper">
    <div class="duplicate-stamp">DUPLICATE</div>

    <div class="head-addr">
        <strong>Head Office :</strong> M17-18 Mezanine Floor Seth Centre 10 Syed Mouj Darya Road (Edward Road) Lahore...<br>
        <strong>Phone :</strong> 0092-42-37353433
    </div>

    <div class="title-row">
        <span class="grn-title">Goods Receipt Note</span>
        <span class="gst-inv">GST INVOICE</span>
    </div>

    <div class="top-fields">
        <div class="field-block">
            <div class="field-row"><span class="field-label">Terminal #</span><span>100</span></div>
            <div class="field-row"><span class="field-label">PO #</span><span>{{ $purchase->po_ref ?? '0' }}</span></div>
            <div class="field-row"><span class="field-label">Bill #</span><span>{{ $purchase->id }}</span></div>
            <div class="field-row"><span class="field-label">Bill Date :</span><span>{{ \Carbon\Carbon::parse($purchase->purchase_date)->format('d/m/Y') }}</span></div>
            <div class="field-row"><span class="field-label">Rcvd By :</span><span>{{ auth()->user()->id }}</span></div>
        </div>

        <div class="barcode-area">
            <div class="barcode-box">
                @php
                    $barcodeVal = str_pad($purchase->id, 10, "0", STR_PAD_LEFT);
                @endphp
                <img class="barcode-img" src="{{ route('generate-barcode-image', ['code' => $barcodeVal]) }}" alt="barcode">
                <div style="font-size: 10px; text-align:center; font-weight:bold; letter-spacing:2px; margin-top:2px;">{{ $barcodeVal }}</div>
            </div>
            <div class="posted-btn">POSTED</div>
        </div>

        <div class="right-info">
            <div class="field-row"><span class="field-label">Code :</span><span style="font-weight:bold;">{{ $purchase->vendor->id }}</span></div>
            <div class="field-row"><span class="field-label">Supplier :</span><span style="font-weight:bold;">{{ strtoupper($purchase->vendor->name) }}</span></div>
            <div class="field-row"><span class="field-label">Address :</span><span>{{ strtoupper($purchase->vendor->address ?? '-') }}</span></div>
            <div class="field-row"><span class="field-label">Contact :</span><span>{{ $purchase->vendor->phone ?? '-' }}</span></div>
            <div class="field-row"><span class="field-label">Cell # :</span><span>-</span></div>
            <div class="ntn-margin-row">
                <span>NTN :</span>
                <span>Margin : &nbsp;0.00 &nbsp;%</span>
            </div>
        </div>
    </div>

    <div class="meta-row">
        <span>GRN # &nbsp; {{ $purchase->invoice_no }}</span>
        <span>ID : &nbsp; <span style="color:red;">{{ $purchase->id }}</span></span>
        <span>Dated : &nbsp; {{ \Carbon\Carbon::parse($purchase->created_at)->format('d/m/Y H:i:s') }}</span>
        <span style="margin-left:20px;">Location : &nbsp; {{ strtoupper($purchase->warehouse->warehouse_name ?? 'HEAD OFFICE') }}</span>
        <span class="page-num">Page 1 of 1</span>
    </div>

    @php 
        $hasGst = ((float)($purchase->gst_total ?? $purchase->total_gst ?? 0) > 0);
        $grandItemDisc = 0;
        $grandTotalPieces = 0;
        foreach($purchase->items as $it) {
            $grandItemDisc += (float)($it->item_discount ?? 0);
            $ppb = max(1, (int)($it->pieces_per_box ?? 1));
            $tp = (int)($it->total_pieces ?? 0);
            $tfp = (int)($it->free_qty_pieces ?? 0);
            $grandTotalPieces += ($tp + $tfp);
        }
    @endphp

    <table class="items">
        <thead>
            <tr>
                <th style="width:40px;">Code</th>
                <th style="text-align:left;">Item Description</th>
                <th style="width:60px;">Pack</th>
                <th style="width:50px;">Qty</th>
                <th style="width:80px;">Rate</th>
                <th style="width:60px;">Disc/Pc</th>
                @if($hasGst)
                <th style="width:60px;">GST</th>
                @endif
                <th style="width:85px;">Net Total</th>
                <th style="width:70px;">LOT NO</th>
                <th style="width:85px;">EXPIRY</th>
            </tr>
        </thead>
        <tbody>
            @php $totalQty = 0; @endphp
            @foreach($purchase->items as $item)
                @php $totalQty += $item->qty; @endphp
                <tr>
                    <td class="r">{{ $item->product->id }}</td>
                    <td>
                        {{ strtoupper($item->product->item_name . ' ' . ($item->product->brand->name ?? '')) }}
                    </td>
                    <td class="c">
                        @php
                            $uomLabel = null;
                            if ($item->uom_name && strtolower($item->uom_name) !== 'piece' && $item->uom_name !== '-- Base --') {
                                $uomLabel = $item->uom_name;
                            } elseif ($item->uom) {
                                $uomLabel = $item->uom->name;
                            }
                            if (!$uomLabel && $item->pieces_per_box > 1) $uomLabel = '1X'.(int)$item->pieces_per_box;
                            if (!$uomLabel) $uomLabel = $item->product->unit->name ?? 'Piece';
                        @endphp
                        {{ $uomLabel }}
                    </td>
                    <td class="r">
                        <strong>{{ number_format($item->total_pieces, 0) }}</strong>
                        @if($item->free_qty_pieces > 0)
                            <br><small>+{{ (int)$item->free_qty_pieces }} Free</small>
                        @endif
                    </td>
                    <td class="r">{{ number_format($item->price, 3) }}</td>
                    <td class="r">{{ (float)$item->item_discount > 0 ? number_format($item->item_discount, 2) : '—' }}</td>
                    @if($hasGst)
                    <td class="r">{{ (float)($item->gst_amount ?? 0) > 0 ? number_format($item->gst_amount, 2) : '—' }}</td>
                    @endif
                    <td class="r">{{ number_format($item->line_total, 2) }}</td>
                    <td class="c fw-bold">{{ $item->batch_no ?? '-' }}</td>
                    <td class="c">{{ $item->exp_date ? \Carbon\Carbon::parse($item->exp_date)->format('d/m/Y') : '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="bottom-section">
        <div class="remarks-box">
            <strong>Remarks :</strong><br/>
            {{ $purchase->remarks ?: '1' }}
        </div>
        <div class="totals-side">
    @php
            $pDiscount   = (float)($purchase->discount ?? 0);
            $pDiscType   = $purchase->discount_type ?? 'amount';
            $pExtraCost  = (float)($purchase->extra_cost ?? 0);
            $pFreight    = (float)($purchase->freight_charges ?? 0);
            $pGst        = (float)($purchase->gst_total ?? $purchase->total_gst ?? 0);
            $pNet        = (float)$purchase->net_amount;
            $pPaid       = (float)($purchase->paid_amount ?? 0);
            $pBalance    = $pNet - $pPaid;
        @endphp
        <table class="totals-table">
                <tr>
                    <td class="lbl">Sub Total :</td>
                    <td class="val">{{ number_format($purchase->subtotal + $grandItemDisc, 2) }}</td>
                    @if($hasGst)
                    <td class="lbl" style="padding-left:20px;">Total GST :</td>
                    <td class="val">{{ number_format($pGst, 2) }}</td>
                    @else
                    <td colspan="2"></td>
                    @endif
                </tr>
                <tr>
                    <td class="lbl">Product Discount :</td>
                    <td class="val" style="color:#c0392b;">{{ $grandItemDisc > 0 ? '- ' : '' }}{{ number_format($grandItemDisc, 2) }}</td>
                    <td class="lbl" style="padding-left:20px;">Bill Discount{{ $pDiscType === 'percent' ? ' (%)' : '' }} :</td>
                    <td class="val" style="color:#c0392b;">{{ $pDiscount > 0 ? '- ' : '' }}{{ number_format($pDiscount, 2) }}</td>
                </tr>
                <tr>
                    <td class="lbl">Bilti Expenses :</td>
                    <td class="val">{{ number_format($pExtraCost, 2) }}</td>
                    <td class="lbl" style="padding-left:20px;">Freight Charges :</td>
                    <td class="val">{{ number_format($pFreight, 2) }}</td>
                </tr>
                <tr>
                    <td class="lbl">Amount Paid :</td>
                    <td class="val" style="color:#27ae60;">{{ number_format($pPaid, 2) }}</td>
                    <td colspan="2"></td>
                </tr>
                <tr class="divider">
                    <td class="lbl"><strong>Net Payable :</strong></td>
                    <td class="val" style="font-size:12px;"><strong>{{ number_format($pNet, 2) }}</strong></td>
                    <td class="lbl" style="padding-left:20px;"><strong>Balance :</strong></td>
                    <td class="val" style="font-weight:bold; {{ $pBalance > 0 ? 'color:#c0392b;' : 'color:#27ae60;' }}">
                        {{ number_format($pBalance, 2) }}
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <div class="qty-margin-row">
        <span>Quantity Received : &nbsp;&nbsp; <strong>{{ number_format($grandTotalPieces, 0) }}</strong> <small>Pcs</small></span>
        <span>GRN Margin : &nbsp;&nbsp; 0.00 &nbsp;&nbsp;&nbsp; 0.00 %</span>
    </div>

    <div class="footer-audit">
        <div class="audit-col">
            <div><strong>Date Created :</strong> &nbsp; {{ \Carbon\Carbon::parse($purchase->created_at)->format('d/m/Y H:i:s') }}</div>
            <div><strong>Created By :</strong> &nbsp; {{ auth()->user()->name }}</div>
        </div>
        <div class="audit-col">
            <div><strong>Date Modified :</strong> &nbsp; {{ \Carbon\Carbon::parse($purchase->updated_at)->format('d/m/Y H:i:s') }}</div>
            <div><strong>Modified By :</strong> &nbsp; {{ auth()->user()->name }}</div>
        </div>
    </div>

    <div class="sig-block">
        <div><strong>Verified &amp; Posted By :</strong></div>
        <div class="sig-name">{{ strtoupper(auth()->user()->name) }}</div>
        <div style="font-weight:bold;">{{ auth()->user()->name }}</div>
        <div style="margin-top:5px;"><strong>Dated :</strong> &nbsp; {{ \Carbon\Carbon::parse($purchase->created_at)->format('d/m/Y H:i:s') }}</div>
        <div style="margin-top:10px;"><strong>Signature :</strong> <span class="sig-line"></span></div>
    </div>

    <div class="page-footer">
        <div>BizPro ver.8.0.1.4593 Copyrights &copy; 2026 Cybernetic Technologies. All rights reserved. &nbsp; rptGoodsReceiptNote</div>
        <div><strong>Print Date :</strong> &nbsp; {{ now()->format('d/m/Y H:i:s') }}</div>
    </div>
</div>

</body>
</html>