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
            $table->string('uom_name')->nullable()->after('product_id');
            $table->decimal('uom_factor', 18, 4)->default(1)->after('uom_name');
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->string('uom_name')->nullable()->after('product_name');
            $table->decimal('uom_factor', 18, 4)->default(1)->after('uom_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_items', function (Blueprint $table) {
            $table->dropColumn(['uom_name', 'uom_factor']);
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn(['uom_name', 'uom_factor']);
        });
    }
};
