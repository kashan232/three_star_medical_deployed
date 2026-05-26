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
        Schema::table('customers', function (Blueprint $table) {
            $table->string('bank_name')->nullable()->after('payment_mode');
            $table->string('cheque_no')->nullable()->after('bank_name');
            $table->date('cheque_date')->nullable()->after('cheque_no');
        });

        Schema::table('vendors', function (Blueprint $table) {
            $table->string('bank_name')->nullable()->after('payment_mode');
            $table->string('cheque_no')->nullable()->after('bank_name');
            $table->date('cheque_date')->nullable()->after('cheque_no');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['bank_name', 'cheque_no', 'cheque_date']);
        });

        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn(['bank_name', 'cheque_no', 'cheque_date']);
        });
    }
};
