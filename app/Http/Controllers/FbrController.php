<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SystemSetting;
use App\Services\FbrService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FbrController extends Controller
{
    /**
     * Get sale details and FBR preview info for the FBR Post modal
     */
    public function getDetails($id)
    {
        FbrService::ensureSchemaExists();

        $sale = Sale::with(['customer_relation', 'items.product'])->findOrFail($id);

        $payload = FbrService::buildPayload($sale);
        $scenarios = FbrService::getScenarios();
        $isFbrPosted = (($sale->fbr_status ?? '') === 'posted');

        return response()->json([
            'success'          => true,
            'sale_id'          => $sale->id,
            'invoice_no'       => $sale->invoice_no,
            'sale_status'      => $sale->sale_status,
            'fbr_status'       => $sale->fbr_status ?? 'unposted',
            'fbr_invoice_no'   => $sale->fbr_invoice_no,
            'fbr_posted_at'    => $sale->fbr_posted_at ? date('d-M-Y H:i', strtotime($sale->fbr_posted_at)) : null,
            'is_fbr_posted'    => $isFbrPosted,
            'detected_scenario'=> $payload['scenarioId'],
            'seller'           => FbrService::getSellerDetails(),
            'buyer'            => [
                'name'              => $payload['buyerBusinessName'],
                'ntn_cnic'          => $payload['buyerNTNCNIC'],
                'registration_type' => $payload['buyerRegistrationType'],
                'province'          => $payload['buyerProvince'],
                'address'           => $payload['buyerAddress'],
            ],
            'items_count'      => count($payload['items']),
            'total_net'        => $sale->total_net,
            'total_gst'        => $sale->total_gst,
            'scenarios'        => $scenarios,
            'environment'      => FbrService::getEnvironment(),
        ]);
    }

    /**
     * Post a sale invoice to FBR Digital Invoicing API
     */
    public function postToFbr(Request $request, $id)
    {
        try {
            FbrService::ensureSchemaExists();

            $sale = Sale::findOrFail($id);

            if ($sale->sale_status !== 'post') {
                return response()->json([
                    'success' => false,
                    'message' => 'Sale invoice must be POSTED in the ERP before submitting to FBR.'
                ], 422);
            }

            $scenarioId = $request->input('scenario_id');
            $result = FbrService::postInvoice($sale, $scenarioId);

            return response()->json($result, $result['success'] ? 200 : 400);
        } catch (\Throwable $e) {
            \Log::error('FBR postToFbr Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Server Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate sale invoice data against FBR without posting
     */
    public function validateWithFbr(Request $request, $id)
    {
        try {
            FbrService::ensureSchemaExists();

            $sale = Sale::findOrFail($id);
            $scenarioId = $request->input('scenario_id');
            $result = FbrService::validateInvoice($sale, $scenarioId);

            return response()->json($result, $result['success'] ? 200 : 400);
        } catch (\Throwable $e) {
            \Log::error('FBR validateWithFbr Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Server Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * FBR Settings Management Page
     */
    public function settingsPage()
    {
        $environment   = SystemSetting::get('fbr_environment', 'sandbox');
        $enabled       = (bool) SystemSetting::get('fbr_enabled', true);
        $sandboxUrl    = SystemSetting::get('fbr_sandbox_url', 'https://gw.fbr.gov.pk/di_data/v1/di/postinvoicedata_sb');
        $sandboxValUrl = SystemSetting::get('fbr_sandbox_validate_url', 'https://gw.fbr.gov.pk/di_data/v1/di/validateinvoicedata_sb');
        $sandboxToken  = SystemSetting::get('fbr_sandbox_token', 'c2bb2ccd-c57d-3f97-9b8b-0fbc84d598d8');
        $prodUrl       = SystemSetting::get('fbr_production_url', 'https://gw.fbr.gov.pk/di_data/v1/di/postinvoicedata');
        $prodValUrl    = SystemSetting::get('fbr_production_validate_url', 'https://gw.fbr.gov.pk/di_data/v1/di/validateinvoicedata');
        $prodToken     = SystemSetting::get('fbr_production_token', '');
        $sellerNtn     = SystemSetting::get('fbr_seller_ntn', '3520224331243');
        $sellerName    = SystemSetting::get('fbr_seller_name', 'THREE STARS MEDICAL SUPPLIES');
        $sellerProvince= SystemSetting::get('fbr_seller_province', 'Punjab');
        $sellerAddress = SystemSetting::get('fbr_seller_address', 'M17-M18 SAITH CENTRE SYED MOJ DARYA ROAD LAHORE');
        $defaultHs     = SystemSetting::get('fbr_default_hs_code', '9018.9090');
        $scenarios     = FbrService::getScenarios();
        $sandboxScenarios = FbrService::getAllSandboxScenarios();
        $defaultScenario = SystemSetting::get('fbr_default_scenario', 'SN001');

        $logs = DB::table('fbr_invoice_logs')
            ->leftJoin('sales', 'sales.id', '=', 'fbr_invoice_logs.sale_id')
            ->select('fbr_invoice_logs.*', 'sales.invoice_no')
            ->latest('fbr_invoice_logs.id')
            ->limit(30)
            ->get();

        return view('admin_panel.settings.fbr_settings', compact(
            'environment', 'enabled', 'sandboxUrl', 'sandboxValUrl', 'sandboxToken',
            'prodUrl', 'prodValUrl', 'prodToken', 'sellerNtn', 'sellerName',
            'sellerProvince', 'sellerAddress', 'defaultHs', 'scenarios', 'sandboxScenarios', 'defaultScenario', 'logs'
        ));

    }

    /**
     * Update FBR Settings
     */
    public function updateSettings(Request $request)
    {
        $request->validate([
            'fbr_environment'   => 'required|in:sandbox,production',
            'fbr_seller_ntn'    => 'required|string',
            'fbr_seller_name'   => 'required|string',
            'fbr_seller_province'=> 'required|string',
            'fbr_seller_address'=> 'required|string',
        ]);

        $keys = [
            'fbr_enabled'                  => $request->has('fbr_enabled') ? '1' : '0',
            'fbr_environment'              => $request->fbr_environment,
            'fbr_sandbox_url'              => $request->fbr_sandbox_url,
            'fbr_sandbox_validate_url'     => $request->fbr_sandbox_validate_url,
            'fbr_sandbox_token'            => $request->fbr_sandbox_token,
            'fbr_production_url'           => $request->fbr_production_url,
            'fbr_production_validate_url'  => $request->fbr_production_validate_url,
            'fbr_production_token'         => $request->fbr_production_token,
            'fbr_seller_ntn'               => $request->fbr_seller_ntn,
            'fbr_seller_name'              => $request->fbr_seller_name,
            'fbr_seller_province'          => $request->fbr_seller_province,
            'fbr_seller_address'           => $request->fbr_seller_address,
            'fbr_default_scenario'         => $request->fbr_default_scenario ?? 'SN001',
            'fbr_default_hs_code'          => $request->fbr_default_hs_code ?? '9018.9090',
        ];

        foreach ($keys as $key => $val) {
            SystemSetting::updateOrCreate(['key' => $key], [
                'value' => $val,
                'group' => 'fbr',
                'type'  => 'string'
            ]);
            \Illuminate\Support\Facades\Cache::forget("setting_{$key}");
        }

        return redirect()->back()->with('success', 'FBR Digital Invoicing settings updated successfully.');
    }

    /**
     * Test Gateway Connectivity
     */
    public function testConnection()
    {
        $url = FbrService::getValidateUrl();
        $token = FbrService::getToken();
        $seller = FbrService::getSellerDetails();

        $dummyPayload = [
            "invoiceType"           => "Sale Invoice",
            "invoiceDate"           => date('Y-m-d'),
            "sellerNTNCNIC"         => $seller['sellerNTNCNIC'],
            "sellerBusinessName"    => $seller['sellerBusinessName'],
            "sellerProvince"        => $seller['sellerProvince'],
            "sellerAddress"         => $seller['sellerAddress'],
            "buyerNTNCNIC"          => "0000000000000",
            "buyerBusinessName"     => "Test Connectivity",
            "buyerProvince"         => "Punjab",
            "buyerAddress"          => "Lahore",
            "buyerRegistrationType" => "Unregistered",
            "invoiceRefNo"          => "",
            "scenarioId"            => "SN002",
            "items"                 => [
                [
                    "hsCode"                          => "9018.9090",
                    "productDescription"             => "Ping Test",
                    "rate"                            => "18%",
                    "uoM"                             => "Numbers, pieces, units",
                    "quantity"                        => 1,
                    "totalValues"                     => 118,
                    "valueSalesExcludingST"           => 100,
                    "fixedNotifiedValueOrRetailPrice" => 0.0,
                    "salesTaxApplicable"              => 18,
                    "salesTaxWithheldAtSource"        => 0,
                    "extraTax"                        => "",
                    "furtherTax"                      => 0,
                    "sroScheduleNo"                   => "",
                    "fedPayable"                      => 0,
                    "discount"                        => 0,
                    "saleType"                        => "Goods at standard rate (default)",
                    "sroItemSerialNo"                 => ""
                ]
            ]
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
            'Accept: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($dummyPayload));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        $json = json_decode($response, true);
        $status = $json['validationResponse']['status'] ?? null;
        $isOk = ($httpCode === 200 && ($status === 'Valid' || isset($json['validationResponse'])));

        return response()->json([
            'success'     => $isOk,
            'http_code'   => $httpCode,
            'status'      => $status ?? ($isOk ? 'Connected' : 'Error'),
            'environment' => FbrService::getEnvironment(),
            'url'         => $url,
            'response'    => $json ?? $response,
            'error'       => $err,
            'message'     => $isOk ? 'Successfully connected to FBR Digital Invoicing Gateway!' : 'Could not reach FBR Gateway: ' . ($err ?: 'HTTP ' . $httpCode)
        ]);
    }

    /**
     * Run all 14 Sandbox Scenarios (API endpoint for the Settings UI)
     */
    public function runAllScenarios(Request $request)
    {
        try {
            $validateOnly = $request->boolean('validate_only', false);
            $res = FbrService::runAllSandboxScenarios($validateOnly);

            return response()->json([
                'success'     => true,
                'is_all_pass' => $res['is_all_pass'],
                'total'       => $res['total'],
                'successful'  => $res['successful'],
                'failed'      => $res['failed'],
                'scenarios'   => array_values($res['scenarios']),
                'message'     => $res['is_all_pass'] 
                    ? "All {$res['total']} scenarios passed successfully! Your FBR Sandbox is ready for Production Security Token generation."
                    : "{$res['successful']} of {$res['total']} scenarios succeeded."
            ]);
        } catch (\Throwable $e) {
            \Log::error('FBR runAllScenarios Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error running scenarios: ' . $e->getMessage()
            ], 500);
        }
    }
}

