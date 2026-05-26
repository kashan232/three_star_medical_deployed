@extends('admin_panel.layout.app')

@section('content')
    <style>
        /* Premium Modal & UI Styles */
        .premium-card {
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(0, 0, 0, 0.02);
        }

        .modal-content {
            border-radius: 16px;
            border: none;
            overflow: hidden;
        }

        .modal-header {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 20px 30px;
        }

        .modal-title {
            font-weight: 700;
            font-size: 1.25rem;
        }

        .modal-body {
            padding: 30px;
            background: #f8fafc;
        }

        .btn-close-white {
            filter: invert(1) grayscale(100%) brightness(200%);
        }

        .info-label {
            font-size: 0.8rem;
            color: #64748b;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 5px;
            display: block;
        }

        .big-input {
            font-size: 1.25rem;
            font-weight: 600;
            border-radius: 10px;
            padding: 12px;
        }

        .details-card {
            background: white;
            border: 1px dashed #cbd5e1;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 20px;
            display: none;
        }

        .calc-box {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 15px;
            text-align: center;
        }

        .calc-number {
            font-size: 1.5rem;
            font-weight: 800;
            color: #0369a1;
        }

        /* Select2 fixes for Modal */
        .select2-container {
            width: 100% !important;
            z-index: 9999;
        }

        .select2-dropdown {
            z-index: 9999;
        }

        .select2-container--default .select2-selection--single {
            height: 45px;
            display: flex;
            align-items: center;
            border-color: #e2e8f0;
            border-radius: 8px;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 43px;
        }
    </style>

    <div class="container-fluid">
        <div class="card premium-card border-0">
            <div class="card-header bg-white py-4 d-flex justify-content-between align-items-center border-bottom">
                <div>
                    <h4 class="mb-1 fw-bold text-dark"><i class="fas fa-warehouse me-2 text-success"></i> Warehouse Inventory</h4>
                    <p class="text-muted small mb-0"><i class="fas fa-shield-alt me-1"></i> Stock is strictly tracked by UOM. Manual overrides are disabled to ensure audit integrity.</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('report.warehouse') }}" class="btn btn-outline-success btn-sm border-2">
                        <i class="fas fa-chart-line me-1"></i> Inventory Report
                    </a>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="stockTable">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4 text-uppercase small fw-bold text-muted" style="width: 50px;">#</th>
                                <th class="text-uppercase small fw-bold text-muted">Warehouse / Branch</th>
                                <th class="text-uppercase small fw-bold text-muted">Product Identity</th>
                                <th class="text-center text-uppercase small fw-bold text-muted">UOM Type</th>
                                <th class="text-end text-uppercase small fw-bold text-muted pe-4">Current Stock</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($stocks as $stock)
                                <tr>
                                    <td class="ps-4 text-muted small">{{ $stocks->firstItem() + $loop->index }}</td>
                                    <td>
                                        <div class="fw-bold text-dark">{{ $stock->warehouse->warehouse_name ?? 'Unknown Warehouse' }}</div>
                                        <div class="small text-muted"><i class="fas fa-code-branch me-1"></i> {{ $stock->warehouse->branch->name ?? 'Main Branch' }}</div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            @if ($stock->product && $stock->product->image)
                                                <img src="{{ asset('uploads/products/' . $stock->product->image) }}"
                                                    class="rounded border shadow-sm me-3"
                                                    style="width: 40px; height: 40px; object-fit: cover;">
                                            @else
                                                <div class="rounded border shadow-sm me-3 bg-light d-flex align-items-center justify-content-center text-muted"
                                                    style="width: 40px; height: 40px; font-size: 10px;">NO IMG</div>
                                            @endif
                                            <div>
                                                <div class="fw-bold text-dark" style="font-size: 0.95rem;">{{ $stock->product->item_name ?? 'Deleted Product' }}</div>
                                                <div class="badge bg-light text-dark border small fw-normal">{{ $stock->product->item_code ?? 'N/A' }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        @php
                                            $totalPcs = (float)($stock->total_pieces ?? 0);
                                            $packings = optional($stock->product)->packings ? $stock->product->packings->sortByDesc('pieces_per_box') : collect();
                                            $breakdown = [];
                                            $remaining = $totalPcs;

                                            if ($packings->count() > 0) {
                                                foreach ($packings as $p) {
                                                    $ppb = (int)($p->pieces_per_box ?? 1);
                                                    if ($ppb > 1 && $remaining >= $ppb) {
                                                        $count = floor($remaining / $ppb);
                                                        $breakdown[] = $count . ' ' . $p->name;
                                                        $remaining %= $ppb;
                                                    }
                                                }
                                            }
                                            
                                            if ($remaining > 0 || empty($breakdown)) {
                                                $breakdown[] = $remaining . ' pcs';
                                            }
                                        @endphp
                                        <div class="d-flex flex-wrap justify-content-center gap-1">
                                            @foreach($breakdown as $item)
                                                <span class="badge rounded-pill bg-soft-info text-info border border-info px-2 py-1 small">
                                                    {{ $item }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </td>
                                    <td class="text-end pe-4">
                                        @php
                                            $totalPcs = (float)($stock->total_pieces ?? 0);
                                            $ppb = (int)($stock->product->pieces_per_box ?? 1);
                                            $boxes = 0;
                                            $loose = $totalPcs;
                                            
                                            if ($ppb > 1) {
                                                $boxes = floor($totalPcs / $ppb);
                                                $loose = $totalPcs % $ppb;
                                            }
                                        @endphp
                                        <div class="d-inline-block text-end">
                                            <div class="fw-bold text-primary mb-0 d-flex align-items-baseline justify-content-end" style="font-size: 1.25rem;">
                                                <span class="fs-4">{{ $boxes }}</span>
                                                <span class="text-muted mx-1">.</span>
                                                <span class="text-secondary" style="font-size: 1.1rem;">{{ $loose }}</span>
                                                <small class="ms-2 text-muted fw-normal" style="font-size: 0.75rem;">(B.P)</small>
                                            </div>
                                            <div class="small text-muted mt-n1">
                                                <i class="fas fa-cubes me-1 opacity-50"></i> {{ number_format($totalPcs) }} Total Pcs
                                            </div>
                                            <div class="small text-muted">
                                                @if(optional($stock->product)->size_mode === 'by_size')
                                                    <span class="badge bg-soft-secondary text-secondary border-0 p-0">
                                                        <i class="fas fa-ruler-combined me-1"></i>~ {{ number_format($totalPcs * ($stock->product->total_m2 / max(1, (int)$stock->product->pieces_per_box)), 2) }} m²
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted">
                                        <i class="fas fa-box-open fa-3x mb-3 opacity-25"></i>
                                        <p class="mb-0">No stock records found.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-white py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="small text-muted">
                        Showing {{ $stocks->firstItem() }} to {{ $stocks->lastItem() }} of {{ $stocks->total() }} stock entries
                    </div>
                    <div>
                        {{ $stocks->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .bg-soft-info { background-color: rgba(13, 202, 240, 0.1); }
        .bg-soft-secondary { background-color: rgba(108, 117, 125, 0.1); }
        .text-info { color: #0dcaf0 !important; }
        .border-info { border-color: rgba(13, 202, 240, 0.3) !important; }
        .table-hover tbody tr:hover { background-color: rgba(16, 185, 129, 0.02); }
    </style>
@endsection
