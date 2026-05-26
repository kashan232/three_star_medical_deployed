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
            $table->decimal('debit', 15, 2)->default(0)->after('vendor_id');
            $table->decimal('credit', 15, 2)->default(0)->after('debit');
            $table->string('description')->nullable()->after('credit');
            
            // Link to source transaction (Purchase, VoucherMaster, etc.)
            $table->nullableMorphs('source'); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendor_ledgers', function (Blueprint $table) {
            $table->dropColumn(['debit', 'credit', 'description']);
            $table->dropMorphs('source');
        });
    }
};
