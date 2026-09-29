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
        Schema::table('sales', function (Blueprint $table) {
            if (!Schema::hasColumn('sales', 'fbr_status')) {
                $table->string('fbr_status', 20)->default('unposted')->after('sale_status');
            }
            if (!Schema::hasColumn('sales', 'fbr_invoice_no')) {
                $table->string('fbr_invoice_no', 100)->nullable()->after('fbr_status');
            }
            if (!Schema::hasColumn('sales', 'fbr_scenario_id')) {
                $table->string('fbr_scenario_id', 20)->nullable()->after('fbr_invoice_no');
            }
            if (!Schema::hasColumn('sales', 'fbr_posted_at')) {
                $table->timestamp('fbr_posted_at')->nullable()->after('fbr_scenario_id');
            }
            if (!Schema::hasColumn('sales', 'fbr_environment')) {
                $table->string('fbr_environment', 20)->nullable()->after('fbr_posted_at');
            }
            if (!Schema::hasColumn('sales', 'fbr_qr_code')) {
                $table->text('fbr_qr_code')->nullable()->after('fbr_environment');
            }
            if (!Schema::hasColumn('sales', 'fbr_response')) {
                $table->longText('fbr_response')->nullable()->after('fbr_qr_code');
            }
        });

        if (!Schema::hasTable('fbr_invoice_logs')) {
            Schema::create('fbr_invoice_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('sale_id')->nullable()->index();
                $table->string('environment', 20)->default('sandbox');
                $table->string('action', 30)->default('post'); // post, validate
                $table->string('scenario_id', 20)->nullable();
                $table->longText('request_payload')->nullable();
                $table->longText('response_payload')->nullable();
                $table->string('status_code', 20)->nullable();
                $table->string('status', 30)->nullable();
                $table->string('fbr_invoice_no', 100)->nullable();
                $table->text('error_message')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn([
                'fbr_status',
                'fbr_invoice_no',
                'fbr_scenario_id',
                'fbr_posted_at',
                'fbr_environment',
                'fbr_qr_code',
                'fbr_response'
            ]);
        });

        Schema::dropIfExists('fbr_invoice_logs');
    }
};
