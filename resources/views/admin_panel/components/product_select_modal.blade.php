{{-- ============================================================
     REUSABLE ADVANCED PRODUCT SELECTION MODAL
     Usage: @include('admin_panel.components.product_select_modal')
     Trigger per-row: window.ERPProductModal.open({ targetRow: $row, priceField: 'purchase'|'sale', onSelect: fn })
     ============================================================ --}}

<style>
    /* ── Row Product Button (replaces Select2) ── */
    .product-select-btn {
        display: block; width: 100%; min-width: 180px;
        padding: 0.3rem 0.65rem;
        background: #fff; color: #212529;
        border: 1px solid #333; border-radius: 3px;
        font-size: 0.82rem; font-weight: 400; text-align: left;
        cursor: pointer; white-space: nowrap; overflow: hidden;
        text-overflow: ellipsis; transition: border-color .15s;
        line-height: 1.5;
    }
    .product-select-btn:hover  { border-color: #3b82f6; color: #1d4ed8; }
    .product-select-btn.has-value { font-weight: 600; color: #0f172a; border-color: #64748b; }
    .product-select-btn .psm-btn-arrow { float: right; opacity:.45; margin-left:4px; }

    /* ── Modal Overrides ── */
    #erpProductModal .modal-dialog { max-width: 92vw; width: 1100px; }
    #erpProductModal .modal-content { border: none; border-radius: 16px; overflow: hidden; box-shadow: 0 25px 60px rgba(0,0,0,.18); }
    #erpProductModal .modal-header {
        background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 100%);
        color: white; padding: 1rem 1.5rem; border: none;
    }
    #erpProductModal .modal-title { font-weight: 700; font-size: 1.1rem; display: flex; align-items: center; gap: .5rem; }
    #erpProductModal .btn-close { filter: invert(1) brightness(2); }

    /* ── Filter Bar ── */
    .psm-filters { background: #f8fafc; border-bottom: 1px solid #e2e8f0; padding: .875rem 1.25rem; display: flex; flex-wrap: wrap; gap: .625rem; align-items: center; }
    .psm-search { position: relative; flex: 1; min-width: 220px; }
    .psm-search i { position: absolute; left: .875rem; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: .9rem; pointer-events: none; }
    .psm-search input { padding-left: 2.4rem; border-radius: 8px; border: 1px solid #cbd5e1; height: 38px; font-size: .875rem; width: 100%; transition: all .2s; background:#fff; }
    .psm-search input:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,.12); outline: none; }
    .psm-filter-select { height: 38px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 .75rem; font-size: .8rem; background: #fff; color: #334155; min-width: 130px; cursor:pointer; }
    .psm-filter-select:focus { border-color: #3b82f6; outline: none; }
    .psm-btn-search {
        height: 38px; padding: 0 1.1rem; border-radius: 8px; font-weight: 600; font-size: .82rem;
        background: linear-gradient(to bottom, #3b82f6, #2563eb); color: white; border: none; cursor: pointer;
        display: flex; align-items: center; gap: .4rem; transition: all .2s;
    }
    .psm-btn-search:hover { background: linear-gradient(to bottom, #2563eb, #1d4ed8); }
    .psm-btn-reset { height: 38px; padding: 0 .75rem; border-radius: 8px; font-size: .82rem; border: 1px solid #cbd5e1; background: #fff; color: #64748b; cursor:pointer; }

    /* ── Table ── */
    .psm-table-wrap { max-height: 430px; overflow-y: auto; position: relative; }
    #psmTable { width: 100%; border-collapse: collapse; font-size: .84rem; }
    #psmTable thead th {
        position: sticky; top: 0; z-index: 2;
        background: #f1f5f9; padding: .6rem .75rem;
        font-size: .72rem; font-weight: 700; text-transform: uppercase;
        letter-spacing: .04em; color: #475569; border-bottom: 2px solid #e2e8f0;
        white-space: nowrap;
    }
    #psmTable tbody td { padding: .55rem .75rem; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }
    #psmTable tbody tr:hover td { background: #eff6ff; cursor: pointer; }
    #psmTable tbody tr.psm-selected td { background: #eff6ff !important; font-weight: 500; }
    #psmTable tbody tr.psm-selected td:first-child { border-left: 4px solid #2563eb; position: relative; }
    /* Show checkmark only when NOT in multi-import mode to avoid clashing with checkboxes */
    .psm-single-mode #psmTable tbody tr.psm-selected td:first-child::after {
        content: '✓'; position: absolute; left: 8px; top: 50%; transform: translateY(-50%);
        color: #2563eb; font-weight: 800; font-size: 1rem;
    }
    .psm-check { width: 18px; height: 18px; cursor: pointer; accent-color: #2563eb; }
    .psm-badge { display: inline-block; padding: .15rem .5rem; border-radius: 20px; font-size: .72rem; font-weight: 600; }
    .psm-badge-cat { background: #e0f2fe; color: #0369a1; }
    .psm-badge-brand { background: #f0fdf4; color: #166534; }
    .psm-badge-uom { background: #fef3c7; color: #92400e; }
    .psm-stock-ok { color: #059669; font-weight: 700; }
    .psm-stock-low { color: #dc2626; font-weight: 700; }

    /* ── State Messages ── */
    #psmStateRow td { text-align: center; padding: 2.5rem 1rem; color: #64748b; }
    .psm-spinner { display: inline-block; width: 28px; height: 28px; border: 3px solid #e2e8f0; border-top-color: #3b82f6; border-radius: 50%; animation: psm-spin .7s linear infinite; }
    @keyframes psm-spin { to { transform: rotate(360deg); } }

    /* ── Footer ── */
    .psm-footer { background: #f8fafc; border-top: 1px solid #e2e8f0; padding: .875rem 1.25rem; display: flex; justify-content: space-between; align-items: center; gap: 1rem; }
    .psm-count-badge { background: #dbeafe; color: #1d4ed8; border-radius: 20px; padding: .2rem .75rem; font-size: .8rem; font-weight: 700; }
    .psm-total-info { font-size: .8rem; color: #64748b; }
    .psm-btn-add {
        padding: .55rem 1.5rem; border-radius: 8px; font-weight: 700; font-size: .88rem;
        background: linear-gradient(to bottom, #10b981, #059669); color: white; border: none; cursor: pointer;
        display: flex; align-items: center; gap: .5rem; transition: all .2s;
    }
    .psm-btn-add:hover { background: linear-gradient(to bottom, #059669, #047857); }
    .psm-btn-add:disabled { opacity: .5; cursor: not-allowed; }
    .psm-btn-cancel { padding: .55rem 1rem; border-radius: 8px; font-size: .85rem; border: 1px solid #cbd5e1; background: #fff; color: #475569; cursor: pointer; }

    /* ── Select All bar (multi-select mode) ── */
    .psm-select-all-bar { background: #eff6ff; border-bottom: 1px solid #dbeafe; padding: .4rem 1.1rem; display: flex; align-items: center; gap: .75rem; font-size: .8rem; color: #1d4ed8; font-weight: 600; }
    .psm-select-all-bar label { cursor: pointer; display: flex; align-items: center; gap: .4rem; margin: 0; }

    /* Single-select mode hint */
    .psm-single-hint { background: #fef3c7; border-bottom: 1px solid #fde68a; padding: .4rem 1.1rem; font-size: .8rem; color: #92400e; font-weight: 600; }

    /* Multi-toggle switch */
    .psm-multi-toggle-wrap {
        display: flex; align-items: center; gap: .5rem;
        padding: .4rem .8rem; background: #fff; border: 1px solid #cbd5e1;
        border-radius: 8px; margin-left: auto;
    }
    .psm-multi-toggle-wrap .form-check-input { cursor: pointer; width: 2.2em; height: 1.1em; margin-top: 0; }
    .psm-multi-toggle-wrap label { cursor: pointer; font-size: .8rem; font-weight: 700; color: #1e293b; user-select: none; }
</style>

{{-- Modal HTML --}}
<div class="modal fade" id="erpProductModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-boxes"></i> Product Catalogue</h5>
                <button type="button" class="btn-close" data-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="psm-filters">
                <div class="psm-search">
                    <i class="bi bi-search"></i>
                    <input type="text" id="psmSearchInput" placeholder="Search by name, code, brand, category..." autocomplete="off">
                </div>
                <select id="psmCategoryFilter" class="psm-filter-select"><option value="">All Categories</option></select>
                <select id="psmBrandFilter" class="psm-filter-select"><option value="">All Brands</option></select>
                <button type="button" class="psm-btn-search" id="psmBtnSearch"><i class="bi bi-funnel"></i> Filter</button>
                <button type="button" class="psm-btn-reset" id="psmBtnReset"><i class="bi bi-x-circle"></i> Reset</button>

                <div class="psm-multi-toggle-wrap">
                    <div class="form-check form-switch m-0 d-flex align-items-center gap-2">
                        <input class="form-check-input" type="checkbox" id="psmMultiToggle">
                        <label class="form-check-label" for="psmMultiToggle">Multiple Import</label>
                    </div>
                </div>
            </div>

            <div id="psmModeBar"><!-- injected by JS --></div>

            <div class="modal-body p-0">
                <div class="psm-table-wrap" id="psmTableWrap">
                    <table id="psmTable">
                        <thead>
                            <tr>
                                <th id="psmCheckCol" style="width:36px"></th>
                                <th>Product</th>
                                <th style="width:100px">Code</th>
                                <th style="width:100px">HS Code</th>
                                <th style="width:110px">Category</th>
                                <th style="width:120px">Brand</th>
                                <th style="width:80px">UOM</th>
                                <th style="width:90px" class="text-end">Buy Price</th>
                                <th style="width:90px" class="text-end">Sale Price</th>
                                <th style="width:90px" class="text-end">Stock</th>
                            </tr>
                        </thead>
                        <tbody id="psmTbody"></tbody>
                    </table>
                    <div id="psmSentinel" style="height:1px;"></div>
                </div>
            </div>

            <div class="psm-footer" id="psmFooter">
                <div class="d-flex align-items-center gap-2">
                    <span class="psm-count-badge" id="psmCountBadge" style="display:none;"><span id="psmSelectedCount">0</span> selected</span>
                    <span class="psm-total-info" id="psmTotalInfo"></span>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="psm-btn-cancel" data-dismiss="modal">Cancel</button>
                    <button type="button" class="psm-btn-add" id="psmBtnAdd" style="display:none;">
                        <i class="bi bi-check2-square"></i> Add to Form
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
/* ─────────────────────────────────────────────
   ERP Product Selection Modal
   Deferred init — waits for jQuery + Bootstrap
───────────────────────────────────────────── */
(function () {
    'use strict';

    /* ── State ── */
    var S = {
        page: 1, loading: false, hasMore: true, total: 0,
        query: '', categoryId: '', brandId: '',
        selected: new Map(),
        priceField: 'purchase',
        singleSelect: false,   // true when opened from a row button
        multiImport: false,    // user toggle for multiple selection
        targetRow: null,       // jQuery row ref in single-select mode
        onSelect: null,
        existingIds: [],
    };

    /* Expose public API immediately; jQuery-dependent code runs after ready */
    window.ERPProductModal = {
        open: function (opts) {
            opts = opts || {};
            S.priceField   = opts.priceField   || 'purchase';
            S.onSelect     = opts.onSelect     || null;
            S.existingIds  = opts.existingIds  || [];
            S.targetRow    = opts.targetRow    || null;
            S.singleSelect = !!(opts.targetRow || opts.singleSelect);
            S.selected.clear();

            if (opts.selectedIds && Array.isArray(opts.selectedIds)) {
                opts.selectedIds.forEach(id => S.selected.set(parseInt(id), true));
            }

            if (window._psmReady) {
                _open();
            } else {
                // Queue for when DOM is ready
                window._psmPending = true;
            }
        }
    };

    /* ── Init when jQuery is available ── */
    function _waitForJQuery() {
        if (typeof $ !== 'undefined' && $.fn && $.fn.on) {
            _init();
        } else {
            setTimeout(_waitForJQuery, 50);
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        _waitForJQuery();
    });

    /* ── One-time setup ── */
    function _init() {
        window._psmReady = true;

        if (window._psmPending) {
            window._psmPending = false;
            _open();
        }

        /* Search */
        $('#psmBtnSearch').on('click', function () {
            S.query      = $('#psmSearchInput').val().trim();
            S.categoryId = $('#psmCategoryFilter').val();
            S.brandId    = $('#psmBrandFilter').val();
            _fetch(true);
        });
        $('#psmSearchInput').on('keydown', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); $('#psmBtnSearch').trigger('click'); }
        });
        $('#psmBtnReset').on('click', function () {
            S.query = ''; S.categoryId = ''; S.brandId = '';
            $('#psmSearchInput').val('');
            $('#psmCategoryFilter').val('');
            $('#psmBrandFilter').val('');
            _fetch(true);
        });

        /* Multi-Import Toggle */
        $('#psmMultiToggle').on('change', function () {
            S.multiImport = this.checked;
            _updateModeUI();
            _fetch(true); // Re-fetch to update checkboxes
        });

        /* Infinite scroll */
        var sentinel  = document.getElementById('psmSentinel');
        var tableWrap = document.getElementById('psmTableWrap');
        if (sentinel && tableWrap) {
            var io = new IntersectionObserver(function (entries) {
                if (entries[0].isIntersecting && S.hasMore && !S.loading) _fetch(false);
            }, { root: tableWrap, rootMargin: '100px' });
            io.observe(sentinel);
        }

        /* Row click — single: fire+close; multi: toggle checkbox */
        $('#erpProductModal').on('click', '#psmTbody tr', function (e) {
            if (e.target.type === 'checkbox') return; // let checkbox handle itself
            var pJson = this.getAttribute('data-pjson');
            var p = pJson ? JSON.parse(pJson) : null;
            if (!p) return;

            if (!S.multiImport) {
                _fireSelect([p]);
                _hideModal();
            } else {
                /* toggle checkbox */
                var $cb = $(this).find('.psm-check');
                var nowChecked = !$cb.prop('checked');
                $cb.prop('checked', nowChecked);
                var id = parseInt($cb.attr('data-id'));
                if (nowChecked) { S.selected.set(id, p); $(this).addClass('psm-selected'); }
                else            { S.selected.delete(id); $(this).removeClass('psm-selected'); }
                _updateCountUI();
            }
        });

        /* Checkbox direct click (in case user clicks the checkbox itself) */
        $('#erpProductModal').on('change', '#psmTbody .psm-check', function () {
            var $tr   = $(this).closest('tr');
            var id    = parseInt($(this).attr('data-id'));
            var pJson = $tr[0] ? $tr[0].getAttribute('data-pjson') : null;
            var p     = pJson ? JSON.parse(pJson) : null;
            if (!p) return;
            if (this.checked) { S.selected.set(id, p); $tr.addClass('psm-selected'); }
            else              { S.selected.delete(id); $tr.removeClass('psm-selected'); }
            _updateCountUI();
        });

        /* Select all on page */
        $('#erpProductModal').on('change', '#psmCheckAll', function () {
            var checked = this.checked;
            $('#psmTbody .psm-check').each(function () {
                var $tr   = $(this).closest('tr');
                var id    = parseInt($(this).attr('data-id'));
                var pJson = $tr[0] ? $tr[0].getAttribute('data-pjson') : null;
                var p     = pJson ? JSON.parse(pJson) : null;
                if (!p) return;
                this.checked = checked;
                if (checked) { S.selected.set(id, p); $tr.addClass('psm-selected'); }
                else         { S.selected.delete(id); $tr.removeClass('psm-selected'); }
            });
            _updateCountUI();
        });

        /* Add to Form button (multi-select) */
        $('#psmBtnAdd').on('click', function () {
            if (!S.selected.size) return;
            _fireSelect(Array.from(S.selected.values()));
            _hideModal();
        });

        /* Cancel / X button — scoped to THIS modal only */
        $('#erpProductModal').on('click', '.psm-btn-cancel, .btn-close', function () {
            _hideModal();
        });

        /* Reset on modal hide event */
        document.getElementById('erpProductModal').addEventListener('hidden.bs.modal', function () {
            S.selected.clear(); _updateCountUI();
        });

        _loadFilters();
    }

    /* ── Open ── */
    function _open() {
        S.page = 1; S.hasMore = true; S.loading = false;
        S.selected.clear();
        S.query = ''; S.categoryId = ''; S.brandId = '';
        $('#psmSearchInput').val('');
        $('#psmCategoryFilter').val('');
        $('#psmBrandFilter').val('');

        // Set initial multi-import state
        // If opened for a specific row, default to single-select (multiImport = false)
        // If opened from a general "Add" button, default to multi-select
        S.multiImport = !S.singleSelect;
        $('#psmMultiToggle').prop('checked', S.multiImport);

        _updateModeUI();
        _showModal();
        _fetch(true);
    }

    function _updateModeUI() {
        var $modeBar = $('#psmModeBar');
        var $modal = $('#erpProductModal');

        if (!S.multiImport) {
            $modal.addClass('psm-single-mode');
            $modeBar.html('<div class="psm-single-hint"><i class="bi bi-cursor-fill me-1"></i> Click any product to select it for this row.</div>');
            $('#psmCheckCol').html('');
            $('#psmBtnAdd').hide();
            $('#psmCountBadge').hide();
        } else {
            $modal.removeClass('psm-single-mode');
            $modeBar.html('<div class="psm-select-all-bar"><label><input type="checkbox" id="psmCheckAll" class="psm-check"> Select all on page</label><span id="psmPageInfo" class="text-muted fw-normal ms-2"></span></div>');
            $('#psmCheckCol').html('<input type="checkbox" disabled style="opacity:0">'); // placeholder
            $('#psmBtnAdd').show().prop('disabled', S.selected.size === 0);
            $('#psmCountBadge').show();
            _updateCountUI();
        }
    }

    /* ── Show / Hide modal (works with BS4 & BS5) ── */
    function _showModal() {
        var el = document.getElementById('erpProductModal');
        if (!el) return;
        if ($ && $.fn.modal) {
            $(el).modal('show');
        } else if (typeof bootstrap !== 'undefined') {
            bootstrap.Modal.getOrCreateInstance(el).show();
        } else {
            el.style.display = 'block'; el.classList.add('show');
            document.body.classList.add('modal-open');
        }
    }
    function _hideModal() {
        var el = document.getElementById('erpProductModal');
        if (!el) return;
        if ($ && $.fn.modal) {
            $(el).modal('hide');
        } else if (typeof bootstrap !== 'undefined') {
            bootstrap.Modal.getOrCreateInstance(el).hide();
        } else {
            el.style.display = 'none'; el.classList.remove('show');
            document.body.classList.remove('modal-open');
            S.selected.clear(); _updateCountUI();
        }
    }

    /* ── Fire callback ── */
    function _fireSelect(arr) {
        if (typeof S.onSelect === 'function') S.onSelect(arr);
    }

    /* ── Load filter dropdowns ── */
    function _loadFilters() {
        var loaded = false;
        try {
            $.getJSON('/api/product-filters', function (data) {
                loaded = true;
                var $cat   = $('#psmCategoryFilter');
                var $brand = $('#psmBrandFilter');
                $cat.find('option:not(:first)').remove();
                $brand.find('option:not(:first)').remove();
                (data.categories || []).forEach(function (c) { $cat.append('<option value="' + c.id + '">' + _esc(c.name) + '</option>'); });
                (data.brands     || []).forEach(function (b) { $brand.append('<option value="' + b.id + '">' + _esc(b.name) + '</option>'); });
            });
        } catch (e) { /* silent */ }
    }

    /* ── Fetch products ── */
    function _fetch(fresh) {
        if (S.loading || (!S.hasMore && !fresh)) return;
        if (fresh) { S.page = 1; S.hasMore = true; }
        S.loading = true;

        if (fresh) {
            $('#psmTbody').empty();
            _setState('loading', 'Loading products…');
        } else {
            $('#psmTbody').append('<tr id="psmLoadMore"><td colspan="9" style="text-align:center;padding:.6rem;color:#94a3b8;font-size:.8rem;"><div class="psm-spinner" style="width:16px;height:16px;border-width:2px;display:inline-block;vertical-align:middle;margin-right:.4rem;"></div>Loading more…</td></tr>');
        }

        $.ajax({
            url: '{{ route("search-products") }}',
            method: 'GET',
            data: { q: S.query, category_id: S.categoryId, brand_id: S.brandId, page: S.page, per_page: 25, branch_id: $('input[name="branch_id"]').val() || '' },
            timeout: 15000,
            success: function (res) {
                S.loading = false;
                $('#psmLoadMore').remove();
                var rows       = res.results || [];
                var pagination = res.pagination || {};
                S.hasMore = pagination.more || false;
                S.total   = pagination.total || rows.length;
                if (fresh) $('#psmTbody').empty();

                if (rows.length === 0 && fresh) {
                    _setState('empty', 'No products found. Try a different search.');
                    $('#psmTotalInfo').text('');
                    return;
                }

                $('#psmTotalInfo').text('Total: ' + S.total + ' products');
                if (S.multiImport) {
                    var shown = Math.min(S.page * 25, S.total);
                    $('#psmPageInfo').text('Showing ' + shown + ' of ' + S.total);
                }

                rows.forEach(function (p) { _appendRow(p); });

                /* Re-highlight already-selected rows & update S.selected with full objects */
                rows.forEach(function (p) {
                    var id = parseInt(p.id);
                    if (S.selected.has(id)) {
                        S.selected.set(id, p); // upgrade from ID/true to full object
                        $('#psmTbody tr[data-id="' + id + '"]').addClass('psm-selected').find('.psm-check').prop('checked', true);
                    }
                });
                S.page++;
            },
            error: function (xhr, status) {
                S.loading = false;
                $('#psmLoadMore').remove();
                var msg = status === 'timeout' ? 'Connection is slow. Click Retry.' : 'Failed to load products.';
                _setState('error', msg);
            }
        });
    }

    /* ── Render one row ── */
    function _appendRow(p) {
        var isDup    = S.existingIds.includes(parseInt(p.id));
        var buyPrice = parseFloat(p.purchase_price_per_piece || 0).toFixed(2);
        var selPrice = parseFloat(p.sale_price_per_piece    || 0).toFixed(2);
        var stockPcs = parseFloat(p.stock_pieces || 0);
        var stockCls = stockPcs <= 0 ? 'psm-stock-low' : 'psm-stock-ok';
        var uomText = p.uom_name || 'Pcs';
        if (p.packings && p.packings.length > 0) {
            uomText = p.packings.map(pkg => _esc(pkg.name)).join(', ');
        }
        var dupTag   = isDup ? ' <span class="psm-badge" style="background:#fef9c3;color:#854d0e;font-size:.65rem;">In Form</span>' : '';
        var checkCol = !S.multiImport ? '<td></td>' : '<td style="padding:.4rem .75rem;"><input type="checkbox" class="psm-check" data-id="' + p.id + '"></td>';

        var $tr = $('<tr data-id="' + p.id + '">' + checkCol +
            '<td><div style="font-weight:600;color:#1e293b;line-height:1.3;">' + _esc(p.item_name) + dupTag + '</div>' +
                '<div style="font-size:.72rem;color:#94a3b8;">' + _esc(p.sub_category_name || '') + '</div></td>' +
            '<td><span class="psm-badge" style="background:#f1f5f9;color:#475569;font-size:.72rem;">' + _esc(p.item_code) + '</span></td>' +
            '<td><span class="psm-badge" style="background:#fff7ed;color:#9a3412;font-size:.72rem;">' + _esc(p.hs_code || '—') + '</span></td>' +
            '<td><span class="psm-badge psm-badge-cat">'   + _esc(p.category_name  || '—') + '</span></td>' +
            '<td><span class="psm-badge psm-badge-brand">' + _esc(p.brand_name     || '—') + '</span></td>' +
            '<td><span class="psm-badge psm-badge-uom">'   + uomText + '</span></td>' +
            '<td class="text-end" style="font-weight:600;">Rs ' + buyPrice + '</td>' +
            '<td class="text-end" style="color:#059669;font-weight:600;">Rs ' + selPrice + '</td>' +
            '<td class="text-end ' + stockCls + '">' + _esc(p.stock || '0 Pcs') + '</td>' +
            '</tr>');
        $tr[0].setAttribute('data-pjson', JSON.stringify(p));
        $('#psmTbody').append($tr);
    }

    /* ── UI helpers ── */
    function _setState(type, msg) {
        var icon = { loading: '<div class="psm-spinner"></div>', error: '⚠️', empty: '📦' }[type] || '';
        var retry = type === 'error' ? '<br><button onclick="ERPProductModal.open()" class="psm-btn-reset mt-2">↺ Retry</button>' : '';
        $('#psmTbody').html('<tr id="psmStateRow"><td colspan="9" style="text-align:center;padding:2.5rem 1rem;color:#64748b;">' + icon + '<p class="mt-2 mb-0" style="font-size:.9rem;">' + msg + '</p>' + retry + '</td></tr>');
    }

    function _updateCountUI() {
        var n = S.selected.size;
        $('#psmSelectedCount').text(n);
        $('#psmBtnAdd').prop('disabled', n === 0);
    }

    function _esc(s) {
        return String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

})();
</script>
