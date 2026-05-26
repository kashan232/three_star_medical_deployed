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
        Schema::table('purchase_items', function (Blueprint $table) {
            $table->decimal('gst_percent', 12, 2)->default(0)->after('item_discount');
            $table->decimal('gst_amount', 12, 2)->default(0)->after('gst_percent');
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->decimal('total_gst', 12, 2)->default(0)->after('extra_cost');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_items', function (Blueprint $table) {
            $table->dropColumn(['gst_percent', 'gst_amount']);
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn('total_gst');
        });
    }
};
