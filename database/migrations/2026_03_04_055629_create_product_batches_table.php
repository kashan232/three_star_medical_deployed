<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('warehouse_id')->constrained('warehouses')->onDelete('cascade');
            $table->foreignId('purchase_item_id')->nullable()->constrained('purchase_items')->onDelete('set null');

            $table->string('batch_number');
            $table->date('mfg_date')->nullable();
            $table->date('exp_date');

            $table->decimal('qty_received', 12, 2)->default(0);
            $table->decimal('qty_remaining', 12, 2)->default(0);

            $table->enum('source_type', ['purchase', 'opening_stock', 'adjustment'])->default('purchase');
            $table->enum('status', ['active', 'expired', 'consumed'])->default('active');

            $table->timestamps();

            $table->index(['product_id', 'warehouse_id', 'exp_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_batches');
    }
};
