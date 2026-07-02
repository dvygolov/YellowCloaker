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
