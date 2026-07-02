<?php
require_once __DIR__ . '/securitycheck.php';
require_once __DIR__ . '/../bases/ipcountry.php';

header('Content-Type: application/json');

$domain = trim($_GET['domain'] ?? '');
if ($domain === '') {
    echo json_encode(['error' => 'No domain provided']);
    exit;
}

// Strip protocol if user accidentally included it
$domain = preg_replace('#^https?://#i', '', $domain);
$domain = rtrim($domain, '/');

// Wildcard domains can't be DNS-checked
if (str_contains($domain, '*')) {
    echo json_encode([
        'domain' => $domain,
        'wildcard' => true,
        'resolves' => false,
        'cloudflare' => false,
        'ip' => null,
        'serverIp' => null,
        'error' => null
    ]);
    exit;
}

// Strip port for DNS lookup
$dnsHost = $domain;
if (str_contains($dnsHost, ':')) {
    $dnsHost = explode(':', $dnsHost)[0];
}

$serverIp = $_SERVER['SERVER_ADDR'] ?? gethostbyname(gethostname());

// Resolve domain
$records = @dns_get_record($dnsHost, DNS_A);
if ($records === false || empty($records)) {
    echo json_encode([
        'domain' => $domain,
        'wildcard' => false,
        'resolves' => false,
        'cloudflare' => false,
        'ip' => null,
        'serverIp' => $serverIp,
        'error' => "DNS lookup failed — no A record found for $dnsHost"
    ]);
    exit;
}

$resolvedIp = $records[0]['ip'];

$isCloudflare = is_cloudflare_ip($resolvedIp);

// If CloudFlare, domain resolves to CF proxy — that's valid
$resolves = ($resolvedIp === $serverIp) || $isCloudflare;

$error = null;
if (!$resolves) {
    $error = "Domain resolves to $resolvedIp, but server IP is $serverIp";
}

echo json_encode([
    'domain' => $domain,
    'wildcard' => false,
    'resolves' => $resolves,
    'cloudflare' => $isCloudflare,
    'ip' => $resolvedIp,
    'serverIp' => $serverIp,
    'error' => $error
]);
