<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SystemSetting;

class FbrSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['key' => 'fbr_enabled', 'value' => '1', 'type' => 'boolean', 'group' => 'fbr', 'label' => 'Enable FBR Digital Invoicing', 'description' => 'Toggle FBR integration on or off'],
            ['key' => 'fbr_environment', 'value' => 'sandbox', 'type' => 'string', 'group' => 'fbr', 'label' => 'FBR Environment', 'description' => 'Current active mode: sandbox or production'],
            ['key' => 'fbr_sandbox_url', 'value' => 'https://gw.fbr.gov.pk/di_data/v1/di/postinvoicedata_sb', 'type' => 'string', 'group' => 'fbr', 'label' => 'Sandbox Post URL', 'description' => 'Endpoint to submit invoices in sandbox'],
            ['key' => 'fbr_sandbox_validate_url', 'value' => 'https://gw.fbr.gov.pk/di_data/v1/di/validateinvoicedata_sb', 'type' => 'string', 'group' => 'fbr', 'label' => 'Sandbox Validate URL', 'description' => 'Endpoint to validate invoice data in sandbox'],
            ['key' => 'fbr_sandbox_token', 'value' => 'c2bb2ccd-c57d-3f97-9b8b-0fbc84d598d8', 'type' => 'string', 'group' => 'fbr', 'label' => 'Sandbox Bearer Token', 'description' => 'Security token for sandbox API'],
            ['key' => 'fbr_production_url', 'value' => 'https://gw.fbr.gov.pk/di_data/v1/di/postinvoicedata', 'type' => 'string', 'group' => 'fbr', 'label' => 'Production Post URL', 'description' => 'Endpoint to submit invoices in production'],
            ['key' => 'fbr_production_validate_url', 'value' => 'https://gw.fbr.gov.pk/di_data/v1/di/validateinvoicedata', 'type' => 'string', 'group' => 'fbr', 'label' => 'Production Validate URL', 'description' => 'Endpoint to validate invoice data in production'],
            ['key' => 'fbr_production_token', 'value' => '', 'type' => 'string', 'group' => 'fbr', 'label' => 'Production Bearer Token', 'description' => 'Security token for production API'],
            ['key' => 'fbr_seller_ntn', 'value' => '3520224331243', 'type' => 'string', 'group' => 'fbr', 'label' => 'Seller NTN / CNIC', 'description' => 'Registered NTN or CNIC of the seller'],
            ['key' => 'fbr_seller_name', 'value' => 'THREE STARS MEDICAL SUPPLIES', 'type' => 'string', 'group' => 'fbr', 'label' => 'Seller Business Name', 'description' => 'Registered business name of the seller'],
            ['key' => 'fbr_seller_province', 'value' => 'Punjab', 'type' => 'string', 'group' => 'fbr', 'label' => 'Seller Province', 'description' => 'Province of the business location'],
            ['key' => 'fbr_seller_address', 'value' => 'M17-M18 SAITH CENTRE SYED MOJ DARYA ROAD LAHORE', 'type' => 'string', 'group' => 'fbr', 'label' => 'Seller Address', 'description' => 'Registered business address'],
            ['key' => 'fbr_default_scenario', 'value' => 'SN001', 'type' => 'string', 'group' => 'fbr', 'label' => 'Default Scenario ID', 'description' => 'Default scenario code (SN001: Standard to Registered, SN002: Unregistered, SN026: Retail)'],
            ['key' => 'fbr_default_hs_code', 'value' => '9018.9090', 'type' => 'string', 'group' => 'fbr', 'label' => 'Default HS Code', 'description' => 'Default HS Code for items without specific HS code'],
            ['key' => 'fbr_default_uom', 'value' => 'Numbers, pieces, units', 'type' => 'string', 'group' => 'fbr', 'label' => 'Default Unit of Measure', 'description' => 'FBR standardized unit of measure']
        ];

        foreach ($settings as $s) {
            SystemSetting::updateOrCreate(['key' => $s['key']], $s);
        }
    }
}
