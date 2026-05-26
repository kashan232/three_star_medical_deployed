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
        Schema::table('vendor_ledgers', function (Blueprint $table) {
            $table->decimal('opening_balance', 15, 2)->default(0)->nullable()->change();
            $table->decimal('previous_balance', 15, 2)->default(0)->change();
            $table->decimal('closing_balance', 15, 2)->default(0)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendor_ledgers', function (Blueprint $table) {
            $table->text('opening_balance')->nullable(false)->change();
            $table->text('previous_balance')->nullable(false)->change();
            $table->text('closing_balance')->nullable(false)->change();
        });
    }
};
