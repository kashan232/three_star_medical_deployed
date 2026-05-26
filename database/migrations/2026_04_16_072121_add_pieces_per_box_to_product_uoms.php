<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_uoms', function (Blueprint $table) {
            $table->unsignedInteger('pieces_per_box')->default(1)->after('name')
                  ->comment('How many base pieces fit in 1 unit of this UOM');
        });

        // Backfill: try to pull pieces_per_box from purchase_items.uom_factor
        // where the uom_id matches and uom_factor > 1
        DB::statement("
            UPDATE product_uoms pu
            INNER JOIN (
                SELECT uom_id, MAX(uom_factor) AS max_factor
                FROM purchase_items
                WHERE uom_id IS NOT NULL AND uom_factor > 1
                GROUP BY uom_id
            ) pi ON pi.uom_id = pu.id
            SET pu.pieces_per_box = pi.max_factor
        ");
    }

    public function down(): void
    {
        Schema::table('product_uoms', function (Blueprint $table) {
            $table->dropColumn('pieces_per_box');
        });
    }
};
