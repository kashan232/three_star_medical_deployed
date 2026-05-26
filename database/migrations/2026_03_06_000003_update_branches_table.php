<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            // Remove the unique constraint so multiple users can belong to one branch
            $table->dropUnique(['user_id']);
            // Make user_id nullable (branch may not have a dedicated manager user)
            $table->unsignedBigInteger('user_id')->nullable()->change();
            // Add is_active flag
            $table->boolean('is_active')->default(true)->after('number');
            // Add branch code for prefix in invoice numbers
            $table->string('branch_code', 10)->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->unique('user_id');
            $table->dropColumn(['is_active', 'branch_code']);
        });
    }
};
