<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Customer;
use App\Models\Vendor;

class EnhancedMatcher {
    public static function normalizeString(?string $str): string
    {
        if (!$str) return '';
        // Replace non-breaking spaces and all whitespace with single space
        $str = preg_replace('/[\xc2\xa0\s]+/u', ' ', $str);
        return strtoupper(trim($str));
    }

    public static function cleanBaseName(?string $str): string
    {
        $str = self::normalizeString($str);
        if ($str === '') return '';
        // Remove punctuation except alphanumeric and space
        $str = preg_replace('/[^A-Z0-9 ]/', '', $str);
        $words = array_filter(explode(' ', $str));
        
        $cleanWords = array_map(function($w) {
            // Normalize plurals on common words: BROTHERS->BROTHER, TRADERS->TRADER, STORES->STORE, etc.
            if (strlen($w) > 4 && str_ends_with($w, 'S') && !in_array($w, ['PLUS', 'STATUS', 'SERVICES'])) {
                return substr($w, 0, -1);
            }
            return $w;
        }, $words);

        return implode(' ', $cleanWords);
    }

    public static function findTwinVendor(Customer $customer): ?Vendor
    {
        $cCode = trim($customer->customer_id ?? '');
        $cCnic = preg_replace('/[^0-9]/', '', $customer->cnic ?? '');
        $cPhone = preg_replace('/[^0-9]/', '', $customer->mobile ?? '');
        $cEmail = strtolower(trim($customer->email_address ?? ''));
        $cName = self::normalizeString($customer->customer_name ?? '');
        $cBase = self::cleanBaseName($customer->customer_name ?? '');
        $cNum  = preg_match('/(\d+)$/', $cCode, $m) ? (int)$m[1] : null;

        // 1. Direct code exact match (e.g. 1 == 1 or CUST-0234 == CUST-0234)
        if ($cCode !== '') {
            $v = Vendor::where('vendor_code', $cCode)->first();
            if ($v) return $v;
        }

        // 2. CNIC exact match (when at least 10 digits)
        if (strlen($cCnic) >= 10) {
            $v = Vendor::where(function ($q) use ($customer, $cCnic) {
                $q->where('cnic', $customer->cnic)
                  ->orWhereRaw("REPLACE(REPLACE(REPLACE(cnic, '-', ''), ' ', ''), '.', '') = ?", [$cCnic]);
            })->first();
            if ($v) return $v;
        }

        // 3. Exact Name match (ignoring whitespace / case)
        if (strlen($cName) >= 3) {
            $v = Vendor::all()->first(function($item) use ($cName) {
                return EnhancedMatcher::normalizeString($item->name) === $cName;
            });
            if ($v) return $v;
        }

        // 4. Clean Base Name match (handles extra spaces, punctuation, BROTHER vs BROTHERS, etc.)
        if (strlen($cBase) >= 4) {
            $v = Vendor::all()->first(function($item) use ($cBase) {
                return EnhancedMatcher::cleanBaseName($item->name) === $cBase;
            });
            if ($v) return $v;
        }

        // 5. Phone / Mobile match (when at least 10 digits, valid phone)
        if (strlen($cPhone) >= 10 && !preg_match('/^0+$/', $cPhone)) {
            $v = Vendor::all()->first(function($item) use ($cPhone) {
                $vPhone = preg_replace('/[^0-9]/', '', $item->phone ?? '');
                return strlen($vPhone) >= 10 && $vPhone === $cPhone;
            });
            if ($v) return $v;
        }

        // 6. Email match
        if (strlen($cEmail) >= 6 && filter_var($cEmail, FILTER_VALIDATE_EMAIL)) {
            $v = Vendor::whereRaw('LOWER(TRIM(email)) = ?', [$cEmail])->first();
            if ($v) return $v;
        }

        // 7. Code numeric suffix match + Name similarity / ID match
        if ($cNum && strlen($cBase) >= 4) {
            $pad = str_pad($cNum, 4, '0', STR_PAD_LEFT);
            $v = Vendor::where(function ($q) use ($pad) {
                $q->where('vendor_code', "VND-{$pad}")
                  ->orWhere('vendor_code', "VEND-{$pad}")
                  ->orWhere('vendor_code', "V-{$pad}");
            })->first();

            if ($v && (self::cleanBaseName($v->name) === $cBase || $v->id === $customer->id)) {
                return $v;
            }
        }

        return null;
    }

    public static function findTwinCustomer(Vendor $vendor): ?Customer
    {
        $vCode = trim($vendor->vendor_code ?? '');
        $vCnic = preg_replace('/[^0-9]/', '', $vendor->cnic ?? '');
        $vPhone = preg_replace('/[^0-9]/', '', $vendor->phone ?? '');
        $vEmail = strtolower(trim($vendor->email ?? ''));
        $vName = self::normalizeString($vendor->name ?? '');
        $vBase = self::cleanBaseName($vendor->name ?? '');
        $vNum  = preg_match('/(\d+)$/', $vCode, $m) ? (int)$m[1] : null;

        // 1. Direct code exact match
        if ($vCode !== '') {
            $c = Customer::where('customer_id', $vCode)->first();
            if ($c) return $c;
        }

        // 2. CNIC exact match
        if (strlen($vCnic) >= 10) {
            $c = Customer::where(function ($q) use ($vendor, $vCnic) {
                $q->where('cnic', $vendor->cnic)
                  ->orWhereRaw("REPLACE(REPLACE(REPLACE(cnic, '-', ''), ' ', ''), '.', '') = ?", [$vCnic]);
            })->first();
            if ($c) return $c;
        }

        // 3. Exact Name match (ignoring whitespace / case)
        if (strlen($vName) >= 3) {
            $c = Customer::all()->first(function($item) use ($vName) {
                return EnhancedMatcher::normalizeString($item->customer_name) === $vName;
            });
            if ($c) return $c;
        }

        // 4. Clean Base Name match
        if (strlen($vBase) >= 4) {
            $c = Customer::all()->first(function($item) use ($vBase) {
                return EnhancedMatcher::cleanBaseName($item->customer_name) === $vBase;
            });
            if ($c) return $c;
        }

        // 5. Phone / Mobile match
        if (strlen($vPhone) >= 10 && !preg_match('/^0+$/', $vPhone)) {
            $c = Customer::all()->first(function($item) use ($vPhone) {
                $cPhone = preg_replace('/[^0-9]/', '', $item->mobile ?? '');
                return strlen($cPhone) >= 10 && $cPhone === $vPhone;
            });
            if ($c) return $c;
        }

        // 6. Email match
        if (strlen($vEmail) >= 6 && filter_var($vEmail, FILTER_VALIDATE_EMAIL)) {
            $c = Customer::whereRaw('LOWER(TRIM(email_address)) = ?', [$vEmail])->first();
            if ($c) return $c;
        }

        // 7. Code numeric suffix match + Name similarity / ID match
        if ($vNum && strlen($vBase) >= 4) {
            $pad = str_pad($vNum, 4, '0', STR_PAD_LEFT);
            $c = Customer::where(function ($q) use ($pad) {
                $q->where('customer_id', "CUST-{$pad}")
                  ->orWhere('customer_id', "C-{$pad}");
            })->first();

            if ($c && (self::cleanBaseName($c->customer_name) === $vBase || $c->id === $vendor->id)) {
                return $c;
            }
        }

        return null;
    }
}

// Test on TALHA BROTHERS
$cTalha = Customer::find(543);
$vTalha = Vendor::find(458);

$matchV = EnhancedMatcher::findTwinVendor($cTalha);
$matchC = EnhancedMatcher::findTwinCustomer($vTalha);

echo "Customer 543 -> Twin Vendor: " . ($matchV ? "ID {$matchV->id} '{$matchV->name}' ({$matchV->vendor_code})" : "NOT FOUND") . "\n";
echo "Vendor 458 -> Twin Customer: " . ($matchC ? "ID {$matchC->id} '{$matchC->customer_name}' ({$matchC->customer_id})" : "NOT FOUND") . "\n";
