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
        if (Schema::hasTable('voucher_details')) {
            Schema::table('voucher_details', function (Blueprint $table) {
                if (!Schema::hasColumn('voucher_details', 'party_type')) {
                    $table->string('party_type')->nullable()->after('account_id');
                }
                if (!Schema::hasColumn('voucher_details', 'party_id')) {
                    $table->unsignedBigInteger('party_id')->nullable()->after('party_type');
                }
                if (!Schema::hasColumn('voucher_details', 'reference_no')) {
                    $table->string('reference_no')->nullable()->after('narration');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('voucher_details')) {
            Schema::table('voucher_details', function (Blueprint $table) {
                if (Schema::hasColumn('voucher_details', 'party_id')) {
                    $table->dropColumn('party_id');
                }
                if (Schema::hasColumn('voucher_details', 'party_type')) {
                    $table->dropColumn('party_type');
                }
                if (Schema::hasColumn('voucher_details', 'reference_no')) {
                    $table->dropColumn('reference_no');
                }
            });
        }
    }
};
