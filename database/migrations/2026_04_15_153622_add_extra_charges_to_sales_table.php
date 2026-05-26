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
            $table->decimal('total_freight', 15, 2)->default(0)->after('total_extradiscount');
            $table->decimal('total_expense', 15, 2)->default(0)->after('total_freight');
            $table->decimal('total_fixed_tax', 15, 2)->default(0)->after('total_expense');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['total_freight', 'total_expense', 'total_fixed_tax']);
        });
    }
};
