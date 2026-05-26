<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->unsignedBigInteger('uom_id')->nullable()->after('product_id')
                  ->comment('FK to product_uoms; NULL = base/piece unit');
            $table->foreign('uom_id')->references('id')->on('product_uoms')->onDelete('set null');
        });

        // Backfill: match existing uom_name + product_id against product_uoms
        DB::statement("
            UPDATE sale_items si
            INNER JOIN product_uoms pu
                ON pu.product_id = si.product_id
               AND pu.name = si.uom_name
            SET si.uom_id = pu.id
            WHERE si.uom_id IS NULL
              AND si.uom_name IS NOT NULL
              AND si.uom_name != ''
              AND si.uom_name != 'Piece'
        ");
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropForeign(['uom_id']);
            $table->dropColumn('uom_id');
        });
    }
};
