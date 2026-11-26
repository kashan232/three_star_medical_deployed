<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Cheque;
use App\Models\Customer;
use App\Models\CustomerLedger;
use App\Models\JournalEntry;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorLedger;
use App\Models\VoucherMaster;
use App\Services\BalanceService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ChequeManagementTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected Account $zeroBank;
    protected Account $negativeBank;
    protected Account $cashAccount;
    protected Account $expenseAccount;
    protected Customer $customer;
    protected Vendor $vendor;
    protected BalanceService $balanceService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->balanceService = app(BalanceService::class);

        $this->user = User::first() ?? User::factory()->create();
        $perms = [
            'receipts.voucher.view',
            'receipts.voucher.create',
            'receipts.voucher.edit',
            'receipts.voucher.delete',
            'payment.voucher.view',
            'payment.voucher.create',
            'payment.voucher.edit',
            'payment.voucher.delete',
            'journal.voucher.view',
            'journal.voucher.create',
            'sales.view',
            'sales.create',
        ];
        foreach ($perms as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }
        $this->user->givePermissionTo($perms);
        $this->actingAs($this->user);

        // Fresh deterministic test accounts in Branch 1 so existing data never interferes
        $bankHeadId = DB::table('account_heads')->where('branch_id', 1)->where('name', 'like', '%Bank%')->value('id') ?? 5;
        $cashHeadId = DB::table('account_heads')->where('branch_id', 1)->where('name', 'like', '%Cash%')->value('id') ?? 4;
        $expHeadId  = DB::table('account_heads')->where('branch_id', 1)->where('name', 'like', '%Expense%')->value('id') ?? 7;

        $this->zeroBank = Account::create([
            'branch_id'       => 1,
            'head_id'         => $bankHeadId,
            'title'           => 'TEST ZERO BALANCE BANK',
            'account_code'    => '1-02-052-99001',
            'opening_balance' => 0,
            'current_balance' => 0,
            'type'            => 'Debit',
            'status'          => 1,
            'is_active'       => 1,
        ]);

        $this->negativeBank = Account::create([
            'branch_id'       => 1,
            'head_id'         => $bankHeadId,
            'title'           => 'TEST NEGATIVE BALANCE BANK',
            'account_code'    => '1-02-052-99002',
            'opening_balance' => -185000,
            'current_balance' => -185000,
            'type'            => 'Debit',
            'status'          => 1,
            'is_active'       => 1,
        ]);

        $this->cashAccount = Account::create([
            'branch_id'       => 1,
            'head_id'         => $cashHeadId,
            'title'           => 'TEST CASH IN HAND',
            'account_code'    => '1-02-040-99001',
            'opening_balance' => 5000,
            'current_balance' => 5000,
            'type'            => 'Debit',
            'status'          => 1,
            'is_active'       => 1,
        ]);

        $this->expenseAccount = Account::create([
            'branch_id'       => 1,
            'head_id'         => $expHeadId,
            'title'           => 'TEST OFFICE EXPENSE',
            'account_code'    => '5-02-99001',
            'opening_balance' => 0,
            'current_balance' => 0,
            'type'            => 'Debit',
            'status'          => 1,
            'is_active'       => 1,
        ]);

        $this->customer = Customer::create([
            'customer_id'     => 'CUST-TEST-' . rand(10000, 99999),
            'customer_name'   => 'Test Cheque Customer',
            'opening_balance' => 100000,
            'previous_balance'=> 100000,
            'status'          => 'active',
            'is_active'       => 1,
            'branch_id'       => 1,
        ]);

        $this->vendor = Vendor::create([
            'vendor_code'      => 'VEND-TEST-' . rand(10000, 99999),
            'name'             => 'Test Cheque Vendor',
            'opening_balance'  => 120000,
            'previous_balance' => 120000,
            'is_active'        => 1,
            'branch_id'        => 1,
        ]);
    }

    public function test_issue_post_dated_cheque_via_bpv_with_zero_and_negative_bank_balance_and_clear_later(): void
    {
        $pdcDate = now()->addDays(10)->toDateString();

        // 1. Issue 10-day PDC of Rs. 50,000 on ZERO balance bank (must NOT block!)
        $resp1 = $this->post(route('vouchers.store_action', ['type' => 'bpv']), [
            'date'            => now()->toDateString(),
            'branch_id'       => 1,
            'payment_mode'    => 'cheque',
            'cheque_no'       => 'PDC-000101',
            'cheque_date'     => $pdcDate,
            'remarks'         => '10 day PDC payment to vendor',
            'pay_target_type' => 'vendor',
            'target_id'       => 'vendor_' . $this->vendor->id,
            'bank_account_id' => $this->zeroBank->id,
            'amount'          => 50000,
        ]);

        $resp1->assertRedirect(route('vouchers.list', ['type' => 'bpv']));
        $resp1->assertSessionHas('success');
        $resp1->assertSessionMissing('error');

        $cheque = Cheque::where('cheque_no', 'PDC-000101')->first();
        $this->assertNotNull($cheque);
        $this->assertSame(Cheque::TYPE_ISSUED, $cheque->type);
        $this->assertSame(Cheque::STATUS_PENDING, $cheque->status);
        $this->assertEquals(50000.00, (float) $cheque->amount);
        $this->assertEquals($this->zeroBank->id, $cheque->actual_account_id);

        $chequeIssuedAcc = Account::find($this->balanceService->getChequeIssuedId(1));
        $this->assertEquals(50000.00, (float) $chequeIssuedAcc->calculated_balance);
        // Bank balance must remain untouched (0) until cheque is actually cleared
        $this->assertEquals(0.00, (float) $this->zeroBank->fresh()->calculated_balance);
        // Vendor balance reduced from 120,000 -> 70,000
        $this->assertEquals(70000.00, $this->balanceService->getVendorBalance($this->vendor->id));

        // Double-entry balance verification on BPV voucher
        $bpvEntries = JournalEntry::where('source_type', VoucherMaster::class)
            ->where('source_id', $cheque->voucher_master_id)
            ->get();
        $this->assertEquals(
            round((float) $bpvEntries->sum('debit'), 2),
            round((float) $bpvEntries->sum('credit'), 2)
        );

        // 2. Also issue PDC on NEGATIVE balance bank (-185,000) -> must succeed!
        $resp2 = $this->post(route('vouchers.store_action', ['type' => 'bpv']), [
            'date'            => now()->toDateString(),
            'branch_id'       => 1,
            'payment_mode'    => 'cheque',
            'cheque_no'       => 'PDC-000102',
            'cheque_date'     => $pdcDate,
            'remarks'         => 'PDC on negative bank',
            'pay_target_type' => 'vendor',
            'target_id'       => 'vendor_' . $this->vendor->id,
            'bank_account_id' => $this->negativeBank->id,
            'amount'          => 20000,
        ]);
        $resp2->assertSessionHas('success');
        $this->assertSame(Cheque::STATUS_PENDING, Cheque::where('cheque_no', 'PDC-000102')->value('status'));
        $this->assertEquals(-185000.00, (float) $this->negativeBank->fresh()->calculated_balance);

        // 3. Simulate funds arriving in zeroBank (+80,000 via online BRV)
        $this->post(route('vouchers.store_action', ['type' => 'brv']), [
            'date'            => now()->toDateString(),
            'branch_id'       => 1,
            'payment_mode'    => 'online',
            'remarks'         => 'Customer wire transfer',
            'party_type'      => 'customer',
            'party_id'        => 'customer_' . $this->customer->id,
            'bank_account_id' => $this->zeroBank->id,
            'amount'          => 80000,
        ])->assertSessionHas('success');

        $this->assertEquals(80000.00, (float) $this->zeroBank->fresh()->calculated_balance);

        // 4. Clear the 10-day PDC in Cheque Management
        $clearResp = $this->post(route('cheques.clear', $cheque->id), [
            'actual_account_id' => $this->zeroBank->id,
            'clearance_date'    => $pdcDate,
            'notes'             => 'Cleared on maturity',
        ]);
        $clearResp->assertSessionHas('success');

        $cheque->refresh();
        $this->assertSame(Cheque::STATUS_CLEARED, $cheque->status);
        $this->assertNotNull($cheque->clearance_voucher_id);
        $this->assertEquals(30000.00, (float) $this->zeroBank->fresh()->calculated_balance);
        // PDC-000102 (20,000) is still pending, so Cheques Issued (PDC) balance is 20,000
        $this->assertEquals(20000.00, (float) $chequeIssuedAcc->fresh()->calculated_balance);

        // 5. Undo clearance -> reverts cleanly back to pending and restores bank to 80,000
        $undoResp = $this->post(route('cheques.undo_clear', $cheque->id));
        $undoResp->assertSessionHas('success');
        $cheque->refresh();
        $this->assertSame(Cheque::STATUS_PENDING, $cheque->status);
        $this->assertNull($cheque->clearance_voucher_id);
        $this->assertEquals(80000.00, (float) $this->zeroBank->fresh()->calculated_balance);
        $this->assertEquals(70000.00, (float) $chequeIssuedAcc->fresh()->calculated_balance);
    }

    public function test_issued_cheque_bounce_and_cancel_reinstate_vendor_and_expense_balances(): void
    {
        // Issue PDC to Vendor for 30,000
        $this->post(route('vouchers.store_action', ['type' => 'bpv']), [
            'date'            => now()->toDateString(),
            'branch_id'       => 1,
            'payment_mode'    => 'cheque',
            'cheque_no'       => 'PDC-BOUNCE-1',
            'cheque_date'     => now()->addDays(5)->toDateString(),
            'remarks'         => 'Vendor PDC',
            'pay_target_type' => 'vendor',
            'target_id'       => 'vendor_' . $this->vendor->id,
            'bank_account_id' => $this->zeroBank->id,
            'amount'          => 30000,
        ])->assertSessionHas('success');

        $cheque = Cheque::where('cheque_no', 'PDC-BOUNCE-1')->firstOrFail();
        $this->assertEquals(90000.00, $this->balanceService->getVendorBalance($this->vendor->id));

        // Bounce it
        $this->post(route('cheques.bounce', $cheque->id), [
            'bounce_reason' => 'Insufficient Funds',
            'bounce_date'   => now()->toDateString(),
        ])->assertSessionHas('success');

        $cheque->refresh();
        $this->assertSame(Cheque::STATUS_BOUNCED, $cheque->status);
        $this->assertSame('Insufficient Funds', $cheque->bounce_reason);
        $this->assertNotNull($cheque->bounce_voucher_id);
        // Vendor balance and VendorLedger fully reinstated to 120,000!
        $this->assertEquals(120000.00, $this->balanceService->getVendorBalance($this->vendor->id));
        $this->assertEquals(120000.00, (float) $this->vendor->fresh()->previous_balance);
        $this->assertEquals(0.00, (float) Account::find($this->balanceService->getChequeIssuedId(1))->calculated_balance);

        // Issue PDC to Expense Account for 4,500 and cancel it
        $this->post(route('vouchers.store_action', ['type' => 'bpv']), [
            'date'            => now()->toDateString(),
            'branch_id'       => 1,
            'payment_mode'    => 'cheque',
            'cheque_no'       => 'PDC-EXP-1',
            'cheque_date'     => now()->addDays(7)->toDateString(),
            'remarks'         => 'Expense PDC',
            'pay_target_type' => 'expense',
            'target_id'       => 'account_' . $this->expenseAccount->id,
            'bank_account_id' => $this->zeroBank->id,
            'amount'          => 4500,
        ])->assertSessionHas('success');

        $this->assertEquals(4500.00, (float) $this->expenseAccount->fresh()->calculated_balance);

        $expCheque = Cheque::where('cheque_no', 'PDC-EXP-1')->firstOrFail();
        $this->post(route('cheques.cancel', $expCheque->id), [
            'cancel_reason' => 'Stop payment requested',
            'cancel_date'   => now()->toDateString(),
        ])->assertSessionHas('success');

        $expCheque->refresh();
        $this->assertSame(Cheque::STATUS_CANCELLED, $expCheque->status);
        $this->assertEquals(0.00, (float) $this->expenseAccount->fresh()->calculated_balance);
        $this->assertEquals(0.00, (float) Account::find($this->balanceService->getChequeIssuedId(1))->calculated_balance);
    }

    public function test_received_cheque_lifecycle_deposit_clear_and_bounce(): void
    {
        // 1. Receive cheque via BRV (40,000) -> goes to Cheque In Hand, NOT Bank
        $this->post(route('vouchers.store_action', ['type' => 'brv']), [
            'date'            => now()->toDateString(),
            'branch_id'       => 1,
            'payment_mode'    => 'cheque',
            'cheque_no'       => 'RCV-5001',
            'cheque_date'     => now()->addDays(3)->toDateString(),
            'bank_name'       => 'Meezan Bank',
            'remarks'         => 'Customer cheque payment',
            'party_type'      => 'customer',
            'party_id'        => 'customer_' . $this->customer->id,
            'bank_account_id' => $this->zeroBank->id,
            'amount'          => 40000,
        ])->assertSessionHas('success');

        $chequeInHandAcc = Account::find($this->balanceService->getChequeInHandId(1));
        $this->assertEquals(40000.00, (float) $chequeInHandAcc->calculated_balance);
        $this->assertEquals(0.00, (float) $this->zeroBank->fresh()->calculated_balance);
        $this->assertEquals(60000.00, $this->balanceService->getCustomerBalance($this->customer->id));

        $cheque = Cheque::where('cheque_no', 'RCV-5001')->firstOrFail();
        $this->assertSame(Cheque::TYPE_RECEIVED, $cheque->type);
        $this->assertSame(Cheque::STATUS_PENDING, $cheque->status);

        // 2. Mark Deposited in zeroBank
        $this->post(route('cheques.deposit', $cheque->id), [
            'actual_account_id' => $this->zeroBank->id,
        ])->assertSessionHas('success');
        $this->assertSame(Cheque::STATUS_DEPOSITED, $cheque->fresh()->status);

        // 3. Clear Deposited Cheque -> Bank increases +40,000, Cheque In Hand becomes 0
        $this->post(route('cheques.clear', $cheque->id), [
            'actual_account_id' => $this->zeroBank->id,
            'clearance_date'    => now()->toDateString(),
        ])->assertSessionHas('success');

        $this->assertSame(Cheque::STATUS_CLEARED, $cheque->fresh()->status);
        $this->assertEquals(40000.00, (float) $this->zeroBank->fresh()->calculated_balance);
        $this->assertEquals(0.00, (float) $chequeInHandAcc->fresh()->calculated_balance);

        // 4. Receive second cheque (15,000) and Bounce -> Customer balance reinstated from 60,000 back to 60,000
        $this->post(route('vouchers.store_action', ['type' => 'brv']), [
            'date'            => now()->toDateString(),
            'branch_id'       => 1,
            'payment_mode'    => 'cheque',
            'cheque_no'       => 'RCV-5002',
            'cheque_date'     => now()->toDateString(),
            'bank_name'       => 'HBL',
            'remarks'         => 'Second cheque',
            'party_type'      => 'customer',
            'party_id'        => 'customer_' . $this->customer->id,
            'bank_account_id' => $this->zeroBank->id,
            'amount'          => 15000,
        ])->assertSessionHas('success');

        $this->assertEquals(45000.00, $this->balanceService->getCustomerBalance($this->customer->id));

        $cheque2 = Cheque::where('cheque_no', 'RCV-5002')->firstOrFail();
        $this->post(route('cheques.bounce', $cheque2->id), [
            'bounce_reason' => 'Refer to Drawer',
            'bounce_date'   => now()->toDateString(),
        ])->assertSessionHas('success');

        $this->assertSame(Cheque::STATUS_BOUNCED, $cheque2->fresh()->status);
        $this->assertEquals(60000.00, $this->balanceService->getCustomerBalance($this->customer->id));
        $this->assertEquals(60000.00, (float) $this->customer->fresh()->previous_balance);
        $this->assertEquals(0.00, (float) $chequeInHandAcc->fresh()->calculated_balance);
    }

    public function test_direct_cheque_creation_from_cheque_management_and_duplicate_guard(): void
    {
        // Issue directly via POST /cheques on zeroBank
        $this->post(route('cheques.store'), [
            'type'            => 'issued',
            'target_id'       => 'vendor_' . $this->vendor->id,
            'bank_account_id' => $this->zeroBank->id,
            'cheque_no'       => 'DIR-7771',
            'issue_date'      => now()->toDateString(),
            'cheque_date'     => now()->addDays(10)->toDateString(),
            'amount'          => 12000,
            'remarks'         => 'Direct cheque issue',
            'branch_id'       => 1,
        ])->assertSessionHas('success');

        $cheque = Cheque::where('cheque_no', 'DIR-7771')->firstOrFail();
        $this->assertSame(Cheque::TYPE_ISSUED, $cheque->type);
        $this->assertEquals(108000.00, $this->balanceService->getVendorBalance($this->vendor->id));
        $this->assertEquals(0.00, (float) $this->zeroBank->fresh()->calculated_balance);

        // Duplicate cheque number on the same bank account must be rejected
        $dupResp = $this->post(route('cheques.store'), [
            'type'            => 'issued',
            'target_id'       => 'vendor_' . $this->vendor->id,
            'bank_account_id' => $this->zeroBank->id,
            'cheque_no'       => 'DIR-7771',
            'issue_date'      => now()->toDateString(),
            'cheque_date'     => now()->addDays(12)->toDateString(),
            'amount'          => 5000,
            'branch_id'       => 1,
        ]);
        $dupResp->assertSessionHas('error');
        $this->assertSame(1, Cheque::where('cheque_no', 'DIR-7771')->count());

        // Receive directly via POST /cheques
        $this->post(route('cheques.store'), [
            'type'            => 'received',
            'target_id'       => 'customer_' . $this->customer->id,
            'bank_account_id' => $this->zeroBank->id,
            'cheque_no'       => 'DIR-8881',
            'issue_date'      => now()->toDateString(),
            'cheque_date'     => now()->addDays(4)->toDateString(),
            'bank_name'       => 'UBL',
            'amount'          => 9000,
            'remarks'         => 'Direct cheque receipt',
            'branch_id'       => 1,
        ])->assertSessionHas('success');

        $this->assertEquals(91000.00, $this->balanceService->getCustomerBalance($this->customer->id));
    }

    public function test_jv_allows_bank_credit_with_zero_balance_while_cash_stays_restricted(): void
    {
        // 1. JV crediting zeroBank (0 balance) by 10,000 must succeed!
        $jvResp = $this->post(route('vouchers.store_action', ['type' => 'jv']), [
            'date'      => now()->toDateString(),
            'branch_id' => 1,
            'remarks'   => 'Bank adjustment JV',
            'rows'      => [
                [
                    'item_type'  => 'vendor',
                    'account_id' => 'vendor_' . $this->vendor->id,
                    'narration'  => 'Vendor adjustment',
                    'debit'      => 10000,
                    'credit'     => 0,
                ],
                [
                    'item_type'  => 'bank',
                    'account_id' => 'account_' . $this->zeroBank->id,
                    'narration'  => 'Bank credit',
                    'debit'      => 0,
                    'credit'     => 10000,
                ],
            ],
        ]);
        $jvResp->assertSessionHas('success');
        $this->assertEquals(-10000.00, (float) $this->zeroBank->fresh()->calculated_balance);

        // 2. CPV exceeding Cash balance (5,000) must still be blocked!
        $cpvResp = $this->post(route('vouchers.store_action', ['type' => 'cpv']), [
            'date'            => now()->toDateString(),
            'branch_id'       => 1,
            'remarks'         => 'Cash overdraft attempt',
            'pay_target_type' => 'vendor',
            'target_id'       => 'vendor_' . $this->vendor->id,
            'cash_account_id' => $this->cashAccount->id,
            'amount'          => 9000,
        ]);
        $cpvResp->assertSessionHas('error');
        $this->assertEquals(5000.00, (float) $this->cashAccount->fresh()->calculated_balance);
    }

    public function test_edit_and_delete_voucher_sync_pending_cheques_and_lock_cleared_cheques(): void
    {
        // Create pending BPV cheque for 15,000
        $this->post(route('vouchers.store_action', ['type' => 'bpv']), [
            'date'            => now()->toDateString(),
            'branch_id'       => 1,
            'payment_mode'    => 'cheque',
            'cheque_no'       => 'EDT-1001',
            'cheque_date'     => now()->addDays(10)->toDateString(),
            'remarks'         => 'Editable cheque',
            'pay_target_type' => 'vendor',
            'target_id'       => 'vendor_' . $this->vendor->id,
            'bank_account_id' => $this->zeroBank->id,
            'amount'          => 15000,
        ])->assertSessionHas('success');

        $cheque = Cheque::where('cheque_no', 'EDT-1001')->firstOrFail();
        $voucher = VoucherMaster::findOrFail($cheque->voucher_master_id);
        $chequeIssuedId = $this->balanceService->getChequeIssuedId(1);

        // Edit voucher to amount 18,000 and new cheque_no EDT-1002
        $newDueDate = now()->addDays(14)->toDateString();
        $editResp = $this->put(route('vouchers.update_action', $voucher->id), [
            'voucher_type' => 'bpv',
            'date'         => now()->toDateString(),
            'branch_id'    => 1,
            'cheque_no'    => 'EDT-1002',
            'cheque_date'  => $newDueDate,
            'remarks'      => 'Updated cheque amount',
            'rows'         => [
                [
                    'item_type'  => 'vendor',
                    'account_id' => 'vendor_' . $this->vendor->id,
                    'narration'  => 'Vendor updated',
                    'debit'      => 18000,
                    'credit'     => 0,
                ],
                [
                    'item_type'  => 'account',
                    'account_id' => 'account_' . $chequeIssuedId,
                    'narration'  => 'PDC updated',
                    'debit'      => 0,
                    'credit'     => 18000,
                ],
            ],
        ]);
        $editResp->assertSessionHas('success');

        $cheque->refresh();
        $this->assertSame('EDT-1002', $cheque->cheque_no);
        $this->assertSame($newDueDate, $cheque->cheque_date->toDateString());
        $this->assertEquals(18000.00, (float) $cheque->amount);
        $this->assertEquals(102000.00, $this->balanceService->getVendorBalance($this->vendor->id));

        // Clear the cheque -> deleting or editing source voucher must now be blocked!
        $this->post(route('cheques.clear', $cheque->id), [
            'actual_account_id' => $this->zeroBank->id,
            'clearance_date'    => now()->toDateString(),
        ])->assertSessionHas('success');

        $delCleared = $this->delete(route('vouchers.destroy_action', $voucher->id));
        $delCleared->assertSessionHas('error');
        $this->assertNotNull(VoucherMaster::find($voucher->id));

        // Undo clearance, then delete source voucher -> deletes both voucher & pending cheque and restores vendor balance!
        $this->post(route('cheques.undo_clear', $cheque->id))->assertSessionHas('success');
        $delPending = $this->delete(route('vouchers.destroy_action', $voucher->id));
        $delPending->assertSessionHas('success');

        $this->assertNull(VoucherMaster::find($voucher->id));
        $this->assertNull(Cheque::find($cheque->id));
        $this->assertEquals(120000.00, $this->balanceService->getVendorBalance($this->vendor->id));
        $this->assertEquals(0.00, (float) Account::find($chequeIssuedId)->calculated_balance);
    }

    public function test_legacy_payment_and_receipt_vouchers_create_cheques_without_balance_block(): void
    {
        $payHeadId = $this->zeroBank->head_id;

        // Legacy Payment Voucher with payment_mode=cheque on zeroBank
        $payResp = $this->post(route('store_Pay_vochers'), [
            'receipt_date'        => now()->toDateString(),
            'entry_date'          => now()->toDateString(),
            'header_account_head' => $payHeadId,
            'header_account_id'   => $this->zeroBank->id,
            'payment_mode'        => 'cheque',
            'cheque_no'           => 'LEG-PAY-01',
            'cheque_date'         => now()->addDays(10)->toDateString(),
            'remarks'             => 'Legacy cheque payment',
            'vendor_type'         => ['vendor'],
            'vendor_id'           => [$this->vendor->id],
            'narration_id'        => [''],
            'narration_text'      => ['Legacy PDC'],
            'reference_no'        => ['REF-1'],
            'discount_value'      => [0],
            'rate'                => [0],
            'amount'              => [25000],
            'total_amount'        => 25000,
        ]);
        $payResp->assertSessionHas('success');

        $legCheque = Cheque::where('cheque_no', 'LEG-PAY-01')->firstOrFail();
        $this->assertSame(Cheque::TYPE_ISSUED, $legCheque->type);
        $this->assertSame(Cheque::STATUS_PENDING, $legCheque->status);
        $this->assertEquals(0.00, (float) $this->zeroBank->fresh()->opening_balance);
        $this->assertEquals(0.00, (float) $this->zeroBank->fresh()->calculated_balance);
        $this->assertEquals(95000.00, $this->balanceService->getVendorBalance($this->vendor->id));

        // Legacy Receipt Voucher with payment_mode=cheque
        $recResp = $this->post(route('store_rec_vochers'), [
            'receipt_date'     => now()->toDateString(),
            'entry_date'       => now()->toDateString(),
            'payment_mode'     => 'cheque',
            'cheque_no'        => 'LEG-REC-01',
            'cheque_date'      => now()->addDays(2)->toDateString(),
            'bank_name'        => 'Bank Alfalah',
            'vendor_type'      => 'customer',
            'vendor_id'        => $this->customer->id,
            'remarks'          => 'Legacy cheque receipt',
            'narration_id'     => [''],
            'narration_text'   => ['Legacy cheque rec'],
            'row_account_head' => [$payHeadId],
            'row_account_id'   => [$this->zeroBank->id],
            'amount'           => [10000],
            'total_amount'     => 10000,
        ]);
        $recResp->assertSessionHas('success');

        $legRecCheque = Cheque::where('cheque_no', 'LEG-REC-01')->firstOrFail();
        $this->assertSame(Cheque::TYPE_RECEIVED, $legRecCheque->type);
        $this->assertSame(Cheque::STATUS_PENDING, $legRecCheque->status);
        $this->assertEquals(10000.00, (float) Account::find($this->balanceService->getChequeInHandId(1))->calculated_balance);
    }

    public function test_online_bpv_and_overdraft_clear_and_vendor_refund_cheque_bounce(): void
    {
        // 1. Online BPV on zero-balance bank -> direct bank credit, NO cheque created
        $this->post(route('vouchers.store_action', ['type' => 'bpv']), [
            'date'            => now()->toDateString(),
            'branch_id'       => 1,
            'payment_mode'    => 'online',
            'remarks'         => 'Online bank transfer IBFT',
            'pay_target_type' => 'vendor',
            'target_id'       => 'vendor_' . $this->vendor->id,
            'bank_account_id' => $this->zeroBank->id,
            'amount'          => 5000,
        ])->assertSessionHas('success');

        $this->assertEquals(-5000.00, (float) $this->zeroBank->fresh()->calculated_balance);
        $this->assertSame(0, Cheque::where('remarks', 'Online bank transfer IBFT')->count());

        // 2. Direct Expense Cheque issued & cleared even while bank is already negative (overdraft allowed!)
        $this->post(route('cheques.store'), [
            'type'            => 'issued',
            'target_id'       => 'account_' . $this->expenseAccount->id,
            'bank_account_id' => $this->zeroBank->id,
            'cheque_no'       => 'OD-EXP-01',
            'issue_date'      => now()->toDateString(),
            'cheque_date'     => now()->toDateString(),
            'amount'          => 3000,
            'remarks'         => 'Overdraft clear test',
            'branch_id'       => 1,
        ])->assertSessionHas('success');

        $odCheque = Cheque::where('cheque_no', 'OD-EXP-01')->firstOrFail();
        $this->post(route('cheques.clear', $odCheque->id), [
            'actual_account_id' => $this->zeroBank->id,
            'clearance_date'    => now()->toDateString(),
        ])->assertSessionHas('success');

        $this->assertSame(Cheque::STATUS_CLEARED, $odCheque->fresh()->status);
        $this->assertEquals(-8000.00, (float) $this->zeroBank->fresh()->calculated_balance);
        $this->assertEquals(3000.00, (float) $this->expenseAccount->fresh()->calculated_balance);

        // 3. Vendor refund received by cheque via BRV -> bounce reinstates vendor balance
        $beforeVendorBal = $this->balanceService->getVendorBalance($this->vendor->id); // 115,000
        $this->post(route('vouchers.store_action', ['type' => 'brv']), [
            'date'            => now()->toDateString(),
            'branch_id'       => 1,
            'payment_mode'    => 'cheque',
            'cheque_no'       => 'VND-REF-01',
            'cheque_date'     => now()->toDateString(),
            'bank_name'       => 'HBL',
            'remarks'         => 'Vendor refund cheque',
            'party_type'      => 'vendor',
            'party_id'        => 'vendor_' . $this->vendor->id,
            'bank_account_id' => $this->zeroBank->id,
            'amount'          => 6000,
        ])->assertSessionHas('success');

        $this->assertEquals($beforeVendorBal + 6000, $this->balanceService->getVendorBalance($this->vendor->id));
        $vndCheque = Cheque::where('cheque_no', 'VND-REF-01')->firstOrFail();
        $this->post(route('cheques.bounce', $vndCheque->id), [
            'bounce_reason' => 'Signature Mismatch',
        ])->assertSessionHas('success');

        $this->assertEquals($beforeVendorBal, $this->balanceService->getVendorBalance($this->vendor->id));
    }

    public function test_all_cheque_and_voucher_pages_render_without_errors(): void
    {
        // Create a sample cheque due today so navbar badge + table rows + print views all render
        $this->post(route('cheques.store'), [
            'type'            => 'issued',
            'target_id'       => 'vendor_' . $this->vendor->id,
            'bank_account_id' => $this->zeroBank->id,
            'cheque_no'       => 'UI-9001',
            'issue_date'      => now()->toDateString(),
            'cheque_date'     => now()->toDateString(),
            'amount'          => 7500,
            'remarks'         => 'UI render check',
            'branch_id'       => 1,
        ])->assertSessionHas('success');

        $cheque = Cheque::where('cheque_no', 'UI-9001')->firstOrFail();

        $this->get(route('cheques.index'))
            ->assertOk()
            ->assertSee('UI-9001')
            ->assertSee('Due')
            ->assertSee('Cheque(s)');
        $this->get(route('cheques.index', ['type' => 'issued', 'status' => 'due']))->assertOk()->assertSee('UI-9001');
        $this->get(route('vouchers.create_page', ['type' => 'bpv']))->assertOk()->assertSee('Cheque (PDC / Standard)');
        $this->get(route('vouchers.create_page', ['type' => 'brv']))->assertOk()->assertSee('Cheque (PDC / Standard)');
        $this->get(route('vouchers.create_page', ['type' => 'jv']))->assertOk();
        $this->get(route('vouchers.list', ['type' => 'bpv']))->assertOk()->assertSee('UI-9001');
        $this->get(route('vouchers.edit_page', $cheque->voucher_master_id))->assertOk()->assertSee('UI-9001');
        $this->get(route('vouchers.print_unified', $cheque->voucher_master_id))->assertOk()->assertSee('UI-9001');
    }
}
