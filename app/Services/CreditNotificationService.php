<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Vendor;
use App\Models\Sale;
use App\Models\Purchase;
use App\Models\SystemNotification;
use App\Models\Setting;
use App\Events\NotificationSent;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CreditNotificationService
{
    /**
     * Check if a sale invoice is overdue and trigger notification if it is.
     * 
     * @param Sale $sale
     * @return void
     */
    public function checkSaleOverdue(Sale $sale)
    {
        $customer = $sale->customer;
        if (!$customer) return;

        $creditTerms = (int) $customer->credit_terms;
        if ($creditTerms <= 0) return;

        $dueDate = Carbon::parse($sale->sale_date)->addDays($creditTerms);
        
        // Use the new accessor we added to the Sale model
        $dueAmount = (float) $sale->due_amount;

        if (now()->startOfDay()->gt($dueDate->startOfDay()) && $dueAmount > 0) {
            $days = (int) now()->startOfDay()->diffInDays($dueDate->startOfDay());
            $partyName = $customer->customer_name;
            $partyCode = $customer->customer_id;

            $title = "Overdue Payment - Customer: {$partyName} ({$partyCode})";
            $message = "Sale #{$sale->invoice_no} from Customer '{$partyName}' is {$days} days overdue. Due amount: " . number_format($dueAmount, 2) . ". Due date was " . $dueDate->format('d/m/Y');
            
            $this->createNotification($title, $message, $sale, 'Customer');
        }
    }

    /**
     * Check if a purchase invoice is overdue and trigger notification if it is.
     * 
     * @param Purchase $purchase
     * @return void
     */
    public function checkPurchaseOverdue(Purchase $purchase)
    {
        $vendor = $purchase->vendor;
        if (!$vendor) return;

        $creditTerms = (int) $vendor->credit_terms;
        if ($creditTerms <= 0) return;

        $dueDate = Carbon::parse($purchase->purchase_date)->addDays($creditTerms);
        
        $dueAmount = (float) $purchase->due_amount;

        if (now()->startOfDay()->gt($dueDate->startOfDay()) && $dueAmount > 0) {
            $days = (int) now()->startOfDay()->diffInDays($dueDate->startOfDay());
            $partyName = $vendor->name;
            $partyCode = $vendor->vendor_code;

            $title = "Overdue Payment - Vendor: {$partyName} ({$partyCode})";
            $message = "Purchase #{$purchase->invoice_no} from Vendor '{$partyName}' is {$days} days overdue. Due amount: " . number_format($dueAmount, 2) . ". Due date was " . $dueDate->format('d/m/Y');
            
            $this->createNotification($title, $message, $purchase, 'Vendor');
        }
    }

    /**
     * Check if a customer has exceeded their credit limit.
     * 
     * @param Customer $customer
     * @return void
     */
    public function checkCustomerCreditLimit(Customer $customer)
    {
        $limit = (float) $customer->balance_range; // balance_range is used as credit_limit in DB for customers
        if ($limit <= 0) {
            return;
        }

        $balance = app(BalanceService::class)->getCustomerBalance($customer->id);
        if ($balance > $limit) {
            $title = "Credit Limit Exceeded - Customer: {$customer->customer_name} ({$customer->customer_id})";
            $message = "Customer '{$customer->customer_name}' has exceeded their credit limit of " . number_format($limit, 2) . ". Current balance: " . number_format($balance, 2);
            
            $this->createNotification($title, $message, $customer, 'Customer');
        }
    }

    /**
     * Create and broadcast notifications to designated recipients.
     */
    private function createNotification($title, $message, $source, $type)
    {
        // Check if notification already exists for this source to avoid duplicates
        $exists = SystemNotification::where('source_type', get_class($source))
            ->where('source_id', $source->id)
            ->where('title', $title)
            ->where('is_read', false)
            ->exists();

        if ($exists) {
            return;
        }

        // Determine which recipient list to use
        $settingKey = ($type === 'Customer') ? 'sale_notification_recipients' : 'purchase_notification_recipients';
        $recipients = Setting::get($settingKey, []);
        
        if (empty($recipients)) {
            // Default to Super Admin if no recipients configured
            $recipients = \App\Models\User::role('Super Admin')->pluck('id')->toArray();
        }

        foreach ($recipients as $userId) {
            $notification = SystemNotification::create([
                'user_id' => $userId,
                'title' => $title,
                'message' => $message,
                'type' => 'credit_alert',
                'source_type' => get_class($source),
                'source_id' => $source->id,
                'is_read' => false,
            ]);

            // Broadcast real-time event via WebSockets (Reverb)
            try {
                broadcast(new NotificationSent($notification));
            } catch (\Exception $e) {
                Log::error('Broadcast error: ' . $e->getMessage());
            }
        }
    }

    /**
     * Clear notifications for a source (e.g. when paid)
     */
    public function clearNotifications($source)
    {
        SystemNotification::where('source_type', get_class($source))
            ->where('source_id', $source->id)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
    }
}
