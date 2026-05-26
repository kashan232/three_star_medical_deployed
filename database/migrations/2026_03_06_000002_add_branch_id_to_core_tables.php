<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tables to add branch_id to (all existing records default to branch 1).
     */
    public function up(): void
    {
        $tables = [
            'sales',
            'sale_returns',
            'purchase_returns',
            'customers',
            'vendors',
            'accounts',
            'account_heads',
            'voucher_masters',
            'journal_entries',
            'warehouses',
            'warehouse_stocks',
            'product_batches',
        ];

        foreach ($tables as $tableName) {
            if (! Schema::hasColumn($tableName, 'branch_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->unsignedBigInteger('branch_id')->nullable()->default(1)->after('id');
                });

                // Assign all existing records to branch 1 (head office)
                DB::table($tableName)->whereNull('branch_id')->update(['branch_id' => 1]);
            }
        }
    }

    public function down(): void
    {
        $tables = [
            'sales',
            'sale_returns',
            'purchase_returns',
            'customers',
            'vendors',
            'accounts',
            'account_heads',
            'voucher_masters',
            'journal_entries',
            'warehouses',
            'warehouse_stocks',
            'product_batches',
        ];

        foreach ($tables as $tableName) {
            if (Schema::hasColumn($tableName, 'branch_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropColumn('branch_id');
                });
            }
        }
    }
};
