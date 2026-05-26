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
        Schema::create('cdrs', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable(); // Auto-incremental or specific identifier like "000" in image
            $table->string('cdr_no')->nullable();
            $table->date('cdr_date')->nullable();
            $table->string('fiscal_year')->nullable();

            // "Department" means customer according to user
            $table->foreignId('customer_id')->nullable()->constrained('customers')->onDelete('cascade');

            // "Bank" will bring bank account from account heads/accounts
            $table->foreignId('account_id')->nullable()->constrained('accounts')->onDelete('cascade');

            $table->decimal('percentage', 8, 2)->default(0);
            $table->decimal('amount', 30, 2)->default(0);
            $table->string('status', 50)->default('PENDING'); // PENDING, APPROVED, CLEARED
            $table->date('dated')->nullable(); // Specific second date in form

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cdrs');
    }
};
