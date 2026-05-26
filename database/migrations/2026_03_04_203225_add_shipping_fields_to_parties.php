<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('shipping_address')->nullable();
            $table->string('shipping_city')->nullable();
            $table->string('shipping_country')->nullable();
            $table->string('shipping_phone')->nullable();
            $table->string('shipping_fax')->nullable();
            $table->string('shipping_email')->nullable();
        });

        Schema::table('vendors', function (Blueprint $table) {
            $table->string('shipping_address')->nullable();
            $table->string('shipping_city')->nullable();
            $table->string('shipping_country')->nullable();
            $table->string('shipping_phone')->nullable();
            $table->string('shipping_fax')->nullable();
            $table->string('shipping_email')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['shipping_address', 'shipping_city', 'shipping_country', 'shipping_phone', 'shipping_fax', 'shipping_email']);
        });

        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn(['shipping_address', 'shipping_city', 'shipping_country', 'shipping_phone', 'shipping_fax', 'shipping_email']);
        });
    }
};
