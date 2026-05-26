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
            $table->decimal('total_inc_tax', 15, 2)->default(0);
            $table->decimal('total_adv_tax', 15, 2)->default(0);
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->decimal('inc_tax', 15, 2)->default(0);
            $table->decimal('adv_tax', 15, 2)->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['total_inc_tax', 'total_adv_tax']);
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn(['inc_tax', 'adv_tax']);
        });
    }
};
