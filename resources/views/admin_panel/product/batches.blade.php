@extends('admin_panel.layout.app')

@section('content')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background: #f8fafc;
        }

        .page-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 24px;
        }

        .batch-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, .05);
            border: 1px solid #e2e8f0;
            margin-bottom: 20px;
            overflow: hidden;
        }

        .badge-exp {
            font-size: .75rem;
            padding: 6px 12px;
            border-radius: 8px;
            font-weight: 600;
        }

        .stat-pill {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 18px 22px;
            text-align: center;
            transition: transform 0.2s;
        }
        .stat-pill:hover {
            transform: translateY(-2px);
        }
    </style>

    <div class="page-container">
        {{-- Header --}}
        <div class="d-flex align-items-center gap-3 mb-4">
            <a href="{{ url()->previous() }}" class="btn btn-white border shadow-sm rounded-circle p-0"
                style="width:40px;height:40px;display:grid;place-items:center;">
                <i class="las la-arrow-left"></i>
            </a>
            <div>
                <h4 class="fw-bold mb-0">{{ $product->item_name }}</h4>
                <small class="text-muted">SKU: {{ $product->item_code }} &bull; Batch History</small>
            </div>
            <div class="ms-auto d-flex gap-2">
                <a href="{{ route('batches.opening') }}" class="btn btn-outline-primary fw-bold">
                    <i class="las la-plus me-1"></i> Add Opening Batch
                </a>
            </div>
        </div>

        {{-- Summary Pills --}}
        @php
            function formatBatchQty($pieces, $product) {
                if (!$pieces) return '0';
                $mode = $product->size_mode ?? 'standard';
                $ppb = $product->pieces_per_box > 0 ? (int)$product->pieces_per_box : 1;
                $piecesFloat = (float) $pieces;

                if ($mode === 'by_cartons' || $mode === 'by_carton') {
                    // Check if old buggy data (small decimal)
                    $cleanStr = rtrim(rtrim((string)$pieces, '0'), '.');
                    if ($piecesFloat < 1000 && str_contains($cleanStr, '.')) {
                        // Attempt to read it as boxes.pieces since old data didn't convert to pieces
                        $parts = explode('.', $cleanStr);
                        $b = (int)($parts[0] ?? 0);
                        $p = (int)($parts[1] ?? 0);
                        $oldPieces = ($b * $ppb) + $p;
                        if ($oldPieces > 0) {
                            $piecesFloat = $oldPieces;
                        }
                    }
                    $boxes = floor($piecesFloat / $ppb);
                    $rem = round($piecesFloat - ($boxes * $ppb));
                    $res = $boxes . 'box';
                    if ($rem > 0) {
                        $res .= '+' . $rem . 'piece';
                    }
                    return $res;
                }
                return rtrim(rtrim((string)$piecesFloat, '0'), '.');
            }
        @endphp

        @php
            $totalQty = $batches->sum('qty_remaining');
            $expiredCnt = $batches->filter(fn($b) => $b->expiry_status === 'expired')->count();
            $criticalCnt = $batches->filter(fn($b) => $b->expiry_status === 'critical')->count();
            $okCnt = $batches->filter(fn($b) => $b->expiry_status === 'ok')->count();
        @endphp
        <div class="row g-3 mb-4">
            <div class="col-sm-3">
                <div class="stat-pill">
                    <div class="text-muted small">Total Available</div>
                    <div class="fw-bold fs-5">{{ number_format($totalQty) }}</div>
                </div>
            </div>
            <div class="col-sm-3">
                <div class="stat-pill border-success">
                    <div class="text-muted small">OK Batches</div>
                    <div class="fw-bold fs-5 text-success">{{ $okCnt }}</div>
                </div>
            </div>
            <div class="col-sm-3">
                <div class="stat-pill border-warning">
                    <div class="text-muted small">Critical (≤30d)</div>
                    <div class="fw-bold fs-5 text-warning">{{ $criticalCnt }}</div>
                </div>
            </div>
            <div class="col-sm-3">
                <div class="stat-pill border-danger">
                    <div class="text-muted small">Expired</div>
                    <div class="fw-bold fs-5 text-danger">{{ $expiredCnt }}</div>
                </div>
            </div>
        </div>

        {{-- Batch Table --}}
        <div class="batch-card">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>Batch No.</th>
                            <th>MFG Date</th>
                            <th>EXP Date</th>
                            <th>Warehouse</th>
                            <th>Received</th>
                            <th>Remaining</th>
                            <th>Source</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($batches as $batch)
                            <tr>
                                <td><strong>{{ $batch->batch_number }}</strong></td>
                                <td>{{ $batch->mfg_date ? $batch->mfg_date->format('d M Y') : '—' }}</td>
                                <td>
                                    {{ $batch->exp_date->format('d M Y') }}
                                    @php $days = $batch->days_to_expiry @endphp
                                    @if ($days >= 0)
                                        <small class="text-muted">({{ $days }}d)</small>
                                    @endif
                                </td>
                                <td>{{ $batch->warehouse->warehouse_name ?? '—' }}</td>
                                <td>{{ formatBatchQty($batch->qty_received, $product) }}</td>
                                <td class="fw-bold">{{ formatBatchQty($batch->qty_remaining, $product) }}</td>
                                <td><span
                                        class="badge bg-secondary-subtle text-secondary">{{ ucfirst(str_replace('_', ' ', $batch->source_type)) }}</span>
                                </td>
                                <td>
                                    <span class="badge {{ $batch->expiry_badge_class }} badge-exp">
                                        {{ ucfirst($batch->expiry_status) }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-5">No batches found for this product.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
