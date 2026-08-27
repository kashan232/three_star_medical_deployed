<?php

namespace App\Services;

use App\Models\Account;
use App\Models\JournalEntry;
use Illuminate\Database\Eloquent\Model;

class JournalEntryService
{
    /**
     * Create a Journal Entry and update Account Balance.
     *
     * @param  Model  $source  The source model (VoucherMaster, Sale, etc.)
     * @param  string  $date  (Y-m-d)
     * @param  Model|null  $party  (Optional Customer/Vendor model)
     */
    public function recordEntry(Model $source, ?int $accountId, float $debit, float $credit, ?string $description, string $date, ?Model $party = null)
    {
        if (empty($accountId)) {
            \Log::warning("JournalEntryService: Skipping entry because accountId is empty for source " . get_class($source) . " #{$source->id}");
            return null;
        }

        // 1. Create Journal Entry
        $data = [
            'source_type' => get_class($source),
            'source_id' => $source->id,
            'account_id' => $accountId,
            'entry_date' => $date,
            'debit' => $debit,
            'credit' => $credit,
            'description' => $description,
            'branch_id' => $source->branch_id ?? null,
        ];

        if ($party) {
            $data['party_type'] = get_class($party);
            $data['party_id'] = $party->id;
        }

        $entry = JournalEntry::create($data);

        // 2. Update Account Balance
        $this->updateAccountBalance($accountId, $debit, $credit);

        return $entry;
    }

    /**
     * Reverse and delete all Journal Entries for a given source model.
     */
    public function reverseEntriesForSource(Model $source)
    {
        $entries = JournalEntry::where('source_type', get_class($source))
            ->where('source_id', $source->id)
            ->get();

        foreach ($entries as $entry) {
            // Revert balance on Account: Dr subtracted, Cr added
            $this->revertAccountBalance($entry->account_id, $entry->debit, $entry->credit);
            $entry->delete();
        }
    }

    /**
     * Update the real-time balance on the Account model.
     */
    private function updateAccountBalance(int $accountId, float $debit, float $credit)
    {
        $account = Account::find($accountId);
        if (! $account) {
            return;
        }

        $netChange = $debit - $credit;
        $currentBalance = $account->current_balance ?? 0;
        $account->current_balance = $currentBalance + $netChange;
        $account->save();
    }

    /**
     * Revert the real-time balance on the Account model.
     */
    private function revertAccountBalance(int $accountId, float $debit, float $credit)
    {
        $account = Account::find($accountId);
        if (! $account) {
            return;
        }

        $netChange = $debit - $credit;
        $currentBalance = $account->current_balance ?? 0;
        $account->current_balance = $currentBalance - $netChange;
        $account->save();
    }
}
