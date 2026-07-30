<?php
require_once __DIR__ . '/../bases/ipcountry.php';

function get_admin_request_domain(array $server): string
{
    $serverName = trim((string)($server['SERVER_NAME'] ?? ''));
    if ($serverName === '') {
        return '';
    }

    $domain = parse_url('http://' . $serverName, PHP_URL_HOST);
    return is_string($domain) ? $domain : $serverName;
}

function get_admin_request_ip(array $server, ?callable $isCloudflareIp = null): string
{
    $remoteAddr = trim((string)($server['REMOTE_ADDR'] ?? ''));
    $cfConnectingIp = trim((string)($server['HTTP_CF_CONNECTING_IP'] ?? ''));
    $cloudflareChecker = $isCloudflareIp ?? 'is_cloudflare_ip';

    if (
        is_public_ip($cfConnectingIp)
        && filter_var($remoteAddr, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6) === $remoteAddr
        && $cloudflareChecker($remoteAddr)
    ) {
        return $cfConnectingIp;
    }

    if (filter_var($remoteAddr, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6) === $remoteAddr) {
        return $remoteAddr;
    }

    return '';
}

/** @return list<string> */
function get_allowed_admin_ips(array $settings): array
{
    $configuredIps = trim((string)($settings['adminIp'] ?? ''));
    if ($configuredIps === '') {
        return [];
    }

    return array_values(array_unique(array_filter(
        array_map(static fn(string $ip): string => trim($ip), explode(',', $configuredIps)),
        static fn(string $ip): bool => $ip !== ''
    )));
}

function get_admin_shortcut_redirect(array $server, array $settings, ?callable $isCloudflareIp = null): ?string
{
    $adminIps = get_allowed_admin_ips($settings);
    if (
        $adminIps === []
        || !in_array(get_admin_request_ip($server, $isCloudflareIp), $adminIps, true)
    ) {
        return null;
    }

    $adminPath = trim((string)($settings['adminPath'] ?? ''), "/ \t\n\r\0\x0B");
    if (preg_match('/^[A-Za-z0-9_-]{1,64}$/', $adminPath) !== 1) {
        return null;
    }

    $scriptName = str_replace('\\', '/', (string)($server['SCRIPT_NAME'] ?? '/admin.php'));
    $basePath = str_replace('\\', '/', dirname($scriptName));
    if ($basePath === '/' || $basePath === '.') {
        $basePath = '';
    } else {
        $basePath = '/' . trim($basePath, '/');
    }

    return $basePath . '/' . $adminPath . '/';
}

function get_admin_access_error(array $server, array $settings, ?callable $isCloudflareIp = null): ?string
{
    $adminDomain = trim((string)($settings['adminDomain'] ?? ''));
    if ($adminDomain !== '') {
        $currentDomain = get_admin_request_domain($server);
        if ($currentDomain !== $adminDomain) {
            return "Admin Domain $adminDomain is set, but your domain is $currentDomain. You are not allowed to access this page!";
        }
    }

    $configuredAdminIps = trim((string)($settings['adminIp'] ?? ''));
    if ($configuredAdminIps !== '') {
        $currentIp = get_admin_request_ip($server, $isCloudflareIp);
        if (!in_array($currentIp, get_allowed_admin_ips($settings), true)) {
            return "Admin IPs $configuredAdminIps are set, but your IP is $currentIp. You are not allowed to access this page!";
        }
    }

    return null;
}

/** @return array{status: int, body: string} */
function get_admin_access_denial_response(string $accessError, bool $debug): array
{
    if ($debug) {
        return [
            'status' => 200,
            'body' => $accessError,
        ];
    }

    return [
        'status' => 404,
        'body' => 'Not Found',
    ];
}
