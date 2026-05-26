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
        Schema::table('customer_ledgers', function (Blueprint $table) {
            $table->unsignedBigInteger('branch_id')->nullable()->after('customer_id');
        });
        Schema::table('vendor_ledgers', function (Blueprint $table) {
            $table->unsignedBigInteger('branch_id')->nullable()->after('vendor_id');
        });
        Schema::table('customer_payments', function (Blueprint $table) {
            $table->unsignedBigInteger('branch_id')->nullable()->after('customer_id');
        });
        Schema::table('vendor_payments', function (Blueprint $table) {
            $table->unsignedBigInteger('branch_id')->nullable()->after('vendor_id');
        });

        // Data migration: Populate branch_id from customers/vendors
        DB::statement('UPDATE customer_ledgers cl JOIN customers c ON cl.customer_id = c.id SET cl.branch_id = c.branch_id WHERE cl.branch_id IS NULL');
        DB::statement('UPDATE vendor_ledgers vl JOIN vendors v ON vl.vendor_id = v.id SET vl.branch_id = v.branch_id WHERE vl.branch_id IS NULL');
        DB::statement('UPDATE customer_payments cp JOIN customers c ON cp.customer_id = c.id SET cp.branch_id = c.branch_id WHERE cp.branch_id IS NULL');
        DB::statement('UPDATE vendor_payments vp JOIN vendors v ON vp.vendor_id = v.id SET vp.branch_id = v.branch_id WHERE vp.branch_id IS NULL');
    }

    public function down(): void
    {
        Schema::table('customer_ledgers', function (Blueprint $table) {
            $table->dropColumn('branch_id');
        });
        Schema::table('vendor_ledgers', function (Blueprint $table) {
            $table->dropColumn('branch_id');
        });
        Schema::table('customer_payments', function (Blueprint $table) {
            $table->dropColumn('branch_id');
        });
        Schema::table('vendor_payments', function (Blueprint $table) {
            $table->dropColumn('branch_id');
        });
    }
};
