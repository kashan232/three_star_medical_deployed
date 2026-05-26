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
        Schema::table('delivery_note_items', function (Blueprint $table) {
            $table->unsignedBigInteger('batch_id')->nullable()->after('product_id');
            $table->string('lot_number')->nullable()->after('batch_id');
            $table->date('mfg_date')->nullable()->after('lot_number');
            $table->date('exp_date')->nullable()->after('mfg_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delivery_note_items', function (Blueprint $table) {
            $table->dropColumn(['batch_id', 'lot_number', 'mfg_date', 'exp_date']);
        });
    }
};
