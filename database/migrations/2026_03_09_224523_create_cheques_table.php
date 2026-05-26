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
        Schema::create('cheques', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('voucher_master_id')->comment('The receipt voucher that created this cheque');
            $table->string('cheque_no');
            $table->date('cheque_date');
            $table->string('bank_name')->nullable();
            $table->enum('status', ['pending', 'cleared', 'bounced'])->default('pending');
            $table->decimal('amount', 15, 2);
            $table->timestamp('cleared_at')->nullable();
            $table->timestamp('bounced_at')->nullable();
            $table->unsignedBigInteger('actual_account_id')->comment('The targeted bank account where it should eventually clear');
            $table->timestamps();

            // Setup foreign keys if strict mode is on, though many projects skip it.
            // $table->foreign('voucher_master_id')->references('id')->on('voucher_masters')->onDelete('cascade');
        });

        // Ensure "Cheque In Hand" account exists
        $this->ensureChequeInHandAccount();
    }

    private function ensureChequeInHandAccount()
    {
        // Check if Current Assets head exists
        $currentAssetsHead = \Illuminate\Support\Facades\DB::table('account_heads')
            ->where('name', 'Current Assets')
            ->first();

        if ($currentAssetsHead) {
            // Check if Cheque in Hand sub head exists
            $chequeHead = \Illuminate\Support\Facades\DB::table('account_heads')
                ->where('name', 'Cheque In Hand')
                ->where('parent_id', $currentAssetsHead->id)
                ->first();

            if (!$chequeHead) {
                $headId = \Illuminate\Support\Facades\DB::table('account_heads')->insertGetId([
                    'name' => 'Cheque In Hand',
                    'parent_id' => $currentAssetsHead->id,
                    'type' => 'asset',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                $headId = $chequeHead->id;
            }

            // check if account exists
            $account = \Illuminate\Support\Facades\DB::table('accounts')
                ->where('title', 'Cheque In Hand')
                ->first();

            if (!$account) {
                // Get next account code based on branch (assuming branch 1 for default)
                $lastAccount = \Illuminate\Support\Facades\DB::table('accounts')
                    ->where('account_head_id', $headId)
                    ->orderBy('id', 'desc')
                    ->first();
                
                $code = $lastAccount ? intval(substr($lastAccount->account_code, -4)) + 1 : 1;
                $newCode = '1112-' . str_pad($code, 4, '0', STR_PAD_LEFT); // dummy generic code
                
                \Illuminate\Support\Facades\DB::table('accounts')->insert([
                    'title' => 'Cheque In Hand',
                    'account_head_id' => $headId,
                    'account_code' => $newCode,
                    'opening_balance' => 0.00,
                    'status' => 'active',
                    'branch_id' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cheques');
    }
};
