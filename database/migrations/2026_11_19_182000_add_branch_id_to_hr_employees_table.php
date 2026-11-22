<?php

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
        if (Schema::hasTable('hr_employees')) {
            Schema::table('hr_employees', function (Blueprint $table) {
                if (! Schema::hasColumn('hr_employees', 'branch_id')) {
                    $table->foreignId('branch_id')->nullable()->after('user_id')->constrained('branches')->nullOnDelete();
                }
            });

            // Populate branch_id from users table for existing employees
            DB::statement("
                UPDATE hr_employees e
                INNER JOIN users u ON e.user_id = u.id
                SET e.branch_id = u.branch_id
                WHERE e.branch_id IS NULL AND u.branch_id IS NOT NULL
            ");

            // Specifically link Employee 8 (Tahir Awan) with User 7 (Tahir Awan, branch 1) if not linked
            DB::statement("
                UPDATE hr_employees
                SET user_id = 7, branch_id = 1
                WHERE id = 8 AND user_id IS NULL
            ");

            // Fallback: If any employee still has no branch_id, assign branch 1 (or first active branch)
            $firstBranchId = DB::table('branches')->where('is_active', 1)->value('id') ?? 1;
            DB::statement("
                UPDATE hr_employees
                SET branch_id = {$firstBranchId}
                WHERE branch_id IS NULL
            ");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('hr_employees')) {
            Schema::table('hr_employees', function (Blueprint $table) {
                if (Schema::hasColumn('hr_employees', 'branch_id')) {
                    $table->dropForeign(['branch_id']);
                    $table->dropColumn('branch_id');
                }
            });
        }
    }
};
