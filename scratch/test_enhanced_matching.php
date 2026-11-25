<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Customer;
use App\Models\Vendor;
use App\Services\DualPartyLedgerService;

$dualService = app(DualPartyLedgerService::class);
$customers = Customer::all();
$vendors = Vendor::all();

echo "Total Customers: " . $customers->count() . "\n";
echo "Total Vendors: " . $vendors->count() . "\n";

// Let's see how many currently match
$currentMatches = 0;
foreach ($customers as $c) {
    if ($dualService->findTwinVendor($c)) {
        $currentMatches++;
    }
}
echo "Currently matched by DualPartyLedgerService: $currentMatches\n";

// Let's test a smarter matching algorithm
function normalizeString($str) {
    $str = strtoupper(trim($str ?? ''));
    // Remove extra spaces
    $str = preg_replace('/\s+/', ' ', $str);
    // Remove common business entity suffixes or plurals for comparison
    // but keep base
    return $str;
}

function cleanBaseName($str) {
    $str = normalizeString($str);
    // remove punctuation
    $str = preg_replace('/[^A-Z0-9 ]/', '', $str);
    // remove words like PVT, LTD, TRADERS, TRADER, BROTHERS, BROTHER, CO, COMPANY, PHARMA, PHARMACEUTICALS
    // Or just normalize trailing 'S' on words:
    $words = explode(' ', $str);
    $cleanWords = array_map(function($w) {
        // e.g. BROTHERS -> BROTHER
        if (strlen($w) > 3 && str_ends_with($w, 'S') && !in_array($w, ['PLUS', 'SERVICES', 'STATUS'])) {
            return substr($w, 0, -1);
        }
        return $w;
    }, $words);
    return implode(' ', $cleanWords);
}

$newMatches = [];
foreach ($customers as $c) {
    $twin = $dualService->findTwinVendor($c);
    if (!$twin) {
        $cNameClean = cleanBaseName($c->customer_name);
        $cPhone = preg_replace('/[^0-9]/', '', $c->mobile ?? '');
        $cEmail = strtolower(trim($c->email_address ?? ''));
        
        foreach ($vendors as $v) {
            $vNameClean = cleanBaseName($v->name);
            $vPhone = preg_replace('/[^0-9]/', '', $v->phone ?? '');
            $vEmail = strtolower(trim($v->email ?? ''));
            
            $matched = false;
            $reason = '';
            
            // Name base match
            if (strlen($cNameClean) >= 4 && $cNameClean === $vNameClean) {
                $matched = true;
                $reason = "Clean name match: '{$c->customer_name}' vs '{$v->name}'";
            }
            // Levenshtein similarity on names >= 6 chars
            elseif (strlen($cNameClean) >= 6 && strlen($vNameClean) >= 6 && (levenshtein($cNameClean, $vNameClean) <= 1 || similar_text($cNameClean, $vNameClean, $perc) && $perc >= 92)) {
                $matched = true;
                $reason = "Fuzzy name match ($perc%): '{$c->customer_name}' vs '{$v->name}'";
            }
            // Phone match (at least 7 digits, not all zeros)
            elseif (strlen($cPhone) >= 7 && $cPhone === $vPhone && !preg_match('/^0+$/', $cPhone)) {
                $matched = true;
                $reason = "Phone match: {$cPhone}";
            }
            // Email match (valid email)
            elseif (strlen($cEmail) >= 6 && $cEmail === $vEmail && filter_var($cEmail, FILTER_VALIDATE_EMAIL)) {
                $matched = true;
                $reason = "Email match: {$cEmail}";
            }
            
            if ($matched) {
                $newMatches[] = [
                    'cust_id' => $c->id,
                    'cust_name' => $c->customer_name,
                    'cust_code' => $c->customer_id,
                    'vend_id' => $v->id,
                    'vend_name' => $v->name,
                    'vend_code' => $v->vendor_code,
                    'reason' => $reason
                ];
                break;
            }
        }
    }
}

echo "Additional pairs found with enhanced matching: " . count($newMatches) . "\n";
foreach ($newMatches as $m) {
    echo "  Cust #{$m['cust_id']} '{$m['cust_name']}' ({$m['cust_code']}) <-> Vend #{$m['vend_id']} '{$m['vend_name']}' ({$m['vend_code']}) | {$m['reason']}\n";
}
