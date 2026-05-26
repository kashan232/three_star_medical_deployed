<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Sales Table
        if (Schema::hasTable('sales')) {
            if (Schema::hasColumn('sales', 'invoice_no')) {
                DB::table('sales')
                    ->where('invoice_no', 'LIKE', '%SRN-%')
                    ->update(['invoice_no' => DB::raw("REPLACE(invoice_no, 'SRN-', 'SIN-')")]);
            }
            if (Schema::hasColumn('sales', 'reference')) {
                DB::table('sales')
                    ->where('reference', 'LIKE', '%SRN-%')
                    ->update(['reference' => DB::raw("REPLACE(reference, 'SRN-', 'SIN-')")]);
            }
        }

        // 2. Product Bookings
        if (Schema::hasTable('product_bookings')) {
            if (Schema::hasColumn('product_bookings', 'invoice_no')) {
                DB::table('product_bookings')
                    ->where('invoice_no', 'LIKE', '%SRN-%')
                    ->update(['invoice_no' => DB::raw("REPLACE(invoice_no, 'SRN-', 'SIN-')")]);
            }
            if (Schema::hasColumn('product_bookings', 'reference')) {
                DB::table('product_bookings')
                    ->where('reference', 'LIKE', '%SRN-%')
                    ->update(['reference' => DB::raw("REPLACE(reference, 'SRN-', 'SIN-')")]);
            }
        }

        // 3. Customer Ledgers
        if (Schema::hasTable('customer_ledgers')) {
            if (Schema::hasColumn('customer_ledgers', 'description')) {
                DB::table('customer_ledgers')
                    ->where('description', 'LIKE', '%SRN-%')
                    ->update(['description' => DB::raw("REPLACE(description, 'SRN-', 'SIN-')")]);
            }
        }

        // 4. Stock Movements
        if (Schema::hasTable('stock_movements')) {
            if (Schema::hasColumn('stock_movements', 'note')) {
                DB::table('stock_movements')
                    ->where('note', 'LIKE', '%SRN-%')
                    ->update(['note' => DB::raw("REPLACE(note, 'SRN-', 'SIN-')")]);
            }
        }

        // 5. Journal Entries
        if (Schema::hasTable('journal_entries')) {
            if (Schema::hasColumn('journal_entries', 'description')) {
                DB::table('journal_entries')
                    ->where('description', 'LIKE', '%SRN-%')
                    ->update(['description' => DB::raw("REPLACE(description, 'SRN-', 'SIN-')")]);
            }
        }

        // 6. Voucher Masters (ERP System)
        if (Schema::hasTable('voucher_masters')) {
            if (Schema::hasColumn('voucher_masters', 'remarks')) {
                DB::table('voucher_masters')
                    ->where('remarks', 'LIKE', '%SRN-%')
                    ->update(['remarks' => DB::raw("REPLACE(remarks, 'SRN-', 'SIN-')")]);
            }
        }

        // 7. Voucher Details (ERP System)
        if (Schema::hasTable('voucher_details')) {
            if (Schema::hasColumn('voucher_details', 'narration')) {
                DB::table('voucher_details')
                    ->where('narration', 'LIKE', '%SRN-%')
                    ->update(['narration' => DB::raw("REPLACE(narration, 'SRN-', 'SIN-')")]);
            }
        }

        // 8. Legacy Vouchers
        if (Schema::hasTable('vouchers')) {
            if (Schema::hasColumn('vouchers', 'narration')) {
                DB::table('vouchers')
                    ->where('narration', 'LIKE', '%SRN-%')
                    ->update(['narration' => DB::raw("REPLACE(narration, 'SRN-', 'SIN-')")]);
            }
        }

        // 9. Customer Payments
        if (Schema::hasTable('customer_payments')) {
            if (Schema::hasColumn('customer_payments', 'description')) {
                DB::table('customer_payments')
                    ->where('description', 'LIKE', '%SRN-%')
                    ->update(['description' => DB::raw("REPLACE(description, 'SRN-', 'SIN-')")]);
            }
        }

        // 10. Receipts Vouchers
        if (Schema::hasTable('receipts_vouchers')) {
            if (Schema::hasColumn('receipts_vouchers', 'remarks')) {
                DB::table('receipts_vouchers')
                    ->where('remarks', 'LIKE', '%SRN-%')
                    ->update(['remarks' => DB::raw("REPLACE(remarks, 'SRN-', 'SIN-')")]);
            }
        }

        // 11. Delivery Notes
        if (Schema::hasTable('delivery_notes')) {
            if (Schema::hasColumn('delivery_notes', 'note')) {
                DB::table('delivery_notes')
                    ->where('note', 'LIKE', '%SRN-%')
                    ->update(['note' => DB::raw("REPLACE(note, 'SRN-', 'SIN-')")]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Simplified reverse logic for major tables
        if (Schema::hasTable('sales')) {
            if (Schema::hasColumn('sales', 'invoice_no')) {
                DB::table('sales')->where('invoice_no', 'LIKE', '%SIN-%')->update(['invoice_no' => DB::raw("REPLACE(invoice_no, 'SIN-', 'SRN-')")]);
            }
        }
    }
};
