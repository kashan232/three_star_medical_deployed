<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->date('sale_date')->nullable()->after('invoice_no');
            $table->string('vendor_bill_no')->nullable()->after('sale_order_no');
            $table->string('order_no')->nullable()->after('vendor_bill_no');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['sale_date', 'vendor_bill_no', 'order_no']);
        });
    }
};
