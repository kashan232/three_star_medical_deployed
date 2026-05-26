<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Add 'held' to the status enum
        DB::statement("ALTER TABLE product_batches MODIFY COLUMN status ENUM('active','expired','consumed','held') NOT NULL DEFAULT 'active'");
    }

    public function down()
    {
        // Revert enum back
        DB::statement("ALTER TABLE product_batches MODIFY COLUMN status ENUM('active','expired','consumed') NOT NULL DEFAULT 'active'");
    }
};
