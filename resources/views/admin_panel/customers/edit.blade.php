@extends('admin_panel.layout.app')
@section('content')
    <div class="main-content">
        <div class="main-content-inner">
            <div class="container">
                <h3>Edit Customer</h3>
                <form action="{{ route('customers.update', $customer->id) }}" method="POST">
                    @csrf
                    <!-- No PUT, since we're using POST only -->

                    <div class="row mb-3">
                        <div class="col-md-2">
                            <label>Customer ID:</label>
                            <input type="text" class="form-control" name="customer_id" readonly
                                value="{{ $customer->customer_id }}">
                        </div>
                        <div class="col-md-5">
                            <label>Customer:</label>
                            <input type="text" class="form-control" name="customer_name"
                                value="{{ $customer->customer_name }}">
                        </div>
                        <div class="col-md-5">
                            <label>کسٹمر کا نام:</label>
                            <input type="text" class="form-control text-end" name="customer_name_ur" dir="rtl"
                                value="{{ $customer->customer_name_ur }}">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label>Customer Type:</label>
                            <select class="form-control" name="customer_type">
                                <option value="Main Customer"
                                    {{ $customer->customer_type == 'Main Customer' ? 'selected' : '' }}>Main Customer
                                </option>
                                <option value="Walking Customer"
                                    {{ $customer->customer_type == 'Walking Customer' ? 'selected' : '' }}>Walking Customer
                                </option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label>Sales Officer:</label>
                            <select class="form-control" name="sales_officer_id">
                                <option value="">-- Select Officer --</option>
                                @foreach ($salesOfficers as $officer)
                                    <option value="{{ $officer->id }}"
                                        {{ $customer->sales_officer_id == $officer->id ? 'selected' : '' }}>
                                        {{ $officer->full_name }} @if ($officer->phone)
                                            ({{ $officer->phone }})
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label>NTN / CNIC no:</label>
                            <input type="text" class="form-control" name="cnic" value="{{ $customer->cnic }}">
                        </div>
                        <div class="col-md-4">
                            <label>Filer Type:</label>
                            <select class="form-control" name="filer_type">
                                <option value="filer" {{ $customer->filer_type == 'filer' ? 'selected' : '' }}>Filer
                                </option>
                                <option value="non filer" {{ $customer->filer_type == 'non filer' ? 'selected' : '' }}>Non
                                    Filer</option>
                                <option value="exempt" {{ $customer->filer_type == 'exempt' ? 'selected' : '' }}>Exempt
                                </option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label>Zone:</label>
                            <select class="form-control" name="zone">
                                <option value="Hyderabad" {{ $customer->zone == 'Hyderabad' ? 'selected' : '' }}>Hyderabad
                                </option>
                                <option value="Karachi" {{ $customer->zone == 'Karachi' ? 'selected' : '' }}>Karachi
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label>Contact Person:</label>
                            <input type="text" class="form-control" name="contact_person"
                                value="{{ $customer->contact_person }}">
                        </div>
                        <div class="col-md-4">
                            <label>Mobile#:</label>
                            <input type="text" class="form-control" name="mobile" value="{{ $customer->mobile }}">
                        </div>
                        <div class="col-md-4">
                            <label>Email Address:</label>
                            <input type="email" class="form-control" name="email_address"
                                value="{{ $customer->email_address }}">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label>Contact Person-2:</label>
                            <input type="text" class="form-control" name="contact_person_2"
                                value="{{ $customer->contact_person_2 }}">
                        </div>
                        <div class="col-md-4">
                            <label>Mobile# 2:</label>
                            <input type="text" class="form-control" name="mobile_2" value="{{ $customer->mobile_2 }}">
                        </div>
                        <div class="col-md-4">
                            <label>Email Address 2:</label>
                            <input type="email" class="form-control" name="email_address_2"
                                value="{{ $customer->email_address_2 }}">
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-4">
                            <label>Opening Balance (Dr):</label>
                            <input type="number" step="0.01" class="form-control" name="opening_balance"
                                value="{{ $customer->opening_balance ?? 0 }}">
                        </div>
                        <div class="col-md-4">
                            <label class="text-success fw-bold">Credit Limit:</label>
                            <input type="number" step="0.01" class="form-control border-success bg-light" name="balance_range"
                                value="{{ $customer->balance_range ?? 0 }}">
                        </div>
                        <div class="col-md-4">
                            <label>Credit Terms:</label>
                            @php
                                $cTerms = $customer->credit_terms;
                                $isCustom = !in_array($cTerms, [0, 7, 15, 30]) && !is_null($cTerms);
                            @endphp
                            <select class="form-control" name="credit_terms" id="creditTermsSelect" onchange="toggleCustomCreditTerms()">
                                <option value="0" {{ $cTerms == 0 ? 'selected' : '' }}>Cash / Immediate</option>
                                <option value="7" {{ $cTerms == 7 ? 'selected' : '' }}>7 Days</option>
                                <option value="15" {{ $cTerms == 15 ? 'selected' : '' }}>15 Days</option>
                                <option value="30" {{ $cTerms == 30 ? 'selected' : '' }}>30 Days</option>
                                <option value="custom" {{ $isCustom ? 'selected' : '' }}>Custom Days</option>
                            </select>
                            <input type="number" class="form-control mt-2" name="custom_credit_terms" id="customCreditTermsInput" 
                                placeholder="Enter days" style="display: {{ $isCustom ? 'block' : 'none' }};" min="1" value="{{ $isCustom ? $cTerms : '' }}">
                        </div>
                    </div>

                    <div class="col-md-6 mb-4">
                        <label>Address:</label>
                        <textarea rows="4" class="form-control" name="address">{{ $customer->address }}</textarea>
                    </div>

                    <div class="text-center mt-3">
                        <button class="btn btn-primary" type="submit">Update Customer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    </div>
@endsection

@section('js')
<script>
    function toggleCustomCreditTerms() {
        var select = document.getElementById('creditTermsSelect');
        var customInput = document.getElementById('customCreditTermsInput');
        if (select.value === 'custom') {
            customInput.style.display = 'block';
            customInput.setAttribute('required', 'required');
        } else {
            customInput.style.display = 'none';
            customInput.removeAttribute('required');
            customInput.value = '';
        }
    }
</script>
@endsection
