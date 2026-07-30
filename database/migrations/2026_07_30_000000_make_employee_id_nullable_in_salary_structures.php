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
        if (Schema::hasTable('hr_salary_structures')) {
            Schema::table('hr_salary_structures', function (Blueprint $table) {
                // Drop foreign key if exists
                try {
                    $table->dropForeign(['employee_id']);
                } catch (\Exception $e) {}

                // Make employee_id nullable
                $table->unsignedBigInteger('employee_id')->nullable()->change();

                // Re-add foreign key
                try {
                    $table->foreign('employee_id')
                        ->references('id')
                        ->on('hr_employees')
                        ->cascadeOnDelete();
                } catch (\Exception $e) {}
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('hr_salary_structures')) {
            Schema::table('hr_salary_structures', function (Blueprint $table) {
                $table->unsignedBigInteger('employee_id')->nullable(false)->change();
            });
        }
    }
};
