<?php
require_once __DIR__ . '/../logging.php';
if (!extension_loaded('maxminddb')) {
    require_once __DIR__ . '/geoip2.phar';
}

function getip(array|null $headers = null): string
{
    if (is_null($headers)) $headers = $_SERVER;
    
    $remoteAddr = (string)($headers['REMOTE_ADDR'] ?? '');

    $trustedCfIp = get_ip_from_cloudfare($headers);
    if ($trustedCfIp !== null) {
        return $trustedCfIp;
    }

    if ($remoteAddr === '::1' || !is_public_ip($remoteAddr))
        return '109.124.224.100'; //for debugging
    
    return $remoteAddr;
}

function is_public_ip(string $ip): bool
{
    return filter_var(
    $ip,
    FILTER_VALIDATE_IP,
    FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
    ) === $ip;
}

function is_cloudflare_ip(string $ip): bool
{
    if (!is_public_ip($ip)) {
        return false;
    }

    try {
        $isp = getisp($ip);
    } catch (Throwable $exception) {
        return false;
    }

    return is_string($isp) && str_contains(strtolower($isp), 'cloudflare');
}

function get_ip_from_cloudfare(array $headers): ?string
{
    $remoteAddr = trim((string)($headers['REMOTE_ADDR'] ?? ''));
    $cfip = trim((string)($headers['HTTP_CF_CONNECTING_IP'] ?? ''));

    if ($cfip === '' || !is_public_ip($cfip)) {
        return null;
    }

    if (is_cloudflare_ip($remoteAddr)) {
        return $cfip;
    }

    add_log("bases", "Fake CloudflareIP: $cfip, RemoteAddr: $remoteAddr");
    return null;
}

function getcountry(string $ip): string
{
    if ($ip === 'Unknown') return 'Unknown';

    if ($ip === '::1' || $ip === '127.0.0.1')
        $ip = '31.177.76.70'; //for debugging

    if (use_maxminddb_extension()) {
        $record = read_maxminddb_record('country.mmdb', $ip);
        if ($record === null) {
            add_log("bases", "GetCountry AddressNotFoundException: $ip");
            return 'Unknown';
        }
        return get_country_code_from_record($record);
    }

    $record = read_maxminddb_record('country.mmdb', $ip);
    if ($record === null) {
        add_log("bases", "GetCountry AddressNotFoundException: $ip");
        return 'Unknown';
    }
    return get_country_code_from_record($record);
}

function getisp(string $ip)
{
    if ($ip === 'Unknown') return 'Unknown';
    if ($ip === '::1' || $ip === '127.0.0.1')
        $ip = '31.177.76.70'; //for debugging

    if (use_maxminddb_extension()) {
        $record = read_maxminddb_record('asn.mmdb', $ip);
        if ($record === null) {
            add_log("bases", "GetISP AddressNotFoundException: $ip");
            return 'Unknown';
        }
        return get_asn_organization_from_record($record);
    }

    $record = read_maxminddb_record('asn.mmdb', $ip);
    if ($record === null) {
        add_log("bases", "GetISP AddressNotFoundException: $ip");
        return 'Unknown';
    }
    return get_asn_organization_from_record($record);
}

function get_country_code_from_record(?array $record): string
{
    if (!is_array($record)) {
        return 'Unknown';
    }

    return (string)($record['country_code'] ?? 'Unknown');
}

function get_asn_organization_from_record(?array $record): string
{
    if (!is_array($record)) {
        return 'Unknown';
    }

    return (string)($record['autonomous_system_organization'] ?? 'Unknown');
}

function use_maxminddb_extension(): bool
{
    return extension_loaded('maxminddb') && class_exists('\\MaxMind\\Db\\Reader', false);
}

function read_maxminddb_record(string $fileName, string $ip): ?array
{
    $path = __DIR__ . '/' . $fileName;
    if (!is_readable($path)) {
        throw new RuntimeException("Configuration error: GeoIP database is missing or unreadable: $path. Run bases/update.php, or upload $fileName manually.");
    }

    try {
        $reader = new \MaxMind\Db\Reader($path);
        $record = $reader->get($ip);
        $reader->close();
        return is_array($record) ? $record : null;
    } catch (Throwable $exception) {
        throw new RuntimeException("Configuration error: GeoIP database cannot be opened: $path. " . $exception->getMessage(), 0, $exception);
    }
}
