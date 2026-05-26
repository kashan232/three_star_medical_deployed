<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            if (!Schema::hasColumn('purchases', 'wht_amount')) {
                $table->decimal('wht_amount', 15, 2)->default(0)->after('total_gst');
            }
            if (!Schema::hasColumn('purchases', 'adv_tax_amount')) {
                $table->decimal('adv_tax_amount', 15, 2)->default(0)->after('wht_amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn(['wht_amount', 'adv_tax_amount']);
        });
    }
};
