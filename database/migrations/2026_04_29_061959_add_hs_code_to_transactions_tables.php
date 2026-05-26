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
        if (!Schema::hasColumn('purchases', 'enable_hs_code')) {
            Schema::table('purchases', function (Blueprint $table) {
                $table->boolean('enable_hs_code')->default(false);
            });
        }
        if (!Schema::hasColumn('purchase_items', 'hs_code')) {
            Schema::table('purchase_items', function (Blueprint $table) {
                $table->string('hs_code')->nullable()->after('line_total');
            });
        }
        if (!Schema::hasColumn('sales', 'enable_hs_code')) {
            Schema::table('sales', function (Blueprint $table) {
                $table->boolean('enable_hs_code')->default(false);
            });
        }
        if (!Schema::hasColumn('sale_items', 'hs_code')) {
            Schema::table('sale_items', function (Blueprint $table) {
                $table->string('hs_code')->nullable()->after('total');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('purchases', 'enable_hs_code')) {
            Schema::table('purchases', function (Blueprint $table) {
                $table->dropColumn('enable_hs_code');
            });
        }
        if (Schema::hasColumn('purchase_items', 'hs_code')) {
            Schema::table('purchase_items', function (Blueprint $table) {
                $table->dropColumn('hs_code');
            });
        }
        if (Schema::hasColumn('sales', 'enable_hs_code')) {
            Schema::table('sales', function (Blueprint $table) {
                $table->dropColumn('enable_hs_code');
            });
        }
        if (Schema::hasColumn('sale_items', 'hs_code')) {
            Schema::table('sale_items', function (Blueprint $table) {
                $table->dropColumn('hs_code');
            });
        }
    }
};
