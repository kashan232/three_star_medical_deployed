<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_payments', function (Blueprint $table) {
            // Link payment to a specific sale
            $table->unsignedBigInteger('sale_id')->nullable()->after('customer_id');
            $table->foreign('sale_id')->references('id')->on('sales')->onDelete('set null');
            // Track how much commission was triggered by this payment
            $table->decimal('commission_triggered', 10, 2)->default(0)->after('sale_id');
        });
    }

    public function down(): void
    {
        Schema::table('customer_payments', function (Blueprint $table) {
            $table->dropForeign(['sale_id']);
            $table->dropColumn(['sale_id', 'commission_triggered']);
        });
    }
};
