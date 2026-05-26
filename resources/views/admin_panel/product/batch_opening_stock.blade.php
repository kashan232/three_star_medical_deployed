@extends('admin_panel.layout.app')

@section('content')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/line-awesome/1.3.0/line-awesome/css/line-awesome.min.css">

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: #f1f5f9;
        }

        .page-container {
            max-width: 1100px;
            margin: 0 auto;
            padding: 20px;
        }

        .section-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 1px 4px rgba(0, 0, 0, .06);
            border: 1px solid #e2e8f0;
            margin-bottom: 24px;
            overflow: hidden;
        }

        .card-header-pro {
            padding: 16px 24px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .card-body-pro {
            padding: 24px;
        }

        .form-label-pro {
            font-size: 0.72rem;
            font-weight: 600;
            text-transform: uppercase;
            color: #64748b;
            margin-bottom: 5px;
            letter-spacing: .04em;
        }

        .form-control-pro {
            width: 100%;
            padding: 9px 13px;
            font-size: .92rem;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            background: #fff;
            transition: border-color .15s;
        }

        .form-control-pro:focus {
            outline: none;
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, .1);
        }

        .btn-add-row {
            background: #eff6ff;
            color: #3b82f6;
            border: 1px dashed #93c5fd;
            border-radius: 10px;
            padding: 10px 20px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            margin-top: 12px;
        }

        .btn-add-row:hover {
            background: #dbeafe;
        }

        .batch-row {
            background: #fafafa;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 12px;
            position: relative;
        }

        .btn-remove-row {
            position: absolute;
            top: 12px;
            right: 12px;
            background: #fef2f2;
            border: none;
            border-radius: 8px;
            color: #ef4444;
            width: 30px;
            height: 30px;
            cursor: pointer;
            font-size: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .badge-exp {
            font-size: .7rem;
            padding: 2px 8px;
            border-radius: 20px;
        }
    </style>

    <div class="page-container">
        <div class="d-flex align-items-center gap-3 mb-4">
            <a href="{{ url()->previous() }}" class="btn btn-white border shadow-sm rounded-circle p-0"
                style="width:40px;height:40px;display:grid;place-items:center;">
                <i class="las la-arrow-left"></i>
            </a>
            <div>
                <h4 class="fw-bold mb-0">Opening Stock Batch Entry</h4>
                <small class="text-muted">Register existing stock with Batch / MFG / EXP dates</small>
            </div>
        </div>

        <div id="alertBox" class="d-none"></div>

        <div class="section-card">
            <div class="card-header-pro">
                <h6 class="fw-bold mb-0"><i class="las la-layer-group text-primary me-2"></i>Batch Rows</h6>
                <span class="text-muted small">Fill in one row per batch. Same product can have multiple batches.</span>
            </div>
            <div class="card-body-pro">
                <div id="batchRows"></div>
                <button type="button" class="btn-add-row" onclick="addRow()">
                    <i class="las la-plus-circle me-2"></i> Add Another Batch
                </button>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2">
            <button type="button" class="btn btn-outline-secondary px-4" id="btnCancel"
                onclick="history.back()">Cancel</button>
            <button type="button" class="btn btn-primary px-5 fw-bold" id="btnSave" onclick="saveAll()">
                <i class="las la-save me-2" id="saveIcon"></i>
                <span id="saveSpinner" class="spinner-border spinner-border-sm me-2 d-none" role="status"
                    aria-hidden="true"></span>
                <span id="saveText">Save All Batches</span>
            </button>
        </div>
    </div>

    <!-- Row Template -->
    <template id="rowTemplate">
        <div class="batch-row" data-row-index="__IDX__">
            <button type="button" class="btn-remove-row" onclick="removeRow(this)" title="Remove">&times;</button>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label-pro">Medicine / Product <span class="text-danger">*</span></label>
                    <select name="product_id" class="form-control-pro product-select" required>
                        <option value="">— Select Product —</option>
                        @foreach ($products as $p)
                            <option value="{{ $p->id }}">{{ $p->item_name }} ({{ $p->item_code }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label-pro">Warehouse <span class="text-danger">*</span></label>
                    <select name="warehouse_id" class="form-control-pro" required>
                        <option value="">— Select —</option>
                        @foreach ($warehouses as $w)
                            <option value="{{ $w->id }}">{{ $w->warehouse_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label-pro">Qty <span class="text-danger">*</span></label>
                    <input type="number" name="qty" class="form-control-pro" placeholder="0" step="0.01"
                        min="0.01" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label-pro">Batch / Lot No. <span class="text-danger">*</span></label>
                    <input type="text" name="batch_number" class="form-control-pro" placeholder="e.g. BT-2024-001"
                        required>
                </div>
                <div class="col-md-3">
                    <label class="form-label-pro">MFG Date</label>
                    <input type="date" name="mfg_date" class="form-control-pro">
                </div>
                <div class="col-md-3">
                    <label class="form-label-pro">EXP Date <span class="text-danger">*</span></label>
                    <input type="date" name="exp_date" class="form-control-pro exp-date-input" required>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <div id="exp_badge___IDX__"
                        class="badge-exp bg-secondary text-white px-3 py-2 rounded-3 w-100 text-center"></div>
                </div>
            </div>
        </div>
    </template>
@endsection

@section('js')
    <script>
        let rowCount = 0;

        function addRow() {
            const tpl = document.getElementById('rowTemplate').innerHTML.replace(/__IDX__/g, rowCount);
            const div = document.createElement('div');
            div.innerHTML = tpl;
            document.getElementById('batchRows').appendChild(div.firstElementChild);

            // Bind EXP date change for live badge
            const row = document.querySelector(`[data-row-index="${rowCount}"]`);
            row.querySelector('.exp-date-input').addEventListener('change', function() {
                updateExpBadge(row, this.value);
            });

            rowCount++;
        }

        function removeRow(btn) {
            const row = btn.closest('.batch-row');
            if (document.querySelectorAll('.batch-row').length > 1) {
                row.remove();
            } else {
                alert('You need at least one row.');
            }
        }

        function updateExpBadge(row, expDate) {
            const badge = row.querySelector('[id^="exp_badge_"]');
            if (!expDate) {
                badge.textContent = '';
                return;
            }
            const days = Math.round((new Date(expDate) - new Date()) / (1000 * 60 * 60 * 24));
            if (days < 0) {
                badge.textContent = 'EXPIRED';
                badge.className = 'badge-exp bg-danger text-white px-3 py-2 rounded-3 w-100 text-center';
            } else if (days <= 30) {
                badge.textContent = days + 'd left';
                badge.className = 'badge-exp bg-warning text-dark px-3 py-2 rounded-3 w-100 text-center';
            } else if (days <= 90) {
                badge.textContent = days + 'd left';
                badge.className = 'badge-exp bg-info text-dark px-3 py-2 rounded-3 w-100 text-center';
            } else {
                badge.textContent = 'OK';
                badge.className = 'badge-exp bg-success text-white px-3 py-2 rounded-3 w-100 text-center';
            }
        }

        function saveAll() {
            const btnSave = document.getElementById('btnSave');
            const btnCancel = document.getElementById('btnCancel');
            const saveSpinner = document.getElementById('saveSpinner');
            const saveIcon = document.getElementById('saveIcon');
            const saveText = document.getElementById('saveText');
            const btnAddRow = document.querySelector('.btn-add-row');
            const removeBtns = document.querySelectorAll('.btn-remove-row');

            const setProcessingState = (isProcessing) => {
                btnSave.disabled = isProcessing;
                if (btnCancel) btnCancel.disabled = isProcessing;
                if (btnAddRow) btnAddRow.disabled = isProcessing;
                removeBtns.forEach(b => b.disabled = isProcessing);

                if (isProcessing) {
                    saveIcon.classList.add('d-none');
                    saveSpinner.classList.remove('d-none');
                    saveText.textContent = 'Saving...';
                } else {
                    saveIcon.classList.remove('d-none');
                    saveSpinner.classList.add('d-none');
                    saveText.textContent = 'Save All Batches';
                }
            };

            const rows = document.querySelectorAll('.batch-row');
            const data = {
                rows: []
            };
            let valid = true;

            rows.forEach(row => {
                const obj = {
                    product_id: row.querySelector('[name="product_id"]').value,
                    warehouse_id: row.querySelector('[name="warehouse_id"]').value,
                    batch_number: row.querySelector('[name="batch_number"]').value,
                    mfg_date: row.querySelector('[name="mfg_date"]').value || null,
                    exp_date: row.querySelector('[name="exp_date"]').value,
                    qty: row.querySelector('[name="qty"]').value,
                };
                if (!obj.product_id || !obj.warehouse_id || !obj.batch_number || !obj.exp_date || !obj.qty) {
                    valid = false;
                }
                data.rows.push(obj);
            });

            if (!valid) {
                showAlert('danger', 'Please fill all required fields (Product, Warehouse, Batch No., EXP Date, Qty).');
                return;
            }

            setProcessingState(true);

            fetch('{{ route('batches.opening.store') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(data)
                })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        showAlert('success', res.message + ' Page will reset in 2 seconds...');
                        setTimeout(() => {
                            document.getElementById('batchRows').innerHTML = '';
                            rowCount = 0;
                            addRow();
                            setProcessingState(false);
                            document.getElementById('alertBox').classList.add('d-none');
                        }, 2000);
                    } else {
                        showAlert('danger', JSON.stringify(res.errors ?? res.message));
                        setProcessingState(false);
                    }
                })
                .catch(() => {
                    showAlert('danger', 'Something went wrong. Please try again.');
                    setProcessingState(false);
                });
        }

        function showAlert(type, msg) {
            const box = document.getElementById('alertBox');
            box.className = `alert alert-${type} mb-3`;
            box.textContent = msg;
        }

        // Start with one row
        document.addEventListener('DOMContentLoaded', () => addRow());
    </script>
@endsection
