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
        Schema::table('purchase_items', function (Blueprint $table) {
            // raw free qty value (same decimal notation as qty, e.g. 2.1 = 2 boxes + 1 piece)
            $table->decimal('free_qty', 12, 3)->default(0)->after('qty');
            // resolved total free pieces (computed on save)
            $table->decimal('free_qty_pieces', 12, 3)->default(0)->after('free_qty');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_items', function (Blueprint $table) {
            $table->dropColumn(['free_qty', 'free_qty_pieces']);
        });
    }
};
