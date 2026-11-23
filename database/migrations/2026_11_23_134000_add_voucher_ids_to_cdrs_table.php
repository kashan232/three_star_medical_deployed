<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cdrs')) {
            Schema::table('cdrs', function (Blueprint $table) {
                if (!Schema::hasColumn('cdrs', 'voucher_id')) {
                    $table->unsignedBigInteger('voucher_id')->nullable()->after('account_id');
                }
                if (!Schema::hasColumn('cdrs', 'clear_voucher_id')) {
                    $table->unsignedBigInteger('clear_voucher_id')->nullable()->after('voucher_id');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('cdrs')) {
            Schema::table('cdrs', function (Blueprint $table) {
                if (Schema::hasColumn('cdrs', 'clear_voucher_id')) {
                    $table->dropColumn('clear_voucher_id');
                }
                if (Schema::hasColumn('cdrs', 'voucher_id')) {
                    $table->dropColumn('voucher_id');
                }
            });
        }
    }
};
