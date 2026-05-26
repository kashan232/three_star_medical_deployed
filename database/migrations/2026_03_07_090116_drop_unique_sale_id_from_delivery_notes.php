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
        Schema::table('delivery_notes', function (Blueprint $table) {
            $table->dropForeign('delivery_notes_sale_id_foreign');
            $table->dropUnique('delivery_notes_sale_id_unique');
            $table->foreign('sale_id')->references('id')->on('sales')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delivery_notes', function (Blueprint $table) {
            $table->dropForeign('delivery_notes_sale_id_foreign');
            $table->unique('sale_id');
            $table->foreign('sale_id')->references('id')->on('sales')->cascadeOnDelete();
        });
    }
};
