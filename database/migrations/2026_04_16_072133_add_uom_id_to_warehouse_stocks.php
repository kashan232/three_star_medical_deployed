<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warehouse_stocks', function (Blueprint $table) {
            $table->unsignedBigInteger('uom_id')->nullable()->after('product_id')
                  ->comment('NULL = base/unclassified unit; FK to product_uoms');
            $table->foreign('uom_id')->references('id')->on('product_uoms')->onDelete('set null');
        });

        // Drop old unique key if any (warehouse_id + product_id only)
        // and add new unique key (warehouse_id + product_id + uom_id)
        // We use a try/catch style via raw SQL as Laravel can't drop unknown index names portably
        try {
            DB::statement('ALTER TABLE warehouse_stocks DROP INDEX warehouse_stocks_warehouse_id_product_id_unique');
        } catch (\Exception $e) {
            // Index may not exist under that name — that's fine
        }

        // Add the new composite unique index (NULL-safe via UNIQUE in MySQL treats each NULL as distinct,
        // so two rows with same warehouse/product but uom_id=NULL are also unique — this is correct)
        DB::statement('ALTER TABLE warehouse_stocks ADD UNIQUE KEY uq_wh_product_uom (warehouse_id, product_id, uom_id)');
    }

    public function down(): void
    {
        Schema::table('warehouse_stocks', function (Blueprint $table) {
            $table->dropForeign(['uom_id']);
            $table->dropColumn('uom_id');
        });

        try {
            DB::statement('ALTER TABLE warehouse_stocks DROP INDEX uq_wh_product_uom');
        } catch (\Exception $e) {}
    }
};
