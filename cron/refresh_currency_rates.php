<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../currency.php';

$ok = CurrencyRateManager::refresh(false);
echo $ok ? "Currency rates cache is fresh.\n" : "Currency rates refresh failed.\n";
exit($ok ? 0 : 1);
