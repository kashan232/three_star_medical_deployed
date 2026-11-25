<?php

$logPath = __DIR__ . '/../storage/logs/laravel.log';
if (!file_exists($logPath)) {
    echo "No laravel.log found\n";
    exit;
}

$lines = file($logPath);
echo "Total log lines: " . count($lines) . "\n";
$matches = [];
foreach ($lines as $i => $line) {
    if (stripos($line, 'SIN-0008') !== false || stripos($line, 'Sale #9') !== false || stripos($line, '692') !== false) {
        $matches[] = "Line $i: $line";
    }
}

echo "Found " . count($matches) . " log lines mentioning SIN-0008 or product 692:\n";
foreach (array_slice($matches, -30) as $m) {
    echo $m;
}
