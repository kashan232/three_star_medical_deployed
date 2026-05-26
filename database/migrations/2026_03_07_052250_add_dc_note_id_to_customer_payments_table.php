<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_payments', function (Blueprint $table) {
            $table->unsignedBigInteger('dc_note_id')->nullable()->after('sale_id');
            $table->unsignedBigInteger('account_id')->nullable()->after('payment_date');
            $table->string('description')->nullable()->after('account_id');
        });
    }

    public function down(): void
    {
        Schema::table('customer_payments', function (Blueprint $table) {
            $table->dropColumn(['dc_note_id', 'account_id', 'description']);
        });
    }
};
