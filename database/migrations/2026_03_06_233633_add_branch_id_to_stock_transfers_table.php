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
        if (!Schema::hasColumn('stock_transfers', 'branch_id')) {
            Schema::table('stock_transfers', function (Blueprint $table) {
                $table->unsignedBigInteger('branch_id')->nullable()->default(1)->after('id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('stock_transfers', 'branch_id')) {
            Schema::table('stock_transfers', function (Blueprint $table) {
                $table->dropColumn('branch_id');
            });
        }
    }
};
