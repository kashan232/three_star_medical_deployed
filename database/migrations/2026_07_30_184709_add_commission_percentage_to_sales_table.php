<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds commission_percentage column to the sales table.
     * Commission Calculation Formula (International Standard):
     *   Tax-Exclusive Base = total_net - total_gst (18%) - total_adv_tax (5%)
     *   Commission Amount  = Tax-Exclusive Base × (commission_percentage / 100)
     *
     * Trigger: Commission is earned ONLY when customer makes FULL payment
     * against a posted invoice (SIN- prefix, sale_status = 'post').
     * DC-only notes (without invoice) do NOT generate commission.
     */
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            if (!Schema::hasColumn('sales', 'commission_percentage')) {
                $table->decimal('commission_percentage', 5, 2)
                      ->nullable()
                      ->default(null)
                      ->after('total_commission')
                      ->comment('Manually entered commission % per sale note. Applied on tax-exclusive base amount.');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            if (Schema::hasColumn('sales', 'commission_percentage')) {
                $table->dropColumn('commission_percentage');
            }
        });
    }
};
