<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_item_batches', function (Blueprint $table) {
            $table->foreignId('delivery_note_item_id')->nullable()->constrained('delivery_note_items')->onDelete('cascade')->after('sale_item_id');
        });
    }

    public function down(): void
    {
        Schema::table('sale_item_batches', function (Blueprint $table) {
            $table->dropConstrainedForeignId('delivery_note_item_id');
        });
    }
};
