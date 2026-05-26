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
        Schema::create('delivery_return_notes', function (Blueprint $table) {
            $table->id();
            $table->string('return_no')->unique();
            $table->unsignedBigInteger('delivery_note_id')->nullable();
            $table->unsignedBigInteger('sale_id')->nullable(); // SO number
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('branch_id')->default(1);
            $table->unsignedBigInteger('warehouse_id')->nullable();
            $table->date('return_date');
            $table->decimal('bill_amount', 15, 2)->default(0);
            $table->decimal('net_amount', 15, 2)->default(0);
            $table->text('remarks')->nullable();
            $table->string('status')->default('posted');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_return_notes');
    }
};
