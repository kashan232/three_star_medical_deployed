@extends('admin_panel.layout.app')

@section('content')
    {{-- 
        SUCCESS: Horizontal Layout Redesign
        Features: 
        - Top Section: Identity (Image + Details side-by-side)
        - Middle Section: Measurements & Stock
        - Bottom Section: Financials & Action
    --}}

    {{-- External Resources --}}
    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/line-awesome/1.3.0/line-awesome/css/line-awesome.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: #4f46e5;
            --primary-hover: #4338ca;
            --primary-light: #eef2ff;
            --bg-body: #f1f5f9;
            --bg-card: #ffffff;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
            --radius-md: 10px;
            --radius-lg: 16px;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-body);
            color: var(--text-main);
            padding-bottom: 40px;
        }

        .page-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        /* --- Global Cards --- */
        .section-card {
            background: var(--bg-card);
            border-radius: var(--radius-lg);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05), 0 1px 2px rgba(0, 0, 0, 0.06);
            border: 1px solid var(--border-color);
            margin-bottom: 24px;
            overflow: hidden;
        }

        .card-header-pro {
            padding: 16px 24px;
            border-bottom: 1px solid var(--border-color);
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .card-title-pro {
            font-size: 1rem;
            font-weight: 700;
            color: var(--text-main);
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 0;
        }

        .card-body-pro {
            padding: 24px;
        }

        /* --- Form Styling --- */
        .form-label-pro {
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            color: var(--text-muted);
            margin-bottom: 6px;
            letter-spacing: 0.02em;
        }

        .form-control-pro {
            display: block;
            width: 100%;
            padding: 10px 14px;
            font-size: 0.95rem;
            font-weight: 500;
            color: var(--text-main);
            background-color: #fff;
            background-clip: padding-box;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }

        .form-control-pro:focus {
            border-color: var(--primary);
            outline: 0;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }

        .form-select-pro {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 0.75rem center;
            background-size: 16px 12px;
        }

        /* --- Section 1: Identity Grid --- */
        .identity-wrapper {
            display: flex;
            gap: 24px;
        }

        .image-section {
            width: 280px;
            flex-shrink: 0;
        }

        .details-section {
            flex: 1;
        }

        .img-uploader {
            width: 100%;
            aspect-ratio: 1/1;
            /* Square for product */
            border: 2px dashed #cbd5e1;
            border-radius: var(--radius-lg);
            background: #f8fafc;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            transition: all 0.2s;
        }

        .img-uploader:hover {
            border-color: var(--primary);
            background: var(--primary-light);
        }

        .img-uploader img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            /* Show full product */
            padding: 10px;
        }

        /* --- Section 2: Specs --- */
        .specs-grid {
            display: grid;
            grid-template-columns: 250px 1fr 300px;
            gap: 24px;
            align-items: start;
        }

        /* Mode Switcher Vertical */
        .mode-switcher-vertical {
            display: flex;
            flex-direction: column;
            gap: 8px;
            background: #f8fafc;
            padding: 12px;
            border-radius: var(--radius-md);
        }

        .mode-btn-v {
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--text-muted);
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 10px;
            border: 1px solid transparent;
        }

        .mode-btn-v:hover {
            background: #fff;
        }

        .mode-btn-v.active {
            background: #fff;
            color: var(--primary);
            border-color: var(--border-color);
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }

        .mode-btn-v i {
            font-size: 1.2rem;
        }

        /* Stats Box */
        .stats-summary-box {
            background: #f8fafc;
            border-radius: var(--radius-md);
            padding: 20px;
            border: 1px solid var(--border-color);
        }

        .stat-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
            padding-bottom: 12px;
            border-bottom: 1px solid #e2e8f0;
        }

        .stat-item:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border: none;
        }

        .stat-label {
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        .stat-value {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--text-main);
        }


        /* --- Section 3: Financials --- */
        .financials-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr 300px;
            /* Split inputs, calcs, and total */
            gap: 24px;
        }

        .total-value-display {
            background: #0f172a;
            color: #fff;
            padding: 24px;
            border-radius: var(--radius-lg);
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: center;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }

        .btn-save-floating {
            position: fixed;
            bottom: 24px;
            right: 24px;
            background: var(--primary);
            color: white;
            padding: 16px 32px;
            border-radius: 50px;
            font-weight: 700;
            font-size: 1rem;
            border: none;
            box-shadow: 0 10px 25px -5px rgba(79, 70, 229, 0.5);
            z-index: 100;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .btn-save-floating:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 30px -5px rgba(79, 70, 229, 0.6);
            background: var(--primary-hover);
            color: #fff;
        }

        /* --- Responsive --- */
        @media (max-width: 991px) {
            .identity-wrapper {
                flex-direction: column;
            }

            .image-section {
                width: 100%;
            }

            .img-uploader {
                aspect-ratio: 16/9;
            }

            .specs-grid {
                grid-template-columns: 1fr;
            }

            .financials-grid {
                grid-template-columns: 1fr;
            }

            .mode-switcher-vertical {
                flex-direction: row;
                overflow-x: auto;
            }

            .btn-save-floating {
                width: calc(100% - 48px);
                justify-content: center;
                text-align: center;
            }
        }
    </style>

    <div class="page-container">

        {{-- Page Title --}}
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div class="d-flex align-items-center gap-3">
                <a href="{{ route('product') }}" class="btn btn-white border shadow-sm rounded-circle p-0"
                    style="width: 40px; height: 40px; display: grid; place-items: center;">
                    <i class="las la-arrow-left"></i>
                </a>
                <div>
                    <h4 class="fw-bold mb-0 text-dark">Edit Product</h4>
                    <small class="text-muted">Modify existing item in inventory system</small>
                </div>
            </div>
        </div>

        <form action="{{ route('product.update', $product->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            
            {{-- SECTION 1: IDENTITY --}}
            <div class="section-card">
                <div class="card-header-pro">
                    <h5 class="card-title-pro"><i class="las la-tag text-primary"></i> Product Identity</h5>
                </div>
                <div class="card-body-pro">
                    <div class="identity-wrapper">
                        {{-- Image (Left) --}}
                        <div class="image-section">
                            <input type="file" id="imageInput" name="image" class="d-none" accept="image/*">
                            <div class="img-uploader" onclick="document.getElementById('imageInput').click()">
                                <button type="button" id="clearImageBtn"
                                    class="btn btn-danger btn-sm position-absolute top-0 end-0 m-2 {{ $product->image ? '' : 'd-none' }} rounded-circle"
                                    style="width:24px;height:24px;padding:0;z-index: 10;">&times;</button>
                                <img id="preview" src="{{ $product->image ? asset($product->image) : '' }}" class="{{ $product->image ? '' : 'd-none' }}">
                                <div id="uploadPlaceholder" class="text-center {{ $product->image ? 'd-none' : '' }}">
                                    <div class="bg-white p-3 rounded-circle shadow-sm d-inline-block mb-3">
                                        <i class="las la-camera fs-1 text-primary"></i>
                                    </div>
                                    <h6 class="fw-bold mb-1">Update Image</h6>
                                    <small class="text-muted">Click to browse</small>
                                </div>
                            </div>
                        </div>

                        {{-- Details (Right) --}}
                        <div class="details-section">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label-pro">Product Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control-pro fs-6 fw-bold" name="product_name" value="{{ $product->item_name }}" required placeholder="e.g. Paracetamol 500mg">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label-pro">Item Code <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control-pro" name="item_code" value="{{ $product->item_code }}" required placeholder="ITEM-001">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label-pro">Barcode Auto-Gen</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control-pro" id="barcodeInput" name="barcode_path" value="{{ $product->barcode_path }}">
                                        <button type="button" class="btn btn-light border" id="generateBarcodeBtn"><i class="las la-magic"></i></button>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label-pro">Category <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <select class="form-select form-control-pro form-select-pro" id="category-dropdown" name="category_id" required>
                                            <option value="">Select...</option>
                                            @foreach ($categories as $cat) 
                                                <option value="{{ $cat->id }}" {{ $product->category_id == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option> 
                                            @endforeach
                                        </select>
                                        <button type="button" class="btn btn-light border" data-bs-toggle="modal" data-bs-target="#categoryModal"><i class="las la-plus"></i></button>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label-pro">Sub Category</label>
                                    <div class="input-group">
                                        <select class="form-select form-control-pro form-select-pro" id="subcategory-dropdown" name="sub_category_id">
                                            <option value="">Select...</option>
                                            @foreach($subcategories as $sub)
                                                @if($sub->category_id == $product->category_id)
                                                    <option value="{{ $sub->id }}" {{ $product->sub_category_id == $sub->id ? 'selected' : '' }}>{{ $sub->name }}</option>
                                                @endif
                                            @endforeach
                                        </select>
                                        <button type="button" class="btn btn-light border" data-bs-toggle="modal" data-bs-target="#subcategoryModal"><i class="las la-plus"></i></button>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label-pro">Brand <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <select class="form-select form-control-pro form-select-pro" name="brand_id" required>
                                            <option value="">Select...</option>
                                            @foreach ($brands as $brand) 
                                                <option value="{{ $brand->id }}" {{ $product->brand_id == $brand->id ? 'selected' : '' }}>{{ $brand->name }}</option> 
                                            @endforeach
                                        </select>
                                        <button type="button" class="btn btn-light border" data-bs-toggle="modal" data-bs-target="#brandModal"><i class="las la-plus"></i></button>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label-pro">HS Code</label>
                                    <input type="text" class="form-control-pro" name="hs_code" value="{{ $product->hs_code }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label-pro">Model / Series</label>
                                    <input type="text" class="form-control-pro" name="model" value="{{ $product->model }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label-pro">MDR</label>
                                    <input type="text" class="form-control-pro" name="mdr" value="{{ $product->mdr }}">
                                </div>
                                
                                <div class="col-12">
                                    <label class="form-label-pro">Colors / Tags</label>
                                    <div id="colorTagContainer" class="d-flex flex-wrap gap-2 border rounded p-2 bg-white" style="cursor: text; min-height: 45px;">
                                        @if($product->color)
                                            @php $colors = json_decode($product->color) ?? []; if(is_string($colors)) $colors = [$colors]; @endphp
                                            @foreach($colors as $color)
                                                <div class="color-tablet" id="tag_{{ $loop->index }}"><span>{{ $color }}</span> <i class="las la-times-circle remove-tag"></i></div>
                                            @endforeach
                                        @endif
                                        <input type="text" id="colorTagInput" class="border-0 p-0 flex-grow-1 fs-6" style="outline: none; min-width: 120px;" placeholder="Type color and press Enter...">
                                    </div>
                                    <div id="colorHiddenInputs">
                                        @if($product->color)
                                            @foreach($colors as $color)
                                                <input type="hidden" name="color[]" value="{{ $color }}" data-tag-id="tag_{{ $loop->index }}">
                                            @endforeach
                                        @endif
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="d-flex gap-4 p-3 bg-light rounded border">
                                        <div class="form-check"><input class="form-check-input" type="checkbox" name="is_fridge" id="fridge" value="1" {{ $product->is_fridge ? 'checked' : '' }}><label class="form-check-label small fw-bold" for="fridge">Fridge Item</label></div>
                                        <div class="form-check"><input class="form-check-input" type="checkbox" name="is_non_fridge" id="non_fridge" value="1" {{ $product->is_non_fridge ? 'checked' : '' }}><label class="form-check-label small fw-bold" for="non_fridge">Non-Fridge Item</label></div>
                                        <div class="vr mx-2 text-muted opacity-25"></div>
                                        <div class="form-check"><input class="form-check-input" type="checkbox" name="is_fast_moving" id="fast_moving" value="1" {{ $product->is_fast_moving ? 'checked' : '' }}><label class="form-check-label small fw-bold" for="fast_moving">Fast Moving</label></div>
                                        <div class="form-check"><input class="form-check-input" type="checkbox" name="is_slow_moving" id="slow_moving" value="1" {{ $product->is_slow_moving ? 'checked' : '' }}><label class="form-check-label small fw-bold" for="slow_moving">Slow Moving</label></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- SECTION 2: UOM CONFIGURATION --}}
            <div class="section-card">
                <div class="card-header-pro">
                    <h5 class="card-title-pro"><i class="las la-boxes text-info"></i> UOM Configuration</h5>
                    <button type="button" class="btn btn-primary btn-sm rounded-pill px-4 shadow-sm" id="addPackingBtn">
                        <i class="las la-plus"></i> ADD PACKING
                    </button>
                </div>
                <div class="card-body-pro p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="packingTable">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4 py-3 text-muted small fw-bold text-uppercase">Packing Name</th>
                                    <th class="text-muted small fw-bold text-uppercase">Mode</th>
                                    <th class="text-muted small fw-bold text-uppercase">Pcs Per Box (Ratio)</th>
                                    <th class="text-muted small fw-bold text-uppercase">Prices (Buy / Sell)</th>
                                    <th class="pe-4"></th>
                                </tr>
                            </thead>
                            <tbody id="packingTableBody">
                                {{-- Rows via JS or PHP loop --}}
                                @foreach($product->packings as $p)
                                    <tr id="row_existing_{{ $p->id }}">
                                        <td class="ps-4">
                                            <input type="text" name="packings[existing_{{ $p->id }}][name]" class="form-control-pro border-0 p-0 fs-6 fw-bold" placeholder="e.g. 2x100" value="{{ $p->name }}" required>
                                            <input type="hidden" name="packings[existing_{{ $p->id }}][id]" value="{{ $p->id }}">
                                        </td>
                                        <td>
                                            <span class="badge mode-badge {{ $p->pieces_per_box > 1 ? 'bg-primary text-white' : 'bg-light text-dark' }} border">
                                                {{ $p->pieces_per_box > 1 ? 'Carton' : 'Piece' }}
                                            </span>
                                        </td>
                                        <td>
                                            <input type="number" name="packings[existing_{{ $p->id }}][pieces_per_box]" class="form-control-pro border-0 p-0 fs-6 ratio-input" placeholder="Ratio" value="{{ $p->pieces_per_box }}" min="1" required>
                                        </td>
                                        <td style="width: 250px">
                                            <div class="row g-1">
                                                <div class="col-6">
                                                    <div class="input-group input-group-sm">
                                                        <span class="input-group-text bg-transparent border-0 x-small px-1">BUY /pc</span>
                                                        <input type="number" name="packings[existing_{{ $p->id }}][purchase_price]" class="form-control-pro border-0 p-0 fs-6" step="0.01" value="{{ $p->purchase_price }}" required>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="input-group input-group-sm">
                                                        <span class="input-group-text bg-transparent border-0 x-small px-1">SELL /pc</span>
                                                        <input type="number" name="packings[existing_{{ $p->id }}][sale_price]" class="form-control-pro border-0 p-0 fs-6" step="0.01" value="{{ $p->sale_price }}" required>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center pe-4">
                                            <button type="button" class="btn btn-link text-danger p-0 border-0 remove-row">
                                                <i class="las la-trash fs-4"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div id="packingEmptyState" class="text-center py-5 text-muted border-top" style="{{ $product->packings->count() > 0 ? 'display:none;' : '' }}">
                        <i class="las la-info-circle fs-1 opacity-25"></i>
                        <p class="small mt-2 mb-0">No UOM configurations added.<br>Add at least one to save product.</p>
                    </div>
                </div>
            </div>

            {{-- Floating Save Button --}}
            <button type="submit" class="btn-save-floating">
                <i class="las la-check-circle fs-4"></i>
                <span>SAVE PRODUCT</span>
            </button>
        </form>

        {{-- Modals --}}
        {{-- Modals --}}
        <div id="categoryModal" class="modal fade" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-sm">
                <div class="modal-content border-0 shadow-lg" style="border-radius: var(--radius-md);">
                    <form action="{{ route('store.category') }}" method="POST">
                        @csrf
                        <div class="modal-header border-0 pb-0">
                            <h6 class="modal-title fw-bold">New Category</h6>
                            <button type="button" class="btn-close" data-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="page" value="product_page">
                            <div class="mb-3">
                                <label class="form-label-pro">Category Name</label>
                                <input type="text" name="name" class="form-control-pro" required
                                    placeholder="e.g. Ceramics">
                            </div>
                            <button type="submit" class="btn btn-primary w-100 rounded-pill">Create Category</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div id="subcategoryModal" class="modal fade" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-sm">
                <div class="modal-content border-0 shadow-lg" style="border-radius: var(--radius-md);">
                    <form action="{{ route('store.subcategory') }}" method="POST">
                        @csrf
                        <div class="modal-header border-0 pb-0">
                            <h6 class="modal-title fw-bold">New Subcategory</h6>
                            <button type="button" class="btn-close" data-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="page" value="product_page">
                            <div class="mb-3">
                                <label class="form-label-pro">Parent Category</label>
                                <select name="category_id" class="form-select form-control-pro">
                                    @foreach ($categories as $c)
                                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label-pro">Name</label>
                                <input type="text" name="name" class="form-control-pro" required
                                    placeholder="e.g. Floor Tiles">
                            </div>
                            <button type="submit" class="btn btn-primary w-100 rounded-pill">Create Subcategory</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>
@endsection

@section('js')
    {{-- Scripts are provided by layout/app.blade.php --}}

    <script>
        $(document).ready(function() {
            // --- IMAGE HANDLER ---
            const $imgInput = $('#imageInput');
            const $preview = $('#preview');
            const $ph = $('#uploadPlaceholder');
            const $clr = $('#clearImageBtn');

            $imgInput.on('change', function() {
                if (this.files && this.files[0]) {
                    const reader = new FileReader();
                    reader.onload = e => {
                        $preview.attr('src', e.target.result).removeClass('d-none');
                        $ph.addClass('d-none');
                        $clr.removeClass('d-none');
                    }
                    reader.readAsDataURL(this.files[0]);
                }
            });

            $clr.on('click', function(e) {
                e.stopPropagation();
                $imgInput.val('');
                $preview.addClass('d-none').attr('src', '');
                $ph.removeClass('d-none');
                $clr.addClass('d-none');
            });

            // --- BARCODE GEN ---
            function generateBarcode() {
                const code = 'PRD-' + Math.floor(100000 + Math.random() * 900000);
                $('#barcodeInput').val(code);
            }
            $('#generateBarcodeBtn').on('click', generateBarcode);

            // --- SELECT2 & AJAX ---
            $('.form-select-pro').select2({ width: '100%', dropdownParent: $('.page-container') });
            
            $('#category-dropdown').on('change', function() {
                const cid = $(this).val();
                if (!cid) return $('#subcategory-dropdown').html('<option value="">Select...</option>');
                $.get('/get-subcategories/' + cid, function(res) {
                    let html = '<option value="">Select...</option>';
                    res.forEach(s => html += `<option value="${s.id}">${s.name}</option>`);
                    $('#subcategory-dropdown').html(html).trigger('change');
                });
            });

            // --- TAGS / COLORS ---
            const $tagInput = $('#colorTagInput');
            const $tagContainer = $('#colorTagContainer');
            const $tagHidden = $('#colorHiddenInputs');

            $tagInput.on('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    const val = $(this).val().trim();
                    if (val) addTag(val);
                }
            });

            function addTag(val) {
                const id = 'tag_' + Date.now();
                const $tag = $(`<div class="color-tablet" id="${id}"><span>${val}</span> <i class="las la-times-circle remove-tag"></i></div>`);
                const $hidden = $(`<input type="hidden" name="color[]" value="${val}" data-tag-id="${id}">`);
                $tagContainer.prepend($tag);
                $tagHidden.append($hidden);
                $tagInput.val('');
            }

            $tagContainer.on('click', '.remove-tag', function() {
                const $parent = $(this).parent();
                const id = $parent.attr('id');
                $parent.remove();
                $tagHidden.find(`input[data-tag-id="${id}"]`).remove();
            });

            // --- UOM DYNAMIC TABLE ---
            const $packingTableBody = $('#packingTableBody');
            const $addPackingBtn = $('#addPackingBtn');
            const $emptyState = $('#packingEmptyState');

            function updateEmptyState() {
                if ($packingTableBody.children().length === 0) $emptyState.show();
                else $emptyState.hide();
            }

            function addPackingRow(data = {}) {
                const rowId = Date.now();
                const html = `
                    <tr id="row_${rowId}">
                        <td class="ps-4">
                            <input type="text" name="packings[${rowId}][name]" class="form-control-pro border-0 p-0 fs-6 fw-bold" placeholder="e.g. 2x100" value="${data.name || ''}" required>
                        </td>
                        <td>
                            <span class="badge mode-badge bg-light text-dark border">Piece</span>
                        </td>
                        <td>
                            <input type="number" name="packings[${rowId}][pieces_per_box]" class="form-control-pro border-0 p-0 fs-6 ratio-input" placeholder="Ratio" value="${data.ratio || '1'}" min="1" required>
                        </td>
                        <td style="width: 250px">
                            <div class="row g-1">
                                <div class="col-6">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-transparent border-0 x-small px-1">BUY /pc</span>
                                        <input type="number" name="packings[${rowId}][purchase_price]" class="form-control-pro border-0 p-0 fs-6" step="0.01" value="${data.buy || ''}" required>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-transparent border-0 x-small px-1">SELL /pc</span>
                                        <input type="number" name="packings[${rowId}][sale_price]" class="form-control-pro border-0 p-0 fs-6" step="0.01" value="${data.sell || ''}" required>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="text-center pe-4">
                            <button type="button" class="btn btn-link text-danger p-0 border-0 remove-row">
                                <i class="las la-trash fs-4"></i>
                            </button>
                        </td>
                    </tr>
                `;
                $packingTableBody.append(html);
                updateEmptyState();
                updateMode($(`#row_${rowId}`).find('.ratio-input'));
            }

            function updateMode($el) {
                const val = parseInt($el.val());
                const $row = $el.closest('tr');
                const $badge = $row.find('.mode-badge');
                
                if (val > 1) {
                    $badge.text('Carton')
                        .removeClass('bg-light text-dark')
                        .addClass('bg-primary text-white')
                        .css('border-color', 'transparent');
                } else {
                    $badge.text('Piece')
                        .removeClass('bg-primary text-white')
                        .addClass('bg-light text-dark')
                        .css('border-color', '');
                }
            }

            $addPackingBtn.on('click', () => addPackingRow());
            $packingTableBody.on('input', '.ratio-input', function() {
                const val = $(this).val();
                if (val !== '' && parseInt(val) < 1) {
                    Swal.fire({
                        title: 'Invalid Ratio',
                        text: 'Ratio must be at least 1',
                        icon: 'warning',
                        confirmButtonColor: '#3085d6'
                    });
                    $(this).val(1);
                }
                updateMode($(this));
            });

            $packingTableBody.on('click', '.remove-row', function() {
                $(this).closest('tr').remove();
                updateEmptyState();
            });

            // Mutually exclusive flags
            $('#fridge').on('change', function() { if($(this).prop('checked')) $('#non_fridge').prop('checked', false); });
            $('#non_fridge').on('change', function() { if($(this).prop('checked')) $('#fridge').prop('checked', false); });
            $('#fast_moving').on('change', function() { if($(this).prop('checked')) $('#slow_moving').prop('checked', false); });
            $('#slow_moving').on('change', function() { if($(this).prop('checked')) $('#fast_moving').prop('checked', false); });

            // Initialize existing rows' modes
            $packingTableBody.find('.ratio-input').each(function() {
                updateMode($(this));
            });
        });
    </script>
@endsection