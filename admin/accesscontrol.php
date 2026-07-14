<?php
require_once __DIR__ . '/../bases/ipcountry.php';

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

function get_admin_shortcut_redirect(array $server, array $settings, ?callable $isCloudflareIp = null): ?string
{
    $adminIp = trim((string)($settings['adminIp'] ?? ''));
    if ($adminIp === '' || get_admin_request_ip($server, $isCloudflareIp) !== $adminIp) {
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
        $currentDomain = (string)($server['SERVER_NAME'] ?? '');
        if ($currentDomain !== $adminDomain) {
            return "Admin Domain $adminDomain is set, but your domain is $currentDomain. You are not allowed to access this page!";
        }
    }

    $adminIp = trim((string)($settings['adminIp'] ?? ''));
    if ($adminIp !== '') {
        $currentIp = get_admin_request_ip($server, $isCloudflareIp);
        if ($currentIp !== $adminIp) {
            return "Admin IP $adminIp is set, but your IP is $currentIp. You are not allowed to access this page!";
        }
    }

    return null;
}
