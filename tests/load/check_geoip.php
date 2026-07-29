<?php
require_once __DIR__ . '/../../code/settings.php';
require_once __DIR__ . '/../../code/core.php';

// Simulate what happens with a request
$_SERVER['HTTP_HOST'] = '127.0.0.1:8080';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/121.0.0.0 Safari/537.36';
$_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'en-US,en;q=0.9';
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['QUERY_STRING'] = '';
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['PHP_SELF'] = '/index.php';

$core = new FiltrationCore();
$params = $core->click_params;

echo "=== Click params ===\n";
echo "IP: {$params['ip']}\n";
echo "Country: '{$params['country']}'\n";
echo "OS: {$params['os']}\n";
echo "Device: {$params['device']}\n";
echo "Client: {$params['client']}\n";
echo "UA: " . substr($params['useragent'], 0, 60) . "\n";
echo "ISP: {$params['isp']}\n";

// Now test with X-Forwarded-For
echo "\n=== With X-Forwarded-For: 8.8.8.8 ===\n";
$_SERVER['HTTP_X_FORWARDED_FOR'] = '8.8.8.8';
$core2 = new FiltrationCore();
$params2 = $core2->click_params;
echo "IP: {$params2['ip']}\n";
echo "Country: '{$params2['country']}'\n";
echo "ISP: {$params2['isp']}\n";

// Test filter matching
echo "\n=== Filter test: country IN 'RU,BG,SG' ===\n";
$filters = [
    'condition' => 'OR',
    'rules' => [
        ['id' => 'country', 'field' => 'country', 'type' => 'string', 'input' => 'text', 'operator' => 'in', 'value' => 'RU,BG,SG'],
    ],
    'valid' => true
];
$result = $core2->click_matches_filters($filters);
echo "Matches (should be false for US IP): " . ($result ? 'TRUE (blocked)' : 'FALSE (passes)') . "\n";
echo "Block reason: {$core2->block_reason}\n";

// Test with empty country
echo "\n=== Filter test with 127.0.0.1 (no X-Forwarded-For) ===\n";
unset($_SERVER['HTTP_X_FORWARDED_FOR']);
$core3 = new FiltrationCore();
echo "Country: '{$core3->click_params['country']}'\n";
$result3 = $core3->click_matches_filters($filters);
echo "Matches: " . ($result3 ? 'TRUE (blocked)' : 'FALSE (passes)') . "\n";
echo "Block reason: {$core3->block_reason}\n";
