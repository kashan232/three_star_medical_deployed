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
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_fridge')->default(false);
            $table->boolean('is_non_fridge')->default(false);
            $table->boolean('is_fast_moving')->default(false);
            $table->boolean('is_slow_moving')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['is_fridge', 'is_non_fridge', 'is_fast_moving', 'is_slow_moving']);
        });
    }
};
