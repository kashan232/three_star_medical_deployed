<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_note_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dc_note_id')->constrained('delivery_notes')->cascadeOnDelete();
            $table->unsignedBigInteger('sale_item_id')->nullable(); // link back to original SO item
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('warehouse_id')->nullable();

            $table->decimal('qty', 12, 3)->default(0);           // qty in boxes/units (display)
            $table->decimal('total_pieces', 12, 3)->default(0);  // resolved pieces for stock
            $table->decimal('price', 12, 2)->default(0);         // price per piece
            $table->decimal('line_total', 12, 2)->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_note_items');
    }
};
