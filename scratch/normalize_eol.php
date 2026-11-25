<?php
$files = [
    'app/Http/Controllers/ReportingController.php',
    'resources/views/admin_panel/reporting/vendor_ledger_report.blade.php',
    'resources/views/admin_panel/reporting/customer_ledger_report.blade.php',
];

foreach ($files as $file) {
    if (file_exists($file)) {
        $c = file_get_contents($file);
        $c = str_replace("\r\n", "\n", $c);
        file_put_contents($file, $c);
        echo "Normalized $file\n";
    }
}
