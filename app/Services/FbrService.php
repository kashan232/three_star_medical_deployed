<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\Customer;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class FbrService
{
    /**
     * Ensure FBR columns exist on sales table and fbr_invoice_logs table exists.
     * Prevents Unknown column errors if migration hasn't been run yet on production/cPanel.
     */
    public static function ensureSchemaExists(): void
    {
        try {
            if (!Schema::hasColumn('sales', 'fbr_status')) {
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
                        $table->string('fbr_environment', 20)->default('sandbox')->after('fbr_posted_at');
                    }
                    if (!Schema::hasColumn('sales', 'fbr_qr_code')) {
                        $table->text('fbr_qr_code')->nullable()->after('fbr_environment');
                    }
                    if (!Schema::hasColumn('sales', 'fbr_response')) {
                        $table->longText('fbr_response')->nullable()->after('fbr_qr_code');
                    }
                });
            }

            if (!Schema::hasTable('fbr_invoice_logs')) {
                Schema::create('fbr_invoice_logs', function (Blueprint $table) {
                    $table->id();
                    $table->unsignedBigInteger('sale_id')->nullable()->index();
                    $table->string('environment', 20)->default('sandbox');
                    $table->string('action', 50)->default('post');
                    $table->string('scenario_id', 20)->nullable();
                    $table->longText('request_payload')->nullable();
                    $table->longText('response_payload')->nullable();
                    $table->string('status_code', 50)->nullable();
                    $table->string('status', 50)->nullable();
                    $table->string('fbr_invoice_no', 100)->nullable()->index();
                    $table->text('error_message')->nullable();
                    $table->unsignedBigInteger('created_by')->nullable();
                    $table->timestamps();
                });
            }
        } catch (\Throwable $e) {
            Log::error('FBR ensureSchemaExists Error: ' . $e->getMessage());
        }
    }
    /**
     * Get active FBR environment ('sandbox' or 'production')
     */
    public static function getEnvironment(): string
    {
        return SystemSetting::get('fbr_environment', 'sandbox');
    }

    /**
     * Check if FBR integration is enabled
     */
    public static function isEnabled(): bool
    {
        return (bool) SystemSetting::get('fbr_enabled', true);
    }

    /**
     * Get active API endpoint for posting invoice
     */
    public static function getPostUrl(): string
    {
        $env = self::getEnvironment();
        if ($env === 'production') {
            return SystemSetting::get('fbr_production_url', 'https://gw.fbr.gov.pk/di_data/v1/di/postinvoicedata');
        }
        return SystemSetting::get('fbr_sandbox_url', 'https://gw.fbr.gov.pk/di_data/v1/di/postinvoicedata_sb');
    }

    /**
     * Get active API endpoint for validating invoice
     */
    public static function getValidateUrl(): string
    {
        $env = self::getEnvironment();
        if ($env === 'production') {
            return SystemSetting::get('fbr_production_validate_url', 'https://gw.fbr.gov.pk/di_data/v1/di/validateinvoicedata');
        }
        return SystemSetting::get('fbr_sandbox_validate_url', 'https://gw.fbr.gov.pk/di_data/v1/di/validateinvoicedata_sb');
    }

    /**
     * Get active Bearer Token
     */
    public static function getToken(): string
    {
        $env = self::getEnvironment();
        if ($env === 'production') {
            return SystemSetting::get('fbr_production_token', '');
        }
        return SystemSetting::get('fbr_sandbox_token', 'c2bb2ccd-c57d-3f97-9b8b-0fbc84d598d8');
    }

    /**
     * Get seller details from settings or branch
     */
    public static function getSellerDetails(): array
    {
        return [
            'sellerNTNCNIC'      => preg_replace('/[^0-9]/', '', SystemSetting::get('fbr_seller_ntn', '3520224331243')),
            'sellerBusinessName' => SystemSetting::get('fbr_seller_name', 'THREE STARS MEDICAL SUPPLIES'),
            'sellerProvince'     => SystemSetting::get('fbr_seller_province', 'Punjab'),
            'sellerAddress'      => SystemSetting::get('fbr_seller_address', 'M17-M18 SAITH CENTRE SYED MOJ DARYA ROAD LAHORE'),
        ];
    }

    /**
     * Sanitize buyer NTN / CNIC according to FBR standards
     * - Pakistani NTN: 7 digits (if 8 digits, strip check-digit)
     * - Pakistani CNIC: 13 digits
     */
    public static function formatBuyerNtn(?string $rawNtn): string
    {
        if (empty($rawNtn)) return '';
        $clean = preg_replace('/[^0-9]/', '', $rawNtn);
        if (strlen($clean) === 8) {
            return substr($clean, 0, 7);
        }
        if (strlen($clean) === 7) {
            return $clean;
        }
        if (strlen($clean) >= 13) {
            return substr($clean, 0, 13);
        }
        return $clean;
    }

    /**
     * Build the standard FBR Digital Invoicing payload from a Sale model
     */
    public static function buildPayload(Sale $sale, ?string $overrideScenario = null): array
    {
        $sale->loadMissing(['customer_relation', 'items.product.brand']);
        $customer = $sale->customer_relation;
        $seller = self::getSellerDetails();

        // 1. Determine Buyer Information
        $rawNtn = $customer ? ($customer->ntn_no ?? $customer->cnic ?? '') : '';
        $cleanNtn = self::formatBuyerNtn($rawNtn);

        // Buyer Registration Type
        // If buyer has 7-digit NTN and is registered filer:
        $isRegistered = false;
        if (strlen($cleanNtn) === 7 && ($customer?->filer_type === 'Filer' || !empty($customer?->gst_no))) {
            $isRegistered = true;
        }

        $buyerRegType = $isRegistered ? 'Registered' : 'Unregistered';

        // For Unregistered, if no valid NTN/CNIC, use 7-digit NTN if available or CNIC or '0000000000000'
        $buyerNtnCnic = $cleanNtn;
        if (empty($buyerNtnCnic)) {
            $buyerNtnCnic = '0000000000000';
        }

        $buyerName = $customer ? ($customer->business_name ?: $customer->customer_name ?: 'Walk-in Customer') : 'Walk-in Customer';
        $buyerAddress = $customer && !empty($customer->address) ? trim($customer->address) : 'Lahore, Pakistan';
        $buyerProvince = $customer && !empty($customer->city) ? self::mapCityToProvince($customer->city) : $seller['sellerProvince'];

        // 2. Scenario Determination
        if (!empty($overrideScenario)) {
            $scenarioId = $overrideScenario;
        } else {
            // Default scenarios from user specification:
            // SN001: Standard rate to Registered
            // SN002: Standard rate to Unregistered
            $scenarioId = $isRegistered ? 'SN001' : 'SN002';
        }

        // 3. Invoice Header
        $invoiceDate = $sale->sale_date 
            ? date('Y-m-d', strtotime($sale->sale_date)) 
            : date('Y-m-d', strtotime($sale->created_at ?? now()));

        $defaultHsCode = SystemSetting::get('fbr_default_hs_code', '9018.9090');
        $defaultUom = SystemSetting::get('fbr_default_uom', 'Numbers, pieces, units');

        // 4. Transform Line Items
        $items = [];
        $descCounts = [];

        foreach ($sale->items as $index => $item) {
            $product = $item->product;

            // Format HS Code as XXXX.XXXX (e.g. 0101.2100 or 9018.9090)
            $rawHs = !empty($item->hs_code) ? $item->hs_code : (!empty($product?->hs_code) ? $product->hs_code : $defaultHsCode);
            $cleanHs = self::formatHsCode($rawHs, $defaultHsCode);

            $qty = (float)($item->total_pieces > 0 ? $item->total_pieces : ($item->qty > 0 ? $item->qty : 1));
            $unitPrice = (float)($item->price > 0 ? $item->price : 0);
            $lineSubtotal = round($qty * $unitPrice, 2);
            $disc = (float)($item->discount_amount ?? 0);
            $valExclSt = max(0, round($lineSubtotal - $disc, 2));

            $gstRateNum = (float)($item->gst_percent ?? 0);
            $gstAmt = (float)($item->gst_amount ?? round($valExclSt * ($gstRateNum / 100), 2));
            $totalVal = round($valExclSt + $gstAmt, 2);

            $rateStr = $gstRateNum > 0 ? (rtrim(rtrim(number_format($gstRateNum, 2), '0'), '.') . '%') : '0%';
            $saleType = $gstRateNum > 0 ? 'Goods at standard rate (default)' : 'Exempt goods';

            $baseDesc = trim(($item->product_name ?: $product?->item_name ?: 'Medical Item') . ' ' . ($product?->brand?->name ?? ''));
            // Ensure unique description per line to avoid FBR "DUPLICATE INVOICE EXISTS" error on multi-line same items
            $lineNum = $index + 1;
            $descKey = $cleanHs . '_' . $baseDesc;
            if (!isset($descCounts[$descKey])) {
                $descCounts[$descKey] = 1;
                $finalDesc = $baseDesc;
            } else {
                $descCounts[$descKey]++;
                $finalDesc = $baseDesc . ' - Line ' . $descCounts[$descKey];
            }

            $items[] = [
                'hsCode'                          => $cleanHs,
                'productDescription'             => $finalDesc,
                'rate'                            => $rateStr,
                'uoM'                             => $defaultUom,
                'quantity'                        => $qty,
                'totalValues'                     => $totalVal,
                'valueSalesExcludingST'           => $valExclSt,
                'fixedNotifiedValueOrRetailPrice' => 0.0,
                'salesTaxApplicable'              => $gstAmt,
                'salesTaxWithheldAtSource'        => 0.0,
                'extraTax'                        => '',
                'furtherTax'                      => 0.0,
                'sroScheduleNo'                   => '',
                'fedPayable'                      => 0.0,
                'discount'                        => $disc,
                'saleType'                        => $saleType,
                'sroItemSerialNo'                 => ''
            ];
        }

        // Fallback item if invoice has no lines
        if (empty($items)) {
            $items[] = [
                'hsCode'                          => $defaultHsCode,
                'productDescription'             => 'Invoice Items',
                'rate'                            => '18%',
                'uoM'                             => $defaultUom,
                'quantity'                        => 1,
                'totalValues'                     => (float)$sale->total_net,
                'valueSalesExcludingST'           => (float)$sale->total_bill_amount,
                'fixedNotifiedValueOrRetailPrice' => 0.0,
                'salesTaxApplicable'              => (float)$sale->total_gst,
                'salesTaxWithheldAtSource'        => 0.0,
                'extraTax'                        => '',
                'furtherTax'                      => 0.0,
                'sroScheduleNo'                   => '',
                'fedPayable'                      => 0.0,
                'discount'                        => (float)$sale->total_extradiscount,
                'saleType'                        => 'Goods at standard rate (default)',
                'sroItemSerialNo'                 => ''
            ];
        }

        return [
            'invoiceType'           => 'Sale Invoice',
            'invoiceDate'           => $invoiceDate,
            'sellerNTNCNIC'         => $seller['sellerNTNCNIC'],
            'sellerBusinessName'    => $seller['sellerBusinessName'],
            'sellerProvince'        => $seller['sellerProvince'],
            'sellerAddress'         => $seller['sellerAddress'],
            'buyerNTNCNIC'          => $buyerNtnCnic,
            'buyerBusinessName'     => $buyerName,
            'buyerProvince'         => $buyerProvince,
            'buyerAddress'          => $buyerAddress,
            'buyerRegistrationType' => $buyerRegType,
            'invoiceRefNo'          => '',
            'scenarioId'            => $scenarioId,
            'items'                 => $items
        ];
    }

    /**
     * Validate Invoice data with FBR without final posting
     */
    public static function validateInvoice(Sale $sale, ?string $overrideScenario = null): array
    {
        $payload = self::buildPayload($sale, $overrideScenario);
        $url = self::getValidateUrl();
        $token = self::getToken();

        return self::executeRequest($url, $token, $payload, 'validate', $sale->id, $payload['scenarioId']);
    }

    /**
     * Submit/Post invoice directly to FBR Digital Invoicing API
     */
    public static function postInvoice(Sale $sale, ?string $overrideScenario = null): array
    {
        self::ensureSchemaExists();

        // 1. Check if sale is in posted status in ERP
        if ($sale->sale_status !== 'post') {
            return [
                'success' => false,
                'message' => 'Sale invoice must be POSTED in the ERP before sending to FBR.'
            ];
        }

        // 2. Check if already posted to FBR
        if (!empty($sale->fbr_status) && $sale->fbr_status === 'posted' && !empty($sale->fbr_invoice_no)) {
            return [
                'success'        => true,
                'already_posted' => true,
                'fbr_invoice_no' => $sale->fbr_invoice_no,
                'message'        => 'This invoice is already posted to FBR: ' . $sale->fbr_invoice_no
            ];
        }

        $payload = self::buildPayload($sale, $overrideScenario);
        $url = self::getPostUrl();
        $token = self::getToken();

        $result = self::executeRequest($url, $token, $payload, 'post', $sale->id, $payload['scenarioId']);

        if ($result['success']) {
            $fbrInvoiceNo = $result['fbr_invoice_no'] ?? ($result['response_data']['invoiceNumber'] ?? null);

            $updateData = [
                'fbr_status'      => 'posted',
                'fbr_invoice_no'  => $fbrInvoiceNo,
                'fbr_scenario_id' => $payload['scenarioId'],
                'fbr_posted_at'   => now(),
                'fbr_environment' => self::getEnvironment(),
                'fbr_qr_code'     => $fbrInvoiceNo,
                'fbr_response'    => json_encode($result['response_data'] ?? [])
            ];

            try {
                $sale->update($updateData);
            } catch (\Throwable $ex) {
                Log::warning('FBR update failed, running ensureSchemaExists and retrying: ' . $ex->getMessage());
                self::ensureSchemaExists();
                try {
                    DB::table('sales')->where('id', $sale->id)->update($updateData);
                } catch (\Throwable $retryEx) {
                    Log::error('FBR update retry failed: ' . $retryEx->getMessage());
                }
            }
        } else {
            try {
                $sale->update([
                    'fbr_status'   => 'failed',
                    'fbr_response' => json_encode($result['response_data'] ?? ['error' => $result['message']])
                ]);
            } catch (\Throwable $ex) {
                self::ensureSchemaExists();
                try {
                    DB::table('sales')->where('id', $sale->id)->update([
                        'fbr_status'   => 'failed',
                        'fbr_response' => json_encode($result['response_data'] ?? ['error' => $result['message']])
                    ]);
                } catch (\Throwable $retryEx) {}
            }
        }

        return $result;
    }

    /**
     * Helper to execute cURL to FBR
     */
    private static function executeRequest(string $url, string $token, array $payload, string $action, ?int $saleId = null, ?string $scenarioId = null): array
    {
        $jsonPayload = json_encode($payload, JSON_UNESCAPED_SLASHES);
        $environment = self::getEnvironment();

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
            'Accept: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);

        $responseRaw = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        $responseData = json_decode($responseRaw, true) ?? [];

        // Log to database
        $statusCode = null;
        $statusStr = null;
        $fbrInvoiceNo = null;
        $errorMessage = null;

        if (!empty($responseData['validationResponse'])) {
            $statusCode = $responseData['validationResponse']['statusCode'] ?? null;
            $statusStr = $responseData['validationResponse']['status'] ?? null;
            $errorMessage = $responseData['validationResponse']['error'] ?? null;
        }

        if (!empty($responseData['invoiceNumber'])) {
            $fbrInvoiceNo = $responseData['invoiceNumber'];
        }

        if (!empty($curlError)) {
            $errorMessage = 'Network Error: ' . $curlError;
            $statusStr = 'Network Error';
        }

        // Check if successful
        $isSuccess = ($httpCode === 200 && ($statusStr === 'Valid' || $statusCode === '00' || !empty($fbrInvoiceNo)));

        try {
            DB::table('fbr_invoice_logs')->insert([
                'sale_id'          => $saleId,
                'environment'      => $environment,
                'action'           => $action,
                'scenario_id'      => $scenarioId,
                'request_payload'  => $jsonPayload,
                'response_payload' => $responseRaw,
                'status_code'      => $statusCode ?? (string)$httpCode,
                'status'           => $statusStr ?? ($isSuccess ? 'Valid' : 'Invalid'),
                'fbr_invoice_no'   => $fbrInvoiceNo,
                'error_message'    => $errorMessage,
                'created_by'       => auth()->id() ?? 1,
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('FBR Log Insert Failed: ' . $e->getMessage());
        }

        if ($isSuccess) {
            return [
                'success'        => true,
                'message'        => 'Successfully ' . ($action === 'post' ? 'posted to' : 'validated with') . ' FBR Digital Invoicing Gateway.',
                'fbr_invoice_no' => $fbrInvoiceNo,
                'dated'          => $responseData['dated'] ?? now()->toDateTimeString(),
                'response_data'  => $responseData,
                'payload'        => $payload
            ];
        }

        // Format clean error message
        $finalError = $errorMessage;
        if (empty($finalError)) {
            if (!empty($responseData['validationResponse']['invoiceStatuses'][0]['error'])) {
                $finalError = $responseData['validationResponse']['invoiceStatuses'][0]['error'];
            } elseif ($httpCode === 401) {
                $finalError = 'Unauthorized: Token or Seller NTN mismatch with FBR.';
            } else {
                $finalError = 'FBR Rejected: ' . ($responseRaw ?: 'HTTP Error ' . $httpCode);
            }
        }

        return [
            'success'        => false,
            'message'        => $finalError,
            'http_code'      => $httpCode,
            'response_data'  => $responseData,
            'payload'        => $payload
        ];
    }

    /**
     * Format HS code to 8 digits with dot XXXX.XXXX
     */
    private static function formatHsCode(?string $code, string $default = '9018.9090'): string
    {
        if (empty($code)) return $default;
        $clean = preg_replace('/[^0-9]/', '', $code);
        if (strlen($clean) >= 8) {
            return substr($clean, 0, 4) . '.' . substr($clean, 4, 4);
        }
        if (str_contains($code, '.')) {
            return $code;
        }
        return $default;
    }

    /**
     * Map Pakistani city to province
     */
    private static function mapCityToProvince(?string $city): string
    {
        if (empty($city)) return 'Punjab';
        $city = strtolower(trim($city));
        
        $sindh = ['karachi', 'hyderabad', 'sukkur', 'larkana', 'mirpurkhas', 'nawabshah'];
        $kpk = ['peshawar', 'mardan', 'abbottabad', 'swat', 'bannu', 'kohat', 'dera ismail khan'];
        $balochistan = ['quetta', 'gwadar', 'turbat', 'khuzdar', 'sibi'];
        $isb = ['islamabad'];

        if (in_array($city, $sindh)) return 'Sindh';
        if (in_array($city, $kpk)) return 'Khyber Pakhtunkhwa';
        if (in_array($city, $balochistan)) return 'Balochistan';
        if (in_array($city, $isb)) return 'Islamabad Capital Territory';

        return 'Punjab'; // Default
    }

    /**
     * Get list of all FBR Digital Invoicing scenarios for dropdowns
     */
    public static function getScenarios(): array
    {
        return [
            'SN001' => 'SN001: Sale of Standard Rate Goods to Registered Buyers',
            'SN002' => 'SN002: Sale of Standard Rate Goods to Unregistered Buyers',
            'SN003' => 'SN003: Sale of Steel (Melted and Re-Rolled)',
            'SN004' => 'SN004: Sale of Steel Scrap by Ship Breakers',
            'SN005' => 'SN005: Sale of Reduced Rate Goods (Eighth Schedule)',
            'SN006' => 'SN006: Sale of Exempt Goods (Sixth Schedule)',
            'SN007' => 'SN007: Sale of Zero-Rated Goods (Fifth Schedule)',
            'SN008' => 'SN008: Sale of 3rd Schedule Goods',
            'SN024' => 'SN024: Sale Of Goods Listed In SRO 297(I)/2023',
            'SN025' => 'SN025: Drugs Sold at Fixed ST Rate Under Serial 81 Of Eighth Schedule Table 1',
            'SN026' => 'SN026: Sale Of Goods at Standard Rate to End Consumers by Retailers',
            'SN027' => 'SN027: Sale Of 3rd Schedule Goods to End Consumers by Retailers',
            'SN028' => 'SN028: Sale Of Goods at Reduced Rate to End Consumers by Retailers',
        ];
    }
}
