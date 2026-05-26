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
        Schema::table('vendors', function (Blueprint $table) {
            $table->string('gst_no')->after('ntn_no')->nullable();
            $table->string('dsl_no')->after('gst_no')->nullable();
            $table->string('drap_no')->after('dsl_no')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn(['gst_no', 'dsl_no', 'drap_no']);
        });
    }
};
