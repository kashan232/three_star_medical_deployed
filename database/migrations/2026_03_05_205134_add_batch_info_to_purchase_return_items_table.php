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
        Schema::table('purchase_return_items', function (Blueprint $table) {
            $table->string('batch_no')->nullable()->after('product_id');
            $table->date('mfg_date')->nullable()->after('batch_no');
            $table->date('exp_date')->nullable()->after('mfg_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_return_items', function (Blueprint $table) {
            $table->dropColumn(['batch_no', 'mfg_date', 'exp_date']);
        });
    }
};
