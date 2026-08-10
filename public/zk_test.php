<?php
// Direct ZKTeco test via web
require_once 'C:/xampp-new-latest/htdocs/three-star-faraz/public_html/vendor/autoload.php';

header('Content-Type: text/plain; charset=utf-8');

echo "PHP Version: " . phpversion() . "\n";
echo "Sockets Extension: " . (extension_loaded('sockets') ? 'YES' : 'NO') . "\n\n";

try {
    $zk = new Fsuuaas\Zkteco\Lib\ZKTeco('192.168.1.201', 4370);
    echo "ZKTeco object created OK\n";
    
    $connected = $zk->connect();
    if ($connected) {
        echo "SUCCESS: ZKTeco Connected!\n";
        echo "Device Name: " . $zk->deviceName() . "\n";
        echo "Serial No: " . $zk->serialNumber() . "\n";
        $zk->disconnect();
    } else {
        echo "FAILED: ZKTeco connect() returned false\n";
    }
} catch (\Throwable $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
    echo $e->getTraceAsString() . "\n";
}
