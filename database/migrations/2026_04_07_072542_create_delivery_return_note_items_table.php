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
        Schema::create('delivery_return_note_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('delivery_return_note_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('delivery_note_item_id')->nullable();
            $table->string('qty')->nullable(); // stored as 'boxes.loose'
            $table->decimal('total_pieces', 15, 2)->default(0);
            $table->decimal('price', 15, 2)->default(0);
            $table->decimal('line_total', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_return_note_items');
    }
};
