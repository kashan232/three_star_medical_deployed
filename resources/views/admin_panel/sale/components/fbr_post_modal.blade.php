{{-- FBR Digital Invoicing Integration Modal & Action Script --}}
<script>
window.FbrPostManager = {
    csrfToken: '{{ csrf_token() }}',
    routes: {
        details: '{{ url("/sales") }}/:id/fbr-details',
        post: '{{ url("/sales") }}/:id/fbr-post',
        validate: '{{ url("/sales") }}/:id/fbr-validate'
    },

    open: function(saleId) {
        if (!saleId) {
            Swal.fire('Error', 'Invalid Sale ID.', 'error');
            return;
        }

        Swal.fire({
            title: 'Fetching FBR Details...',
            html: '<span class="text-muted">Loading invoice verification details from server...</span>',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        const url = this.routes.details.replace(':id', saleId);

        $.ajax({
            url: url,
            method: 'GET',
            dataType: 'json',
            success: (data) => {
                Swal.close();
                if (!data.success) {
                    Swal.fire('Error', data.message || 'Unable to retrieve invoice details.', 'error');
                    return;
                }

                if (data.is_fbr_posted) {
                    Swal.fire({
                        icon: 'info',
                        title: 'Already Posted to FBR',
                        html: `
                            <div class="text-start p-3 bg-light border rounded" style="font-size: 0.9rem;">
                                <div class="mb-1"><strong>Invoice Ref:</strong> ${data.invoice_no}</div>
                                <div class="mb-1"><strong>FBR Invoice No:</strong> <span class="badge bg-success font-monospace">${data.fbr_invoice_no}</span></div>
                                <div class="mb-1"><strong>Posted Date:</strong> ${data.fbr_posted_at || '-'}</div>
                                <div class="mb-1"><strong>Environment:</strong> <span class="badge bg-secondary text-uppercase">${data.environment}</span></div>
                            </div>
                        `,
                        showCancelButton: true,
                        confirmButtonText: '<i class="fas fa-print me-1"></i> View Invoice',
                        cancelButtonText: 'Close',
                        confirmButtonColor: '#059669'
                    }).then((res) => {
                        if (res.isConfirmed) {
                            window.open('{{ url("/sales") }}/' + saleId + '/invoice', '_blank');
                        }
                    });
                    return;
                }

                this.renderConfirmModal(saleId, data);
            },
            error: (xhr) => {
                Swal.fire('Error', xhr.responseJSON?.message || 'Failed to fetch invoice details.', 'error');
            }
        });
    },

    renderConfirmModal: function(saleId, data) {
        let scenarioOptions = '';
        if (data.scenarios) {
            Object.keys(data.scenarios).forEach(key => {
                const isSelected = (key === data.detected_scenario) ? 'selected' : '';
                scenarioOptions += `<option value="${key}" ${isSelected}>[${key}] ${data.scenarios[key]}</option>`;
            });
        }

        const buyerNtn = data.buyer.ntn_cnic || 'Unregistered (0000000000000)';
        const buyerName = data.buyer.name || 'Walk-in / Cash Customer';
        const totalNet = Number(data.total_net || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
        const totalGst = Number(data.total_gst || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
        const envBadge = data.environment === 'production' 
            ? '<span class="badge bg-danger">PRODUCTION</span>' 
            : '<span class="badge bg-warning text-dark">SANDBOX (TEST)</span>';

        const modalHtml = `
            <div class="text-start" style="font-size: 0.88rem;">
                <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                    <span class="fw-bold text-dark">FBR Digital Invoicing Gateway</span>
                    ${envBadge}
                </div>

                <div class="bg-light p-2 rounded border mb-3">
                    <table class="table table-sm table-borderless mb-0" style="font-size: 0.85rem;">
                        <tr>
                            <td class="text-muted py-1" style="width: 120px;">Invoice #:</td>
                            <td class="fw-bold py-1">${data.invoice_no}</td>
                        </tr>
                        <tr>
                            <td class="text-muted py-1">Buyer:</td>
                            <td class="fw-bold py-1">${buyerName}</td>
                        </tr>
                        <tr>
                            <td class="text-muted py-1">Buyer NTN/CNIC:</td>
                            <td class="font-monospace py-1">${buyerNtn}</td>
                        </tr>
                        <tr>
                            <td class="text-muted py-1">Net Total:</td>
                            <td class="fw-bold text-dark py-1">Rs. ${totalNet}</td>
                        </tr>
                        <tr>
                            <td class="text-muted py-1">GST Amount:</td>
                            <td class="fw-bold text-success py-1">Rs. ${totalGst}</td>
                        </tr>
                    </table>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold mb-1" style="font-size: 0.82rem;">FBR Scenario:</label>
                    <select id="swal_fbr_scenario" class="form-select form-select-sm" style="font-size: 0.82rem;">
                        ${scenarioOptions}
                    </select>
                    <div class="form-text text-muted" style="font-size: 0.75rem;">
                        <i class="fas fa-info-circle me-1"></i>Auto-selected based on buyer registration and tax calculation.
                    </div>
                </div>

                <div class="alert alert-warning py-2 px-3 mb-0" style="font-size: 0.83rem;">
                    <i class="fas fa-question-circle me-1"></i> <strong>Do you want to post it to FBR?</strong>
                    <div class="text-muted" style="font-size: 0.77rem; margin-top: 2px;">
                        Once confirmed, this invoice will be transmitted to the official FBR Digital Invoicing Gateway and an official FBR Invoice Number will be assigned.
                    </div>
                </div>
            </div>
        `;

        Swal.fire({
            title: '<i class="fas fa-file-invoice-dollar text-warning me-2"></i>Post to FBR',
            html: modalHtml,
            showCancelButton: true,
            confirmButtonText: '<i class="fas fa-cloud-upload-alt me-1"></i> Yes, Post to FBR',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#f59e0b',
            cancelButtonColor: '#64748b',
            focusConfirm: false,
            customClass: {
                popup: 'rounded-4 shadow-lg'
            },
            preConfirm: () => {
                const scenario = document.getElementById('swal_fbr_scenario').value;
                if (!scenario) {
                    Swal.showValidationMessage('Please select an FBR scenario');
                    return false;
                }
                return { scenario_id: scenario };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                this.executePost(saleId, result.value.scenario_id, data);
            }
        });
    },

    executePost: function(saleId, scenarioId, previousData) {
        Swal.fire({
            title: 'Transmitting to FBR Gateway...',
            html: `
                <div class="py-2">
                    <p class="text-muted mb-2">Connecting to FBR Digital Invoicing service and submitting payload.</p>
                    <div class="spinner-border text-warning my-2" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="small text-muted mb-0">Please wait. Gateway response typically takes 5–15 seconds.</p>
                </div>
            `,
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        const url = this.routes.post.replace(':id', saleId);

        $.ajax({
            url: url,
            method: 'POST',
            data: {
                _token: this.csrfToken,
                scenario_id: scenarioId
            },
            dataType: 'json',
            success: (res) => {
                if (res.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Invoice Posted to FBR Successfully!',
                        html: `
                            <div class="text-start p-3 bg-light border rounded mt-2" style="font-size: 0.9rem;">
                                <div class="mb-2">
                                    <span class="text-muted">Official FBR Invoice #:</span><br>
                                    <span class="badge bg-success font-monospace" style="font-size: 1rem; padding: 6px 12px;">${res.fbr_invoice_no}</span>
                                </div>
                                <div class="mb-1"><strong>Status:</strong> <span class="badge bg-success">Valid</span></div>
                                <div class="mb-1"><strong>Gateway Code:</strong> <span class="font-monospace">${res.fbr_status_code || '00'}</span></div>
                                <div class="small text-muted mt-2">${res.message || 'Invoice successfully recorded on FBR portal.'}</div>
                            </div>
                        `,
                        showCancelButton: true,
                        confirmButtonText: '<i class="fas fa-print me-1"></i> View / Print Invoice',
                        cancelButtonText: 'Done',
                        confirmButtonColor: '#059669',
                        cancelButtonColor: '#64748b'
                    }).then((btnRes) => {
                        if (btnRes.isConfirmed) {
                            window.open('{{ url("/sales") }}/' + saleId + '/invoice', '_blank');
                        }
                    });

                    // Trigger global event for reactive UI updates
                    $(document).trigger('fbr:posted', {
                        saleId: saleId,
                        fbrInvoiceNo: res.fbr_invoice_no,
                        status: 'posted'
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'FBR Submission Failed',
                        html: `
                            <div class="alert alert-danger text-start p-2 mb-2" style="font-size:0.85rem; max-height:180px; overflow-y:auto;">
                                <strong>Error:</strong> ${res.message || 'Unknown gateway rejection.'}
                            </div>
                            <p class="small text-muted mb-0">You can adjust the scenario or verify the buyer details in the FBR Settings.</p>
                        `,
                        confirmButtonColor: '#ef4444'
                    });
                }
            },
            error: (xhr) => {
                const msg = xhr.responseJSON?.message || 'Server error while transmitting to FBR.';
                Swal.fire({
                    icon: 'error',
                    title: 'FBR Transmission Error',
                    html: `
                        <div class="alert alert-danger text-start p-2 mb-2" style="font-size:0.85rem; max-height:180px; overflow-y:auto;">
                            ${msg}
                        </div>
                        <p class="small text-muted mb-0">Please verify connection or check FBR transmission logs.</p>
                    `,
                    confirmButtonColor: '#ef4444'
                });
            }
        });
    }
};

// Global click listener for any elements marked with .fbr-post-btn
$(document).on('click', '.fbr-post-btn', function(e) {
    e.preventDefault();
    const saleId = $(this).data('id') || $(this).attr('data-sale-id');
    if (saleId) {
        window.FbrPostManager.open(saleId);
    }
});
</script>
