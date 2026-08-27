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
        DB::statement("ALTER TABLE `product_batches` MODIFY `exp_date` DATE NULL");
        DB::statement("ALTER TABLE `product_batches` MODIFY `batch_number` VARCHAR(255) NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE `product_batches` MODIFY `exp_date` DATE NOT NULL");
        DB::statement("ALTER TABLE `product_batches` MODIFY `batch_number` VARCHAR(255) NOT NULL");
    }
};
