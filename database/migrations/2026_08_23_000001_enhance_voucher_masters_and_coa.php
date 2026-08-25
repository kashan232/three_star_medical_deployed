<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. ENHANCE Voucher Masters
        if (Schema::hasTable('voucher_masters')) {
            Schema::table('voucher_masters', function (Blueprint $table) {
                if (!Schema::hasColumn('voucher_masters', 'cheque_no')) {
                    $table->string('cheque_no')->nullable()->after('voucher_no');
                }
                if (!Schema::hasColumn('voucher_masters', 'cheque_date')) {
                    $table->date('cheque_date')->nullable()->after('cheque_no');
                }
                if (!Schema::hasColumn('voucher_masters', 'location')) {
                    $table->string('location')->nullable()->default('HEAD OFFICE')->after('branch_id');
                }
                if (!Schema::hasColumn('voucher_masters', 'verified_by')) {
                    $table->unsignedBigInteger('verified_by')->nullable()->after('created_by');
                }
                if (!Schema::hasColumn('voucher_masters', 'verified_at')) {
                    $table->timestamp('verified_at')->nullable()->after('verified_by');
                }
                if (!Schema::hasColumn('voucher_masters', 'modified_by')) {
                    $table->unsignedBigInteger('modified_by')->nullable()->after('verified_at');
                }
            });

            // Modify voucher_type to string(50) if column exists to allow flexible types
            try {
                DB::statement("ALTER TABLE voucher_masters MODIFY COLUMN voucher_type VARCHAR(50) NOT NULL");
            } catch (\Exception $e) {
                // Ignore if already varchar
            }
        }

        // 2. ENHANCE Voucher Details
        if (Schema::hasTable('voucher_details')) {
            Schema::table('voucher_details', function (Blueprint $table) {
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
        if (Schema::hasTable('voucher_masters')) {
            Schema::table('voucher_masters', function (Blueprint $table) {
                $columns = ['cheque_no', 'cheque_date', 'location', 'verified_by', 'verified_at', 'modified_by'];
                foreach ($columns as $col) {
                    if (Schema::hasColumn('voucher_masters', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        if (Schema::hasTable('voucher_details')) {
            Schema::table('voucher_details', function (Blueprint $table) {
                if (Schema::hasColumn('voucher_details', 'reference_no')) {
                    $table->dropColumn('reference_no');
                }
            });
        }
    }
};
