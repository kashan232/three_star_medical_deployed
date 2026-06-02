<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\Vendor;
use App\Models\CustomerLedger;
use App\Models\VendorLedger;
use Illuminate\Support\Facades\Auth;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Traits\BranchScoped;
use App\Models\Branch;

class PartyController extends Controller
{
    use BranchScoped;
    public function edit($id, Request $request)
    {
        $type = $request->get('type', 'Customer');
        
        if ($type == 'Customer') {
            $party = Customer::findOrFail($id);
            $party->code = $party->customer_id;
            $party->title = $party->customer_name;
            $party->phone = $party->mobile;
            $party->email = $party->email_address;
            $party->credit_limit = $party->balance_range;
        } else {
            $party = Vendor::findOrFail($id);
            $party->code = $party->vendor_code;
            $party->title = $party->name;
            // email, phone, etc already match
        }

        $branches = $this->isSuperAdmin() ? Branch::all() : [];
        return view('admin_panel.parties.edit', compact('party', 'type', 'branches'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'party_type' => 'required|in:Customer,Vendor,Vendor/Customer',
        ]);

        $type = $request->get('type', 'Customer');
        $partyType = $request->input('party_type');
        $data = $request->all();
        if (isset($data['credit_terms']) && $data['credit_terms'] === 'custom') {
            $data['credit_terms'] = $data['custom_credit_terms'] ?? null;
        }

        DB::beginTransaction();
        try {
            $mappedData = [
                'is_active' => $request->has('is_active') ? 1 : 0,
                'party_type' => $partyType,
                'title' => $data['title'] ?? null,
                'url' => $data['url'] ?? null,
                'ntn_no' => $data['ntn_no'] ?? null,
                'city' => $data['city'] ?? null,
                'country' => $data['country'] ?? null,
                'fax' => $data['fax'] ?? null,
                'credit_terms' => $data['credit_terms'] ?? null,
                'shipping_address' => $data['shipping_address'] ?? null,
                'shipping_city' => $data['shipping_city'] ?? null,
                'shipping_country' => $data['shipping_country'] ?? null,
                'shipping_phone' => $data['shipping_phone'] ?? null,
                'shipping_fax' => $data['shipping_fax'] ?? null,
                'shipping_email' => $data['shipping_email'] ?? null,
                'gst_no' => $data['gst_no'] ?? null,
                'dsl_no' => $data['dsl_no'] ?? null,
                'drap_no' => $data['drap_no'] ?? null,
                'ftn_no' => $data['ftn_no'] ?? null,
                'credit_terms' => $data['credit_terms'] ?? null,
                'payment_mode' => $data['payment_mode'] ?? null,
                'credit_limit' => $data['credit_limit'] ?? 0,
                'credit_status' => $data['credit_status'] ?? null,
                'opening_balance' => $data['opening_balance'] ?? 0,
                'branch_id' => $request->input('branch_id') ?? 1,
                'bank_name' => $data['bank_name'] ?? null,
                'cheque_no' => $data['cheque_no'] ?? null,
                'cheque_date' => $data['cheque_date'] ?? null,
            ];

            if ($type === 'Customer') {
                $customer = Customer::findOrFail($id);
                $updateData = array_merge($mappedData, [
                    'customer_name' => $data['title'] ?? null,
                    'business_name' => $data['business_name'] ?? null,
                    'abr' => $data['abr'] ?? null,
                    'cnic' => $data['cnic'] ?? null,
                    'address' => $data['address'] ?? null,
                    'mobile' => $data['phone'] ?? null,
                    'email_address' => $data['email'] ?? null,
                    'category' => $data['category'] ?? null,
                    'balance_range' => $data['credit_limit'] ?? 0,
                    'loyalty_group' => $data['loyalty_group'] ?? null,
                    'default_price' => $data['default_price'] ?? null,
                    'v1_mc' => $data['v1_mc'] ?? 0,
                    'v2_mc' => $data['v2_mc'] ?? 0,
                    'van_no' => $data['van_no'] ?? null,
                    'cng' => $data['cng'] ?? null,
                    'card_expiry' => $data['card_expiry'] ?? null,
                    'contact_person' => $data['contact_person'] ?? null,
                    'contact_person_designation' => $data['contact_person_designation'] ?? null,
                    'contact_person_whatsapp' => $data['contact_person_whatsapp'] ?? null,
                    'contact_person_2' => $data['contact_person_2'] ?? null,
                    'contact_person_2_designation' => $data['contact_person_2_designation'] ?? null,
                    'contact_person_2_whatsapp' => $data['contact_person_2_whatsapp'] ?? null,
                    'mobile_2' => $data['contact_person_2_mobile'] ?? null,
                ]);
                
                if ($request->hasFile('image')) {
                    $updateData['image'] = $request->file('image')->store('customers', 'public');
                }

                $customer->update($updateData);

                // Sync to Vendor if dual type
                if ($partyType === 'Vendor/Customer') {
                    $code = $customer->customer_id;
                    $vendor = Vendor::where('vendor_code', $code)->first();
                    $vendorUpdateData = [
                        'name' => $data['title'] ?? null,
                        'business_name' => $data['business_name'] ?? null,
                        'cnic' => $data['cnic'] ?? null,
                        'address' => $data['address'] ?? null,
                        'phone' => $data['phone'] ?? null,
                        'email' => $data['email'] ?? null,
                        'party_type' => 'Vendor/Customer',
                        'branch_id' => $mappedData['branch_id'],
                        'is_active' => $mappedData['is_active'],
                        'city' => $mappedData['city'] ?? null,
                        'country' => $mappedData['country'] ?? null,
                        'ntn_no' => $mappedData['ntn_no'] ?? null,
                        'gst_no' => $mappedData['gst_no'] ?? null,
                        'dsl_no' => $mappedData['dsl_no'] ?? null,
                        'drap_no' => $mappedData['drap_no'] ?? null,
                        'ftn_no' => $mappedData['ftn_no'] ?? null,
                        'bank_name' => $mappedData['bank_name'] ?? null,
                        'cheque_no' => $mappedData['cheque_no'] ?? null,
                        'cheque_date' => $mappedData['cheque_date'] ?? null,
                        'opening_balance' => $mappedData['opening_balance'] ?? 0,
                        'contact_person' => $data['contact_person'] ?? null,
                        'contact_person_designation' => $data['contact_person_designation'] ?? null,
                        'contact_person_mobile' => $data['phone'] ?? null,
                    ];
                    if ($vendor) {
                        $vendor->update($vendorUpdateData);
                    } else {
                        Vendor::create(array_merge($vendorUpdateData, ['vendor_code' => $code]));
                    }
                }
            } else {
                $vendor = Vendor::findOrFail($id);
                $updateData = array_merge($mappedData, [
                    'name' => $data['title'] ?? null,
                    'business_name' => $data['business_name'] ?? null,
                    'cnic' => $data['cnic'] ?? null,
                    'address' => $data['address'] ?? null,
                    'phone' => $data['phone'] ?? null,
                    'email' => $data['email'] ?? null,
                    'commission_percent' => $data['commission_percent'] ?? 0,
                    'wh_tax' => $data['wh_tax'] ?? 0,
                    'margin_percent' => $data['margin_percent'] ?? 0,
                    'contact_person' => $data['contact_person'] ?? null,
                    'contact_person_designation' => $data['contact_person_designation'] ?? null,
                    'contact_person_mobile' => $data['phone'] ?? null,
                    'contact_person_whatsapp' => $data['contact_person_whatsapp'] ?? null,
                    'contact_person_2' => $data['contact_person_2'] ?? null,
                    'contact_person_2_designation' => $data['contact_person_2_designation'] ?? null,
                    'contact_person_2_mobile' => $data['contact_person_2_mobile'] ?? null,
                    'contact_person_2_whatsapp' => $data['contact_person_2_whatsapp'] ?? null,
                ]);

                if ($request->hasFile('image')) {
                    $updateData['image'] = $request->file('image')->store('vendors', 'public');
                }

                $vendor->update($updateData);

                // Sync to Customer if dual type
                if ($partyType === 'Vendor/Customer') {
                    $code = $vendor->vendor_code;
                    $customer = Customer::where('customer_id', $code)->first();
                    $customerUpdateData = [
                        'customer_name' => $data['title'] ?? null,
                        'business_name' => $data['business_name'] ?? null,
                        'cnic' => $data['cnic'] ?? null,
                        'address' => $data['address'] ?? null,
                        'mobile' => $data['phone'] ?? null,
                        'email_address' => $data['email'] ?? null,
                        'party_type' => 'Vendor/Customer',
                        'branch_id' => $mappedData['branch_id'],
                        'is_active' => $mappedData['is_active'],
                        'city' => $mappedData['city'] ?? null,
                        'country' => $mappedData['country'] ?? null,
                        'ntn_no' => $mappedData['ntn_no'] ?? null,
                        'gst_no' => $mappedData['gst_no'] ?? null,
                        'dsl_no' => $mappedData['dsl_no'] ?? null,
                        'drap_no' => $mappedData['drap_no'] ?? null,
                        'ftn_no' => $mappedData['ftn_no'] ?? null,
                        'bank_name' => $mappedData['bank_name'] ?? null,
                        'cheque_no' => $mappedData['cheque_no'] ?? null,
                        'cheque_date' => $mappedData['cheque_date'] ?? null,
                        'opening_balance' => $mappedData['opening_balance'] ?? 0,
                        'contact_person' => $data['contact_person'] ?? null,
                        'contact_person_designation' => $data['contact_person_designation'] ?? null,
                        'mobile_2' => $data['contact_person_2_mobile'] ?? null,
                    ];
                    if ($customer) {
                        $customer->update($customerUpdateData);
                    } else {
                        Customer::create(array_merge($customerUpdateData, ['customer_id' => $code]));
                    }
                }
            }

            DB::commit();
            $redirectRoute = ($type === 'Vendor') ? 'vendors.index' : 'customers.index';
            return redirect()->route($redirectRoute)->with('success', "Profile for '{$data['title']}' has been updated successfully.");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Party Update Error: " . $e->getMessage());
            return back()->withInput()->with('error', "Failed to update party profile. System Error: " . $e->getMessage());
        }
    }

    public function create(Request $request)
    {
        $type = $request->query('type', 'Customer'); // Default to Customer
        $custDraft = 'CUST-'.str_pad(\App\Models\Customer::max('id') + 1, 4, '0', STR_PAD_LEFT);
        $vendDraft = 'VEND-'.str_pad(\App\Models\Vendor::max('id') + 1, 4, '0', STR_PAD_LEFT);
        $branches = $this->isSuperAdmin() ? Branch::all() : [];

        return view('admin_panel.parties.create', compact('custDraft', 'vendDraft', 'type', 'branches'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'party_type' => 'required|in:Customer,Vendor,Vendor/Customer',
            'code' => 'required|string|unique:customers,customer_id|unique:vendors,vendor_code',
        ]);

        $data = $request->all();
        if (isset($data['credit_terms']) && $data['credit_terms'] === 'custom') {
            $data['credit_terms'] = $data['custom_credit_terms'] ?? null;
        }
        $partyType = $request->input('party_type');
        $opening = $request->input('opening_balance', 0);
        $userId = Auth::id();
        $branchId = $request->input('branch_id') ?? $this->getBranchId() ?? 1;

        DB::beginTransaction();
        try {
            $mappedData = [
                'is_active' => $request->has('is_active') ? 1 : 0,
                'party_type' => $partyType,
                'title' => $data['title'] ?? null,
                'url' => $data['url'] ?? null,
                'ntn_no' => $data['ntn_no'] ?? null,
                'city' => $data['city'] ?? null,
                'country' => $data['country'] ?? null,
                'fax' => $data['fax'] ?? null,
                'opening_balance' => $opening,
                'shipping_address' => $data['shipping_address'] ?? null,
                'shipping_city' => $data['shipping_city'] ?? null,
                'shipping_country' => $data['shipping_country'] ?? null,
                'shipping_phone' => $data['shipping_phone'] ?? null,
                'shipping_fax' => $data['shipping_fax'] ?? null,
                'shipping_email' => $data['shipping_email'] ?? null,
                'gst_no' => $data['gst_no'] ?? null,
                'dsl_no' => $data['dsl_no'] ?? null,
                'drap_no' => $data['drap_no'] ?? null,
                'ftn_no' => $data['ftn_no'] ?? null,
                'credit_terms' => $data['credit_terms'] ?? null,
                'payment_mode' => $data['payment_mode'] ?? null,
                'credit_limit' => $data['credit_limit'] ?? 0,
                'credit_status' => $data['credit_status'] ?? null,
                'branch_id' => $branchId,
                'bank_name' => $data['bank_name'] ?? null,
                'cheque_no' => $data['cheque_no'] ?? null,
                'cheque_date' => $data['cheque_date'] ?? null,
            ];

            $customerData = array_merge($mappedData, [
                'customer_id' => $data['code'] ?? 'CUST-'.rand(100,9999),
                'customer_name' => $data['title'] ?? null,
                'business_name' => $data['business_name'] ?? null,
                'abr' => $data['abr'] ?? null,
                'cnic' => $data['cnic'] ?? null,
                'address' => $data['address'] ?? null,
                'mobile' => $data['phone'] ?? null,
                'email_address' => $data['email'] ?? null,
                'category' => $data['category'] ?? null,
                'credit_status' => $data['credit_status'] ?? null,
                'balance_range' => $data['credit_limit'] ?? 0,
                'loyalty_group' => $data['loyalty_group'] ?? null,
                'default_price' => $data['default_price'] ?? null,
                'v1_mc' => $data['v1_mc'] ?? 0,
                'v2_mc' => $data['v2_mc'] ?? 0,
                'van_no' => $data['van_no'] ?? null,
                'cng' => $data['cng'] ?? null,
                'card_expiry' => $data['card_expiry'] ?? null,
                'contact_person' => $data['contact_person'] ?? null,
                'contact_person_designation' => $data['contact_person_designation'] ?? null,
                'contact_person_whatsapp' => $data['contact_person_whatsapp'] ?? null,
                'contact_person_2' => $data['contact_person_2'] ?? null,
                'contact_person_2_designation' => $data['contact_person_2_designation'] ?? null,
                'contact_person_2_whatsapp' => $data['contact_person_2_whatsapp'] ?? null,
                'mobile_2' => $data['contact_person_2_mobile'] ?? null,
            ]);

            $vendorData = array_merge($mappedData, [
                'name' => $data['title'] ?? null,
                'vendor_code' => $data['code'] ?? 'VEND-'.rand(100,9999),
                'business_name' => $data['business_name'] ?? null,
                'cnic' => $data['cnic'] ?? null,
                'address' => $data['address'] ?? null,
                'phone' => $data['phone'] ?? null,
                'email' => $data['email'] ?? null,
                'credit_limit' => $data['credit_limit'] ?? 0,
                'commission_percent' => $data['commission_percent'] ?? 0,
                'wh_tax' => $data['wh_tax'] ?? 0,
                'margin_percent' => $data['margin_percent'] ?? 0,
                'contact_person' => $data['contact_person'] ?? null,
                'contact_person_designation' => $data['contact_person_designation'] ?? null,
                'contact_person_mobile' => $data['phone'] ?? null,
                'contact_person_whatsapp' => $data['contact_person_whatsapp'] ?? null,
                'contact_person_2' => $data['contact_person_2'] ?? null,
                'contact_person_2_designation' => $data['contact_person_2_designation'] ?? null,
                'contact_person_2_mobile' => $data['contact_person_2_mobile'] ?? null,
                'contact_person_2_whatsapp' => $data['contact_person_2_whatsapp'] ?? null,
            ]);

            $balanceService = app(\App\Services\BalanceService::class);
            $journalService = app(\App\Services\JournalEntryService::class);

            if ($partyType === 'Customer' || $partyType === 'Vendor/Customer') {
                $customer = Customer::create($customerData);
                if ($opening != 0) {
                    CustomerLedger::create([
                        'customer_id' => $customer->id,
                        'admin_or_user_id' => $userId,
                        'previous_balance' => $opening,
                        'opening_balance' => $opening,
                        'closing_balance' => $opening,
                    ]);
                    $balanceService->ensureDefaultCOA($branchId);
                    
                    // Create Receipt Voucher instead of direct journal entry if requested
                    if ($opening > 0) {
                        $this->createOpeningBalanceVoucher($customer, 'receipt', $opening, $data, $branchId);
                    } else {
                        // Fallback to manual entry for negative opening (advance)
                        $arAccountId = $balanceService->getAccountsReceivableId($branchId);
                        $journalService->recordEntry($customer, $arAccountId, 0, abs($opening), "Opening Balance for Customer: {$customer->customer_name}", now()->toDateString(), $customer);
                    }
                }
            }

            if ($partyType === 'Vendor' || $partyType === 'Vendor/Customer') {
                $vendor = Vendor::create($vendorData);
                if ($opening != 0) {
                    VendorLedger::create([
                        'vendor_id' => $vendor->id,
                        'admin_or_user_id' => $userId,
                        'opening_balance' => $opening,
                        'closing_balance' => $opening,
                        'previous_balance' => $opening,
                    ]);
                    $balanceService->ensureDefaultCOA($branchId);
                    
                    // Create Payment Voucher instead of direct journal entry
                    if ($opening > 0) {
                        $this->createOpeningBalanceVoucher($vendor, 'payment', $opening, $data, $branchId);
                    } else {
                        // Fallback to manual entry for negative opening
                        $apAccountId = $balanceService->getAccountsPayableId($branchId);
                        $journalService->recordEntry($vendor, $apAccountId, abs($opening), 0, "Opening Balance for Vendor: {$vendor->name}", now()->toDateString(), $vendor);
                    }
                }
            }

            DB::commit();
            $redirectRoute = ($partyType === 'Vendor') ? 'vendors.index' : 'customers.index';
            return redirect()->route($redirectRoute)->with('success', "New party '{$data['title']}' has been successfully registered in the system.");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Party Store Error: " . $e->getMessage());
            return back()->withInput()->with('error', "Could not create party record. Validation or System failure: " . $e->getMessage());
        }
    }

    private function createOpeningBalanceVoucher($party, $type, $amount, $data, $branchId)
    {
        $balanceService = app(\App\Services\BalanceService::class);
        $voucherService = app(\App\Services\VoucherService::class);
        
        $cashAccountId = $balanceService->getCashAccountId($branchId);
        $partyType = get_class($party);
        
        $lines = [];
        if ($type === 'receipt') {
            // Receipt: Dr Cash (from customer), Cr Receivable (the customer)
            $receivableAccountId = $balanceService->getAccountsReceivableId($branchId);
            
            // If payment_mode is cheque, we use "Cheque in Hand" if exists
            $targetAccountId = $cashAccountId;
            if (strtolower($data['payment_mode'] ?? '') === 'cheque') {
                $chequeInHand = \App\Models\Account::where('title', 'Cheque In Hand')
                    ->where('branch_id', $branchId)->first();
                if ($chequeInHand) $targetAccountId = $chequeInHand->id;
            }

            $lines[] = ['account_id' => $targetAccountId, 'debit' => $amount, 'credit' => 0, 'narration' => 'Opening Balance Payment (Initial)'];
            $lines[] = ['account_id' => $receivableAccountId, 'debit' => 0, 'credit' => $amount, 'narration' => 'Opening Balance Clearing'];
        } else {
            // Payment: Dr Payable (to vendor), Cr Cash (from us)
            $payableAccountId = $balanceService->getAccountsPayableId($branchId);
            
            $lines[] = ['account_id' => $payableAccountId, 'debit' => $amount, 'credit' => 0, 'narration' => 'Opening Balance Payment (Initial)'];
            $lines[] = ['account_id' => $cashAccountId, 'debit' => 0, 'credit' => $amount, 'narration' => 'Opening Balance Clearing'];
        }

        $voucher = $voucherService->createVoucher([
            'voucher_type' => $type,
            'date' => now()->toDateString(),
            'status' => \App\Models\VoucherMaster::STATUS_POSTED,
            'party_type' => $partyType,
            'party_id' => $party->id,
            'remarks' => "Opening Balance Voucher for " . ($data['title'] ?? 'Party'),
            'branch_id' => $branchId
        ], $lines, Auth::id());

        // Handle Cheque Management
        if (strtolower($data['payment_mode'] ?? '') === 'cheque' && !empty($data['cheque_no'])) {
            \App\Models\Cheque::create([
                'voucher_master_id' => $voucher->id,
                'cheque_no' => $data['cheque_no'],
                'cheque_date' => $data['cheque_date'] ?? now()->toDateString(),
                'bank_name' => $data['bank_name'] ?? null,
                'status' => 'pending',
                'amount' => $amount,
                'actual_account_id' => $cashAccountId // Default target bank/clearing account
            ]);
        }
    }

    public function exportDocx(Request $request)
    {
        try {
            $data = $request->all();
            $timestamp = date('Y-m-d H:i:s');
            $user = Auth::user()->name ?? 'System';
            
            // Create Word XML content
            $docContent = <<<'DOCX'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"
            xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"
            xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing">
  <w:body>
    <w:p>
      <w:pPr>
        <w:jc w:val="center"/>
        <w:spacing w:line="360"/>
      </w:pPr>
      <w:r>
        <w:rPr>
          <w:b/>
          <w:sz w:val="56"/>
        </w:rPr>
        <w:t>Party Registration Form</w:t>
      </w:r>
    </w:p>
    <w:p>
      <w:pPr>
        <w:jc w:val="center"/>
      </w:pPr>
      <w:r>
        <w:t>━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━</w:t>
      </w:r>
    </w:p>
    <w:p>
      <w:r>
        <w:t>Generated Date: GENERATED_DATE | Generated By: GENERATED_USER</w:t>
      </w:r>
    </w:p>
    <w:p>
      <w:r>
        <w:t/>
      </w:r>
    </w:p>
    <w:p>
      <w:pPr>
        <w:spacing w:line="360"/>
      </w:pPr>
      <w:r>
        <w:rPr>
          <w:b/>
          <w:sz w:val="28"/>
        </w:rPr>
        <w:t>Party Information</w:t>
      </w:r>
    </w:p>
    <w:tbl>
      <w:tblPr>
        <w:tblW w:w="5000" w:type="dxa"/>
        <w:tblBorders>
          <w:top w:val="single" w:sz="12" w:space="0" w:color="000000"/>
          <w:left w:val="single" w:sz="12" w:space="0" w:color="000000"/>
          <w:bottom w:val="single" w:sz="12" w:space="0" w:color="000000"/>
          <w:right w:val="single" w:sz="12" w:space="0" w:color="000000"/>
          <w:insideH w:val="single" w:sz="12" w:space="0" w:color="000000"/>
          <w:insideV w:val="single" w:sz="12" w:space="0" w:color="000000"/>
        </w:tblBorders>
      </w:tblPr>
      <w:tr>
        <w:tc>
          <w:tcPr><w:tcW w:w="2500" w:type="dxa"/></w:tcPr>
          <w:p><w:r><w:rPr><w:b/></w:rPr><w:t>Party Type</w:t></w:r></w:p>
        </w:tc>
        <w:tc>
          <w:tcPr><w:tcW w:w="2500" w:type="dxa"/></w:tcPr>
          <w:p><w:r><w:t>PARTY_TYPE</w:t></w:r></w:p>
        </w:tc>
      </w:tr>
      <w:tr>
        <w:tc>
          <w:tcPr><w:tcW w:w="2500" w:type="dxa"/></w:tcPr>
          <w:p><w:r><w:rPr><w:b/></w:rPr><w:t>System Code</w:t></w:r></w:p>
        </w:tc>
        <w:tc>
          <w:tcPr><w:tcW w:w="2500" w:type="dxa"/></w:tcPr>
          <w:p><w:r><w:t>SYSTEM_CODE</w:t></w:r></w:p>
        </w:tc>
      </w:tr>
      <w:tr>
        <w:tc>
          <w:tcPr><w:tcW w:w="2500" w:type="dxa"/></w:tcPr>
          <w:p><w:r><w:rPr><w:b/></w:rPr><w:t>Business Name</w:t></w:r></w:p>
        </w:tc>
        <w:tc>
          <w:tcPr><w:tcW w:w="2500" w:type="dxa"/></w:tcPr>
          <w:p><w:r><w:t>BUSINESS_NAME</w:t></w:r></w:p>
        </w:tc>
      </w:tr>
      <w:tr>
        <w:tc>
          <w:tcPr><w:tcW w:w="2500" w:type="dxa"/></w:tcPr>
          <w:p><w:r><w:rPr><w:b/></w:rPr><w:t>Account Holder</w:t></w:r></w:p>
        </w:tc>
        <w:tc>
          <w:tcPr><w:tcW w:w="2500" w:type="dxa"/></w:tcPr>
          <w:p><w:r><w:t>ACCOUNT_HOLDER</w:t></w:r></w:p>
        </w:tc>
      </w:tr>
    </w:tbl>
    <w:p><w:r><w:t/></w:r></w:p>
    <w:p>
      <w:pPr>
        <w:spacing w:line="360"/>
      </w:pPr>
      <w:r>
        <w:rPr>
          <w:b/>
          <w:sz w:val="28"/>
        </w:rPr>
        <w:t>Contact Information</w:t>
      </w:r>
    </w:p>
    <w:tbl>
      <w:tblPr>
        <w:tblW w:w="5000" w:type="dxa"/>
        <w:tblBorders>
          <w:top w:val="single" w:sz="12" w:space="0" w:color="000000"/>
          <w:left w:val="single" w:sz="12" w:space="0" w:color="000000"/>
          <w:bottom w:val="single" w:sz="12" w:space="0" w:color="000000"/>
          <w:right w:val="single" w:sz="12" w:space="0" w:color="000000"/>
          <w:insideH w:val="single" w:sz="12" w:space="0" w:color="000000"/>
          <w:insideV w:val="single" w:sz="12" w:space="0" w:color="000000"/>
        </w:tblBorders>
      </w:tblPr>
      <w:tr>
        <w:tc>
          <w:tcPr><w:tcW w:w="2500" w:type="dxa"/></w:tcPr>
          <w:p><w:r><w:rPr><w:b/></w:rPr><w:t>Contact Person</w:t></w:r></w:p>
        </w:tc>
        <w:tc>
          <w:tcPr><w:tcW w:w="2500" w:type="dxa"/></w:tcPr>
          <w:p><w:r><w:t>CONTACT_PERSON</w:t></w:r></w:p>
        </w:tc>
      </w:tr>
      <w:tr>
        <w:tc>
          <w:tcPr><w:tcW w:w="2500" w:type="dxa"/></w:tcPr>
          <w:p><w:r><w:rPr><w:b/></w:rPr><w:t>Phone</w:t></w:r></w:p>
        </w:tc>
        <w:tc>
          <w:tcPr><w:tcW w:w="2500" w:type="dxa"/></w:tcPr>
          <w:p><w:r><w:t>PHONE</w:t></w:r></w:p>
        </w:tc>
      </w:tr>
      <w:tr>
        <w:tc>
          <w:tcPr><w:tcW w:w="2500" w:type="dxa"/></w:tcPr>
          <w:p><w:r><w:rPr><w:b/></w:rPr><w:t>Email</w:t></w:r></w:p>
        </w:tc>
        <w:tc>
          <w:tcPr><w:tcW w:w="2500" w:type="dxa"/></w:tcPr>
          <w:p><w:r><w:t>EMAIL</w:t></w:r></w:p>
        </w:tc>
      </w:tr>
    </w:tbl>
    <w:p><w:r><w:t/></w:r></w:p>
    <w:p>
      <w:pPr>
        <w:spacing w:line="360"/>
      </w:pPr>
      <w:r>
        <w:rPr>
          <w:b/>
          <w:sz w:val="28"/>
        </w:rPr>
        <w:t>Address Information</w:t>
      </w:r>
    </w:p>
    <w:tbl>
      <w:tblPr>
        <w:tblW w:w="5000" w:type="dxa"/>
        <w:tblBorders>
          <w:top w:val="single" w:sz="12" w:space="0" w:color="000000"/>
          <w:left w:val="single" w:sz="12" w:space="0" w:color="000000"/>
          <w:bottom w:val="single" w:sz="12" w:space="0" w:color="000000"/>
          <w:right w:val="single" w:sz="12" w:space="0" w:color="000000"/>
          <w:insideH w:val="single" w:sz="12" w:space="0" w:color="000000"/>
          <w:insideV w:val="single" w:sz="12" w:space="0" w:color="000000"/>
        </w:tblBorders>
      </w:tblPr>
      <w:tr>
        <w:tc>
          <w:tcPr><w:tcW w:w="2500" w:type="dxa"/></w:tcPr>
          <w:p><w:r><w:rPr><w:b/></w:rPr><w:t>Mailing City</w:t></w:r></w:p>
        </w:tc>
        <w:tc>
          <w:tcPr><w:tcW w:w="2500" w:type="dxa"/></w:tcPr>
          <w:p><w:r><w:t>MAILING_CITY</w:t></w:r></w:p>
        </w:tc>
      </w:tr>
      <w:tr>
        <w:tc>
          <w:tcPr><w:tcW w:w="2500" w:type="dxa"/></w:tcPr>
          <w:p><w:r><w:rPr><w:b/></w:rPr><w:t>Shipping City</w:t></w:r></w:p>
        </w:tc>
        <w:tc>
          <w:tcPr><w:tcW w:w="2500" w:type="dxa"/></w:tcPr>
          <w:p><w:r><w:t>SHIPPING_CITY</w:t></w:r></w:p>
        </w:tc>
      </w:tr>
    </w:tbl>
    <w:p><w:r><w:t/></w:r></w:p>
    <w:p>
      <w:r>
        <w:t>Generated: GENERATED_DATE by GENERATED_USER</w:t>
      </w:r>
    </w:p>
  </w:body>
</w:document>
DOCX;

            // Replace placeholders
            $docContent = str_replace('GENERATED_DATE', $timestamp, $docContent);
            $docContent = str_replace('GENERATED_USER', $user, $docContent);
            $docContent = str_replace('PARTY_TYPE', $data['partyType'] ?? 'N/A', $docContent);
            $docContent = str_replace('SYSTEM_CODE', $data['systemCode'] ?? 'N/A', $docContent);
            $docContent = str_replace('BUSINESS_NAME', $data['businessName'] ?? 'N/A', $docContent);
            $docContent = str_replace('ACCOUNT_HOLDER', $data['accountHolder'] ?? 'N/A', $docContent);
            $docContent = str_replace('CONTACT_PERSON', $data['contactPerson'] ?? 'N/A', $docContent);
            $docContent = str_replace('PHONE', $data['phone'] ?? 'N/A', $docContent);
            $docContent = str_replace('EMAIL', $data['email'] ?? 'N/A', $docContent);
            $docContent = str_replace('MAILING_CITY', $data['mailingCity'] ?? 'N/A', $docContent);
            $docContent = str_replace('SHIPPING_CITY', $data['shippingCity'] ?? 'N/A', $docContent);

            // Create temporary files
            $tempDir = storage_path('temp/docx');
            if (!is_dir($tempDir)) mkdir($tempDir, 0755, true);
            
            $tempFile = $tempDir . '/' . uniqid() . '.zip';
            $docFile = $tempDir . '/document.xml';
            
            // Write document.xml
            file_put_contents($docFile, $docContent);
            
            // Create _rels directory and .rels file
            $relsDir = $tempDir . '/_rels';
            if (!is_dir($relsDir)) mkdir($relsDir, 0755, true);
            file_put_contents($relsDir . '/.rels', '<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"></Relationships>');
            
            // Create word directory
            $wordDir = $tempDir . '/word';
            if (!is_dir($wordDir)) mkdir($wordDir, 0755, true);
            copy($docFile, $wordDir . '/document.xml');
            
            // Create [Content_Types].xml
            $contentTypes = '<?xml version="1.0" encoding="UTF-8"?>'
              . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
              . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
              . '<Default Extension="xml" ContentType="application/xml"/>'
              . '<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>'
              . '<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>'
              . '<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>'
              . '</Types>';
            file_put_contents($tempDir . '/[Content_Types].xml', $contentTypes);

            // Create docProps
            $coreXml = '<?xml version="1.0" encoding="UTF-8"?>'
              . '<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
              . '<dc:title>Party Registration Form</dc:title>'
              . '<dc:creator>' . htmlspecialchars($user) . '</dc:creator>'
              . '<dcterms:created xsi:type="dcterms:W3CDTF">' . date('c') . '</dcterms:created>'
              . '<cp:revision>1</cp:revision>'
              . '</cp:coreProperties>';
            $docPropsDir = $tempDir . '/docProps';
            if (!is_dir($docPropsDir)) mkdir($docPropsDir, 0755, true);
            file_put_contents($docPropsDir . '/core.xml', $coreXml);

            $appXml = '<?xml version="1.0" encoding="UTF-8"?>'
              . '<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties">'
              . '<Application>Prowaves ERP</Application>'
              . '<DocSecurity>0</DocSecurity>'
              . '<ScaleCrop>false</ScaleCrop>'
              . '</Properties>';
            file_put_contents($docPropsDir . '/app.xml', $appXml);

            // Create word/_rels/document.xml.rels (empty relationships)
            $wordRelsDir = $wordDir . '/_rels';
            if (!is_dir($wordRelsDir)) mkdir($wordRelsDir, 0755, true);
            file_put_contents($wordRelsDir . '/document.xml.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"></Relationships>');

            // Create ZIP archive with minimal required parts for Word
            $zip = new \ZipArchive();
            $zip->open($tempFile, \ZipArchive::CREATE);
            // Required parts
            $zip->addFile($tempDir . '/[Content_Types].xml', '[Content_Types].xml');
            $zip->addFile($relsDir . '/.rels', '_rels/.rels');
            $zip->addFile($docPropsDir . '/core.xml', 'docProps/core.xml');
            $zip->addFile($docPropsDir . '/app.xml', 'docProps/app.xml');
            $zip->addFile($wordDir . '/document.xml', 'word/document.xml');
            $zip->addFile($wordRelsDir . '/document.xml.rels', 'word/_rels/document.xml.rels');
            $zip->close();
            
            // Return as download
            $filename = 'Party_' . ($data['systemCode'] ?? 'Export') . '_' . date('Y-m-d-His') . '.docx';
            return response()->download($tempFile, $filename)->deleteFileAfterSend(true);
            
        } catch (\Exception $e) {
            Log::error("Docx Export Error: " . $e->getMessage());
            return response()->json(['error' => 'Failed to export document'], 500);
        }
    }

}

