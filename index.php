<?php

/**
 * Laravel Root Gateway for Shared Hosting (DirectAdmin / cPanel)
 * Proxies execution directly to public/index.php
 */

$publicIndex = __DIR__ . '/public/index.php';

if (file_exists($publicIndex)) {
    require_once $publicIndex;
} else {
    http_response_code(500);
    echo "Laravel public directory not found.";
}
