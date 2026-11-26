<?php

use App\Services\BalanceService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('cheques')) {
            return;
        }

        Schema::table('cheques', function (Blueprint $table) {
            if (! Schema::hasColumn('cheques', 'type')) {
                $table->enum('type', ['received', 'issued'])->default('received')->after('id');
            }
            if (! Schema::hasColumn('cheques', 'branch_id')) {
                $table->unsignedBigInteger('branch_id')->nullable()->after('voucher_master_id');
            }
            if (! Schema::hasColumn('cheques', 'party_type')) {
                $table->string('party_type')->nullable()->after('branch_id');
            }
            if (! Schema::hasColumn('cheques', 'party_id')) {
                $table->unsignedBigInteger('party_id')->nullable()->after('party_type');
            }
            if (! Schema::hasColumn('cheques', 'issue_date')) {
                $table->date('issue_date')->nullable()->after('cheque_no');
            }
            if (! Schema::hasColumn('cheques', 'deposited_at')) {
                $table->timestamp('deposited_at')->nullable()->after('cheque_date');
            }
            if (! Schema::hasColumn('cheques', 'clearance_date')) {
                $table->date('clearance_date')->nullable()->after('cleared_at');
            }
            if (! Schema::hasColumn('cheques', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable()->after('bounced_at');
            }
            if (! Schema::hasColumn('cheques', 'clearing_account_id')) {
                $table->unsignedBigInteger('clearing_account_id')->nullable()->after('actual_account_id');
            }
            if (! Schema::hasColumn('cheques', 'clearance_voucher_id')) {
                $table->unsignedBigInteger('clearance_voucher_id')->nullable()->after('clearing_account_id');
            }
            if (! Schema::hasColumn('cheques', 'bounce_voucher_id')) {
                $table->unsignedBigInteger('bounce_voucher_id')->nullable()->after('clearance_voucher_id');
            }
            if (! Schema::hasColumn('cheques', 'bounce_reason')) {
                $table->string('bounce_reason')->nullable()->after('bounce_voucher_id');
            }
            if (! Schema::hasColumn('cheques', 'remarks')) {
                $table->text('remarks')->nullable()->after('bounce_reason');
            }
            if (! Schema::hasColumn('cheques', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('remarks');
            }
            if (! Schema::hasColumn('cheques', 'cleared_by')) {
                $table->unsignedBigInteger('cleared_by')->nullable()->after('created_by');
            }
            if (! Schema::hasColumn('cheques', 'bounced_by')) {
                $table->unsignedBigInteger('bounced_by')->nullable()->after('cleared_by');
            }
        });

        // Expand status enum and allow actual_account_id to be nullable safely
        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE `cheques` MODIFY COLUMN `status` ENUM('pending','deposited','cleared','bounced','cancelled') NOT NULL DEFAULT 'pending'");
            DB::statement("ALTER TABLE `cheques` MODIFY COLUMN `actual_account_id` BIGINT UNSIGNED NULL");
        }

        // Add helpful indexes safely
        $indexes = [
            'cheques_branch_status_idx' => ['branch_id', 'status'],
            'cheques_type_status_idx'   => ['type', 'status'],
            'cheques_date_idx'          => ['cheque_date'],
            'cheques_no_idx'            => ['cheque_no'],
            'cheques_vm_idx'            => ['voucher_master_id'],
        ];

        $existingIndexes = collect(DB::select('SHOW INDEX FROM `cheques`'))->pluck('Key_name')->unique()->toArray();
        Schema::table('cheques', function (Blueprint $table) use ($indexes, $existingIndexes) {
            foreach ($indexes as $name => $cols) {
                if (! in_array($name, $existingIndexes)) {
                    $table->index($cols, $name);
                }
            }
        });

        // Ensure Cheque control accounts exist for all branches
        try {
            $balanceService = app(BalanceService::class);
            $branchIds = DB::table('branches')->pluck('id');
            if ($branchIds->isEmpty()) {
                $branchIds = collect([1]);
            }
            foreach ($branchIds as $bId) {
                $balanceService->getChequeInHandId((int) $bId);
                $balanceService->getChequeIssuedId((int) $bId);
            }
        } catch (\Throwable $e) {
            \Log::warning('Cheque control account provisioning warning: ' . $e->getMessage());
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('cheques')) {
            return;
        }

        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement("UPDATE `cheques` SET `status` = 'pending' WHERE `status` IN ('deposited')");
            DB::statement("UPDATE `cheques` SET `status` = 'bounced' WHERE `status` IN ('cancelled')");
            DB::statement("ALTER TABLE `cheques` MODIFY COLUMN `status` ENUM('pending','cleared','bounced') NOT NULL DEFAULT 'pending'");
        }

        $dropCols = [
            'type',
            'branch_id',
            'party_type',
            'party_id',
            'issue_date',
            'deposited_at',
            'clearance_date',
            'cancelled_at',
            'clearing_account_id',
            'clearance_voucher_id',
            'bounce_voucher_id',
            'bounce_reason',
            'remarks',
            'created_by',
            'cleared_by',
            'bounced_by',
        ];

        Schema::table('cheques', function (Blueprint $table) use ($dropCols) {
            foreach ($dropCols as $col) {
                if (Schema::hasColumn('cheques', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
