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
            $table->string('item_discount_type')->default('amount')->after('item_discount');
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->string('discount_type')->default('amount')->after('discount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_items', function (Blueprint $table) {
            $table->dropColumn('item_discount_type');
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn('discount_type');
        });
    }
};
