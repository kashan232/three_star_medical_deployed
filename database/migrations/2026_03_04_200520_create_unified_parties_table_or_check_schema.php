<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Adding fields to customers table
        Schema::table('customers', function (Blueprint $table) {
            if (! Schema::hasColumn('customers', 'party_type')) {
                $table->string('party_type')->default('Customer');
            }
            if (! Schema::hasColumn('customers', 'is_active')) {
                $table->boolean('is_active')->default(true);
            }
            if (! Schema::hasColumn('customers', 'abr')) {
                $table->string('abr')->nullable();
            }
            if (! Schema::hasColumn('customers', 'title')) {
                $table->string('title')->nullable();
            }
            if (! Schema::hasColumn('customers', 'url')) {
                $table->string('url')->nullable();
            }
            if (! Schema::hasColumn('customers', 'ntn_no')) {
                $table->string('ntn_no')->nullable();
            }
            if (! Schema::hasColumn('customers', 'dsl_no')) {
                $table->string('dsl_no')->nullable();
            }
            if (! Schema::hasColumn('customers', 'ftn_no')) {
                $table->string('ftn_no')->nullable();
            }
            if (! Schema::hasColumn('customers', 'city')) {
                $table->string('city')->nullable();
            }
            if (! Schema::hasColumn('customers', 'country')) {
                $table->string('country')->nullable();
            }
            if (! Schema::hasColumn('customers', 'fax')) {
                $table->string('fax')->nullable();
            }
            if (! Schema::hasColumn('customers', 'credit_terms')) {
                $table->string('credit_terms')->nullable();
            }
            if (! Schema::hasColumn('customers', 'payment_mode')) {
                $table->string('payment_mode')->nullable();
            }
            if (! Schema::hasColumn('customers', 'category')) {
                $table->string('category')->nullable();
            }
            if (! Schema::hasColumn('customers', 'credit_status')) {
                $table->string('credit_status')->nullable();
            }
            if (! Schema::hasColumn('customers', 'loyalty_group')) {
                $table->string('loyalty_group')->nullable();
            }
            if (! Schema::hasColumn('customers', 'default_price')) {
                $table->string('default_price')->nullable();
            }
            if (! Schema::hasColumn('customers', 'v1_mc')) {
                $table->decimal('v1_mc', 8, 2)->nullable();
            }
            if (! Schema::hasColumn('customers', 'v2_mc')) {
                $table->decimal('v2_mc', 8, 2)->nullable();
            }
            if (! Schema::hasColumn('customers', 'van_no')) {
                $table->string('van_no')->nullable();
            }
            if (! Schema::hasColumn('customers', 'cng')) {
                $table->string('cng')->nullable();
            }
            if (! Schema::hasColumn('customers', 'card_expiry')) {
                $table->date('card_expiry')->nullable();
            }
            if (! Schema::hasColumn('customers', 'contact_person_designation')) {
                $table->string('contact_person_designation')->nullable();
            }
            if (! Schema::hasColumn('customers', 'contact_person_whatsapp')) {
                $table->string('contact_person_whatsapp')->nullable();
            }
            if (! Schema::hasColumn('customers', 'contact_person_2_designation')) {
                $table->string('contact_person_2_designation')->nullable();
            }
            if (! Schema::hasColumn('customers', 'contact_person_2_whatsapp')) {
                $table->string('contact_person_2_whatsapp')->nullable();
            }
        });

        // Adding fields to vendors table
        Schema::table('vendors', function (Blueprint $table) {
            if (! Schema::hasColumn('vendors', 'vendor_code')) {
                $table->string('vendor_code')->nullable();
            }
            if (! Schema::hasColumn('vendors', 'party_type')) {
                $table->string('party_type')->default('Vendor');
            }
            if (! Schema::hasColumn('vendors', 'is_active')) {
                $table->boolean('is_active')->default(true);
            }
            if (! Schema::hasColumn('vendors', 'title')) {
                $table->string('title')->nullable();
            }
            if (! Schema::hasColumn('vendors', 'business_name')) {
                $table->string('business_name')->nullable();
            }
            if (! Schema::hasColumn('vendors', 'ntn_no')) {
                $table->string('ntn_no')->nullable();
            }
            if (! Schema::hasColumn('vendors', 'cnic')) {
                $table->string('cnic')->nullable();
            }
            if (! Schema::hasColumn('vendors', 'url')) {
                $table->string('url')->nullable();
            }
            if (! Schema::hasColumn('vendors', 'credit_terms')) {
                $table->string('credit_terms')->nullable();
            }
            if (! Schema::hasColumn('vendors', 'payment_mode')) {
                $table->string('payment_mode')->nullable();
            }
            if (! Schema::hasColumn('vendors', 'credit_limit')) {
                $table->decimal('credit_limit', 15, 2)->nullable();
            }
            if (! Schema::hasColumn('vendors', 'commission_percent')) {
                $table->decimal('commission_percent', 8, 2)->nullable();
            }
            if (! Schema::hasColumn('vendors', 'wh_tax')) {
                $table->decimal('wh_tax', 8, 2)->nullable();
            }
            if (! Schema::hasColumn('vendors', 'margin_percent')) {
                $table->decimal('margin_percent', 8, 2)->nullable();
            }
            if (! Schema::hasColumn('vendors', 'city')) {
                $table->string('city')->nullable();
            }
            if (! Schema::hasColumn('vendors', 'country')) {
                $table->string('country')->nullable();
            }
            if (! Schema::hasColumn('vendors', 'fax')) {
                $table->string('fax')->nullable();
            }
            if (! Schema::hasColumn('vendors', 'contact_person')) {
                $table->string('contact_person')->nullable();
            }
            if (! Schema::hasColumn('vendors', 'contact_person_designation')) {
                $table->string('contact_person_designation')->nullable();
            }
            if (! Schema::hasColumn('vendors', 'contact_person_mobile')) {
                $table->string('contact_person_mobile')->nullable();
            }
            if (! Schema::hasColumn('vendors', 'contact_person_whatsapp')) {
                $table->string('contact_person_whatsapp')->nullable();
            }
            if (! Schema::hasColumn('vendors', 'contact_person_2')) {
                $table->string('contact_person_2')->nullable();
            }
            if (! Schema::hasColumn('vendors', 'contact_person_2_designation')) {
                $table->string('contact_person_2_designation')->nullable();
            }
            if (! Schema::hasColumn('vendors', 'contact_person_2_mobile')) {
                $table->string('contact_person_2_mobile')->nullable();
            }
            if (! Schema::hasColumn('vendors', 'contact_person_2_whatsapp')) {
                $table->string('contact_person_2_whatsapp')->nullable();
            }
        });
    }

    public function down(): void
    {
        //
    }
};
