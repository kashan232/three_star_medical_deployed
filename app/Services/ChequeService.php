<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Branch;
use App\Models\Cheque;
use App\Models\Customer;
use App\Models\Vendor;
use App\Models\VoucherMaster;
use Illuminate\Support\Facades\DB;

class ChequeService
{
    public function __construct(
        protected BalanceService $balanceService,
        protected VoucherService $voucherService
    ) {
    }

    /**
     * Identify whether an account is a physical Cash-in-Hand account (subject to strict non-negative check).
     * Bank accounts and cheque clearing control accounts are NEVER restricted here.
     */
    public static function isCashAccount(?Account $account): bool
    {
        if (! $account) {
            return false;
        }

        $account->loadMissing('head');
        $title = strtolower(trim($account->title ?? ''));
        $code = strtolower(trim($account->account_code ?? ''));
        $headName = strtolower(trim($account->head?->name ?? ''));

        if (
            str_contains($title, 'cheque') ||
            str_contains($headName, 'cheque') ||
            str_starts_with($code, '1-02-045') ||
            str_starts_with($code, '2-02-015')
        ) {
            return false;
        }

        if (
            str_starts_with($code, '1-02-052') ||
            str_contains($title, 'bank') ||
            str_contains($headName, 'bank')
        ) {
            return false;
        }

        return str_starts_with($code, '1-02-040')
            || str_contains($title, 'cash')
            || str_contains($headName, 'cash');
    }

    /**
     * Prevent issuing the exact same active cheque number twice on the same bank account.
     */
    public function ensureUniqueIssuedCheque(?int $bankAccountId, string $chequeNo, ?int $ignoreChequeId = null): void
    {
        $chequeNo = trim($chequeNo);
        if ($chequeNo === '') {
            throw new \InvalidArgumentException('Cheque number is required.');
        }
        if (! $bankAccountId) {
            return;
        }

        $query = Cheque::where('type', Cheque::TYPE_ISSUED)
            ->where('actual_account_id', $bankAccountId)
            ->where('cheque_no', $chequeNo)
            ->whereIn('status', [Cheque::STATUS_PENDING, Cheque::STATUS_DEPOSITED, Cheque::STATUS_CLEARED]);

        if ($ignoreChequeId) {
            $query->where('id', '!=', $ignoreChequeId);
        }

        if ($query->exists()) {
            $bankTitle = Account::where('id', $bankAccountId)->value('title') ?? 'selected bank';
            throw new \InvalidArgumentException("Cheque #{$chequeNo} has already been issued from {$bankTitle}.");
        }
    }

    /**
     * Register a Cheque record linked to a posted VoucherMaster.
     */
    public function registerCheque(array $data): Cheque
    {
        $type = $data['type'] ?? Cheque::TYPE_RECEIVED;
        $chequeNo = trim((string) ($data['cheque_no'] ?? ''));
        $actualAccountId = ! empty($data['actual_account_id']) ? (int) $data['actual_account_id'] : null;

        if ($type === Cheque::TYPE_ISSUED) {
            $this->ensureUniqueIssuedCheque($actualAccountId, $chequeNo);
        } elseif ($chequeNo === '') {
            throw new \InvalidArgumentException('Cheque number is required.');
        }

        return Cheque::create([
            'type'                => $type,
            'voucher_master_id'   => $data['voucher_master_id'],
            'branch_id'           => $data['branch_id'] ?? 1,
            'party_type'          => $data['party_type'] ?? null,
            'party_id'            => $data['party_id'] ?? null,
            'cheque_no'           => $chequeNo,
            'issue_date'          => $data['issue_date'] ?? now()->toDateString(),
            'cheque_date'         => $data['cheque_date'] ?? now()->toDateString(),
            'bank_name'           => $data['bank_name'] ?? null,
            'status'              => $data['status'] ?? Cheque::STATUS_PENDING,
            'amount'              => (float) $data['amount'],
            'actual_account_id'   => $actualAccountId,
            'clearing_account_id' => $data['clearing_account_id'] ?? null,
            'remarks'             => $data['remarks'] ?? null,
            'created_by'          => $data['created_by'] ?? auth()->id(),
        ]);
    }

    /**
     * Create a Cheque directly from the Cheque Management console (automatically creates backed BPV/BRV).
     * Never blocks issued cheques on low/zero/negative bank balance.
     */
    public function createDirectChequeVoucher(array $data, ?int $userId = null): Cheque
    {
        return DB::transaction(function () use ($data, $userId) {
            $direction = strtolower($data['type'] ?? Cheque::TYPE_ISSUED);
            if (! in_array($direction, [Cheque::TYPE_ISSUED, Cheque::TYPE_RECEIVED], true)) {
                throw new \InvalidArgumentException('Invalid cheque direction.');
            }

            $branchId = (int) ($data['branch_id'] ?? auth()->user()?->getBranchId() ?? 1);
            $branch = Branch::find($branchId);
            $locationName = $branch ? $branch->name : 'HEAD OFFICE';

            $bankAccount = Account::findOrFail((int) $data['bank_account_id']);
            $amount = (float) $data['amount'];
            if ($amount <= 0) {
                throw new \InvalidArgumentException('Cheque amount must be greater than zero.');
            }

            $chequeNo = trim((string) ($data['cheque_no'] ?? ''));
            $issueDate = $data['issue_date'] ?? now()->toDateString();
            $chequeDate = $data['cheque_date'] ?? $issueDate;

            if ($direction === Cheque::TYPE_ISSUED) {
                $this->ensureUniqueIssuedCheque($bankAccount->id, $chequeNo);
            }

            [$partyType, $partyId, $counterAccountId] = $this->resolveTarget(
                (string) ($data['target_id'] ?? ''),
                $branchId
            );

            $userRemarks = trim((string) ($data['remarks'] ?? ''));
            $narration = ($direction === Cheque::TYPE_ISSUED ? 'Cheque Payment' : 'Cheque Receipt')
                . " #{$chequeNo} (Due: {$chequeDate}, Bank: {$bankAccount->title})"
                . ($userRemarks !== '' ? " - {$userRemarks}" : '');

            $lines = [];
            if ($direction === Cheque::TYPE_ISSUED) {
                $clearingAccountId = $this->balanceService->getChequeIssuedId($branchId);
                $voucherType = VoucherMaster::TYPE_BPV;

                $lines[] = [
                    'account_id' => $counterAccountId,
                    'party_type' => $partyType,
                    'party_id'   => $partyId,
                    'debit'      => $amount,
                    'credit'     => 0,
                    'narration'  => $narration,
                ];
                $lines[] = [
                    'account_id' => $clearingAccountId,
                    'debit'      => 0,
                    'credit'     => $amount,
                    'narration'  => $narration,
                ];
            } else {
                $clearingAccountId = $this->balanceService->getChequeInHandId($branchId);
                $voucherType = VoucherMaster::TYPE_BRV;

                $lines[] = [
                    'account_id' => $clearingAccountId,
                    'debit'      => $amount,
                    'credit'     => 0,
                    'narration'  => $narration,
                ];
                $lines[] = [
                    'account_id' => $counterAccountId,
                    'party_type' => $partyType,
                    'party_id'   => $partyId,
                    'debit'      => 0,
                    'credit'     => $amount,
                    'narration'  => $narration,
                ];
            }

            $voucher = $this->voucherService->createVoucher([
                'voucher_type' => $voucherType,
                'date'         => $issueDate,
                'location'     => $locationName,
                'branch_id'    => $branchId,
                'party_type'   => $partyType,
                'party_id'     => $partyId,
                'cheque_no'    => $chequeNo,
                'cheque_date'  => $chequeDate,
                'remarks'      => $narration,
                'status'       => VoucherMaster::STATUS_POSTED,
            ], $lines, $userId ?? auth()->id());

            return $this->registerCheque([
                'type'                => $direction,
                'voucher_master_id'   => $voucher->id,
                'branch_id'           => $branchId,
                'party_type'          => $partyType,
                'party_id'            => $partyId,
                'cheque_no'           => $chequeNo,
                'issue_date'          => $issueDate,
                'cheque_date'         => $chequeDate,
                'bank_name'           => ! empty($data['bank_name']) ? trim((string) $data['bank_name']) : $bankAccount->title,
                'status'              => Cheque::STATUS_PENDING,
                'amount'              => $amount,
                'actual_account_id'   => $bankAccount->id,
                'clearing_account_id' => $clearingAccountId,
                'remarks'             => $userRemarks ?: null,
                'created_by'          => $userId ?? auth()->id(),
            ]);
        });
    }

    /**
     * Mark a received pending cheque as deposited into a bank account (awaiting clearing).
     */
    public function depositCheque(int $chequeId, int $bankAccountId): Cheque
    {
        return DB::transaction(function () use ($chequeId, $bankAccountId) {
            $cheque = Cheque::lockForUpdate()->findOrFail($chequeId);

            if ($cheque->type !== Cheque::TYPE_RECEIVED) {
                throw new \RuntimeException('Only received cheques can be marked as deposited.');
            }
            if ($cheque->status !== Cheque::STATUS_PENDING) {
                throw new \RuntimeException('Only pending cheques can be deposited.');
            }

            $bankAccount = Account::findOrFail($bankAccountId);

            $cheque->update([
                'status'            => Cheque::STATUS_DEPOSITED,
                'actual_account_id' => $bankAccount->id,
                'deposited_at'      => now(),
            ]);

            return $cheque->fresh();
        });
    }

    /**
     * Clear a pending or deposited cheque into/from the bank account via a balanced Clearance JV.
     * Issued cheques are allowed to clear even if bank balance is low (e.g. overdraft), while bank GL is accurately credited.
     */
    public function clearCheque(
        int $chequeId,
        ?int $bankAccountId = null,
        ?string $clearanceDate = null,
        ?string $notes = null,
        ?int $userId = null
    ): Cheque {
        return DB::transaction(function () use ($chequeId, $bankAccountId, $clearanceDate, $notes, $userId) {
            $cheque = Cheque::with('voucherMaster.details')->lockForUpdate()->findOrFail($chequeId);

            if (! in_array($cheque->status, [Cheque::STATUS_PENDING, Cheque::STATUS_DEPOSITED], true)) {
                throw new \RuntimeException('Only pending or deposited cheques can be cleared.');
            }

            $voucher = $cheque->voucherMaster;
            $branchId = (int) ($cheque->branch_id ?? $voucher?->branch_id ?? 1);
            $targetBankId = $bankAccountId ?: $cheque->actual_account_id;

            if (! $targetBankId) {
                throw new \RuntimeException('Please select a target bank account to clear this cheque.');
            }

            $bankAccount = Account::findOrFail($targetBankId);
            $clearDate = $clearanceDate ?: now()->toDateString();
            $amount = (float) $cheque->amount;
            $clearingAccountId = $this->resolveClearingAccountId($cheque, $branchId);

            $isIssued = ($cheque->type === Cheque::TYPE_ISSUED);
            $voucherRef = $voucher?->voucher_no ?? ('CHQ-' . $cheque->id);
            $desc = ($isIssued ? 'Cleared Issued' : 'Cleared Received')
                . " Cheque #{$cheque->cheque_no} ({$bankAccount->title}) Ref: {$voucherRef}"
                . ($notes ? " - {$notes}" : '');

            // Check if the original voucher posted to the clearing account (standard flow) or directly to bank (legacy flow)
            $usedClearingAccount = ! $voucher || $voucher->details->contains(fn ($d) => (int) $d->account_id === (int) $clearingAccountId);

            $clearanceVoucherId = null;
            if ($usedClearingAccount) {
                if ($isIssued) {
                    // Dr Cheques Issued (PDC), Cr Bank Account
                    $lines = [
                        [
                            'account_id' => $clearingAccountId,
                            'debit'      => $amount,
                            'credit'     => 0,
                            'narration'  => $desc,
                        ],
                        [
                            'account_id' => $bankAccount->id,
                            'debit'      => 0,
                            'credit'     => $amount,
                            'narration'  => $desc,
                        ],
                    ];
                } else {
                    // Dr Bank Account, Cr Cheque In Hand
                    $lines = [
                        [
                            'account_id' => $bankAccount->id,
                            'debit'      => $amount,
                            'credit'     => 0,
                            'narration'  => $desc,
                        ],
                        [
                            'account_id' => $clearingAccountId,
                            'debit'      => 0,
                            'credit'     => $amount,
                            'narration'  => $desc,
                        ],
                    ];
                }

                $clearVoucher = $this->voucherService->createVoucher([
                    'voucher_type' => VoucherMaster::TYPE_JV,
                    'date'         => $clearDate,
                    'cheque_no'    => $cheque->cheque_no,
                    'cheque_date'  => $cheque->cheque_date,
                    'remarks'      => $desc,
                    'branch_id'    => $branchId,
                    'status'       => VoucherMaster::STATUS_POSTED,
                    'sync_ledger'  => false,
                ], $lines, $userId ?? auth()->id());

                $clearanceVoucherId = $clearVoucher->id;
            }

            $cheque->update([
                'status'               => Cheque::STATUS_CLEARED,
                'actual_account_id'    => $bankAccount->id,
                'clearing_account_id'  => $usedClearingAccount ? $clearingAccountId : $cheque->clearing_account_id,
                'clearance_voucher_id' => $clearanceVoucherId,
                'clearance_date'       => $clearDate,
                'cleared_at'           => now(),
                'cleared_by'           => $userId ?? auth()->id(),
                'remarks'              => $notes ? trim(($cheque->remarks ? $cheque->remarks . ' | ' : '') . "Clearance: {$notes}") : $cheque->remarks,
            ]);

            return $cheque->fresh();
        });
    }

    /**
     * Mark a pending or deposited cheque as bounced (dishonoured).
     */
    public function bounceCheque(
        int $chequeId,
        ?string $reason = null,
        ?string $bounceDate = null,
        ?int $userId = null
    ): Cheque {
        return $this->reversePendingCheque($chequeId, Cheque::STATUS_BOUNCED, $reason, $bounceDate, $userId);
    }

    /**
     * Cancel / stop payment on a pending or deposited cheque cleanly.
     */
    public function cancelCheque(
        int $chequeId,
        ?string $reason = null,
        ?string $cancelDate = null,
        ?int $userId = null
    ): Cheque {
        return $this->reversePendingCheque($chequeId, Cheque::STATUS_CANCELLED, $reason, $cancelDate, $userId);
    }

    /**
     * Undo a previously cleared cheque (deletes its clearance JV and returns status to pending).
     */
    public function undoClearance(int $chequeId): Cheque
    {
        return DB::transaction(function () use ($chequeId) {
            $cheque = Cheque::lockForUpdate()->findOrFail($chequeId);

            if ($cheque->status !== Cheque::STATUS_CLEARED) {
                throw new \RuntimeException('Only cleared cheques can have their clearance reversed.');
            }

            $clearanceVoucherId = $cheque->clearance_voucher_id;

            $cheque->update([
                'status'               => Cheque::STATUS_PENDING,
                'clearance_voucher_id' => null,
                'clearance_date'       => null,
                'cleared_at'           => null,
                'cleared_by'           => null,
            ]);

            if ($clearanceVoucherId) {
                $clearVoucher = VoucherMaster::find($clearanceVoucherId);
                if ($clearVoucher) {
                    $this->voucherService->deleteVoucher($clearVoucher);
                }
            }

            return $cheque->fresh();
        });
    }

    /**
     * Shared reversal workflow for bouncing or cancelling a pending/deposited cheque.
     */
    private function reversePendingCheque(
        int $chequeId,
        string $targetStatus,
        ?string $reason,
        ?string $actionDate,
        ?int $userId
    ): Cheque {
        return DB::transaction(function () use ($chequeId, $targetStatus, $reason, $actionDate, $userId) {
            $cheque = Cheque::with('voucherMaster.details')->lockForUpdate()->findOrFail($chequeId);

            if (! in_array($cheque->status, [Cheque::STATUS_PENDING, Cheque::STATUS_DEPOSITED], true)) {
                throw new \RuntimeException('Only pending or deposited cheques can be bounced or cancelled.');
            }

            $voucher = $cheque->voucherMaster;
            $branchId = (int) ($cheque->branch_id ?? $voucher?->branch_id ?? 1);
            $date = $actionDate ?: now()->toDateString();
            $amount = (float) $cheque->amount;
            $isIssued = ($cheque->type === Cheque::TYPE_ISSUED);

            $clearingAccountId = $this->resolveClearingAccountId($cheque, $branchId);
            $usedClearingAccount = ! $voucher || $voucher->details->contains(fn ($d) => (int) $d->account_id === (int) $clearingAccountId);
            $creditOrDebitClearing = $usedClearingAccount ? $clearingAccountId : (int) $cheque->actual_account_id;

            $counter = $this->resolveCounterAccountAndParty($cheque, $branchId, $creditOrDebitClearing);
            if (! $counter['account_id']) {
                throw new \RuntimeException('Could not determine the counter account to reverse this cheque.');
            }

            $actionWord = $targetStatus === Cheque::STATUS_CANCELLED ? 'Cancelled' : 'Bounced';
            $defaultReason = $targetStatus === Cheque::STATUS_CANCELLED ? 'Cancelled / Stop Payment' : 'Cheque Dishonoured / Bounced';
            $finalReason = trim((string) ($reason ?: $defaultReason));
            $voucherRef = $voucher?->voucher_no ?? ('CHQ-' . $cheque->id);
            $desc = "{$actionWord} " . ($isIssued ? 'Issued' : 'Received') . " Cheque #{$cheque->cheque_no} Ref: {$voucherRef} ({$finalReason})";

            if ($isIssued) {
                // Reverse BPV: Dr Cheques Issued (PDC), Cr Vendor/AP or Counter Account
                $lines = [
                    [
                        'account_id' => $creditOrDebitClearing,
                        'debit'      => $amount,
                        'credit'     => 0,
                        'narration'  => $desc,
                    ],
                    [
                        'account_id' => $counter['account_id'],
                        'party_type' => $counter['party_type'],
                        'party_id'   => $counter['party_id'],
                        'debit'      => 0,
                        'credit'     => $amount,
                        'narration'  => $desc,
                    ],
                ];
            } else {
                // Reverse BRV: Dr Customer/AR or Counter Account, Cr Cheque In Hand
                $lines = [
                    [
                        'account_id' => $counter['account_id'],
                        'party_type' => $counter['party_type'],
                        'party_id'   => $counter['party_id'],
                        'debit'      => $amount,
                        'credit'     => 0,
                        'narration'  => $desc,
                    ],
                    [
                        'account_id' => $creditOrDebitClearing,
                        'debit'      => 0,
                        'credit'     => $amount,
                        'narration'  => $desc,
                    ],
                ];
            }

            $reversalVoucher = $this->voucherService->createVoucher([
                'voucher_type' => VoucherMaster::TYPE_JV,
                'date'         => $date,
                'cheque_no'    => $cheque->cheque_no,
                'cheque_date'  => $cheque->cheque_date,
                'party_type'   => $counter['party_type'],
                'party_id'     => $counter['party_id'],
                'remarks'      => $desc,
                'branch_id'    => $branchId,
                'status'       => VoucherMaster::STATUS_POSTED,
                'sync_ledger'  => true,
            ], $lines, $userId ?? auth()->id());

            $updates = [
                'status'              => $targetStatus,
                'clearing_account_id' => $creditOrDebitClearing,
                'bounce_voucher_id'   => $reversalVoucher->id,
                'bounce_reason'       => $finalReason,
                'bounced_by'          => $userId ?? auth()->id(),
            ];

            if ($targetStatus === Cheque::STATUS_CANCELLED) {
                $updates['cancelled_at'] = now();
            } else {
                $updates['bounced_at'] = now();
            }

            $cheque->update($updates);

            return $cheque->fresh();
        });
    }

    private function resolveClearingAccountId(Cheque $cheque, int $branchId): int
    {
        if ($cheque->clearing_account_id) {
            return (int) $cheque->clearing_account_id;
        }

        return $cheque->type === Cheque::TYPE_ISSUED
            ? (int) $this->balanceService->getChequeIssuedId($branchId)
            : (int) $this->balanceService->getChequeInHandId($branchId);
    }

    /**
     * Resolve party and counter GL account ID for reversing a cheque.
     */
    private function resolveCounterAccountAndParty(Cheque $cheque, int $branchId, int $clearingAccountId): array
    {
        $voucher = $cheque->voucherMaster;
        $partyType = $cheque->party_type ?: $voucher?->party_type;
        $partyId = $cheque->party_id ?: $voucher?->party_id;

        if ($partyType === Customer::class && $partyId) {
            return [
                'account_id' => $this->balanceService->getAccountsReceivableId($branchId),
                'party_type' => Customer::class,
                'party_id'   => (int) $partyId,
            ];
        }

        if ($partyType === Vendor::class && $partyId) {
            return [
                'account_id' => $this->balanceService->getAccountsPayableId($branchId),
                'party_type' => Vendor::class,
                'party_id'   => (int) $partyId,
            ];
        }

        if ($partyType === Account::class && $partyId) {
            return [
                'account_id' => (int) $partyId,
                'party_type' => null,
                'party_id'   => null,
            ];
        }

        if ($voucher) {
            $counterDetail = $voucher->details
                ->first(fn ($d) => (int) $d->account_id !== $clearingAccountId);

            if ($counterDetail) {
                return [
                    'account_id' => (int) $counterDetail->account_id,
                    'party_type' => $counterDetail->party_type,
                    'party_id'   => $counterDetail->party_id ? (int) $counterDetail->party_id : null,
                ];
            }
        }

        return [
            'account_id' => null,
            'party_type' => null,
            'party_id'   => null,
        ];
    }

    /**
     * Resolve polymorphic target string ('vendor_1', 'customer_2', 'account_5') into [partyType, partyId, accountId].
     */
    private function resolveTarget(string $targetStr, int $branchId): array
    {
        $arAccountId = $this->balanceService->getAccountsReceivableId($branchId);
        $apAccountId = $this->balanceService->getAccountsPayableId($branchId);

        if (str_starts_with($targetStr, 'vendor_')) {
            $vId = (int) str_replace('vendor_', '', $targetStr);
            Vendor::findOrFail($vId);
            return [Vendor::class, $vId, $apAccountId];
        }

        if (str_starts_with($targetStr, 'customer_')) {
            $cId = (int) str_replace('customer_', '', $targetStr);
            Customer::findOrFail($cId);
            return [Customer::class, $cId, $arAccountId];
        }

        if (str_starts_with($targetStr, 'account_')) {
            $accId = (int) str_replace('account_', '', $targetStr);
            Account::findOrFail($accId);
            return [null, null, $accId];
        }

        throw new \InvalidArgumentException('Please select a valid party or account.');
    }
}
