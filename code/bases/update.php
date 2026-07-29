<?php

require_once __DIR__ . "/../debug.php";
require_once __DIR__ . "/../paths.php";
require_once get_admin_dir() . "/password.php";
require_once __DIR__ . "/../logging.php";
require_once __DIR__ . "/../requestfunc.php";

const SAPICS_RELEASE_BASE = 'https://github.com/sapics/ip-location-db/releases/download/latest';
const GEO_DATABASES = [
    'country.mmdb' => SAPICS_RELEASE_BASE . '/geolite2-country.mmdb',
    'asn.mmdb' => SAPICS_RELEASE_BASE . '/origin-asn.mmdb',
];

function download_geo_bases(string $directory): string
{
    $result = "";
    foreach (GEO_DATABASES as $fileName => $url) {
        $result .= download_geo_base($url, $directory . '/' . $fileName) . "\n";
    }
    save_update_version();
    return $result;
}

function download_geo_base(string $url, string $targetPath): string
{
    $fileName = basename($targetPath);
    $tempPath = $targetPath . '.tmp';

    try {
        add_log('trace', "Starting geobase download for $fileName from $url");

        $response = HttpClient::send(new HttpRequest(
            id: $fileName,
            url: $url,
            timeout: 0,
            connectTimeout: 0,
            followRedirects: true,
            verifyPeer: true,
            verifyHost: 2,
            userAgent: 'YellowTDS GeoBases Updater',
            failOnHttpError: true,
        ));
        $output = $response->content;
        if (!$response->isOk()) {
            @unlink($tempPath);
            throw new RuntimeException("$fileName HTTP {$response->httpCode()}: {$response->error}");
        }

        if (file_put_contents($tempPath, (string)$output, LOCK_EX) === false) {
            @unlink($tempPath);
            throw new RuntimeException("$fileName Error: failed to write temporary file");
        }

        if (!rename($tempPath, $targetPath)) {
            @unlink($tempPath);
            throw new RuntimeException("$fileName Error: failed to replace database file");
        }

        add_log('trace', "Downloaded geobase to $targetPath");
        return "$fileName processed!";
    } catch (Throwable $e) {
        @unlink($tempPath);
        add_error_log("GeoBases update: Error processing $fileName: " . $e->getMessage());
        throw $e;
    }
}

function save_update_version(): void
{
    $dateObj = new DateTime();
    $formattedDate = $dateObj->format('d.m.y');
    file_put_contents(__DIR__ . "/update.txt", $formattedDate);
}

function send_update_result($msg, $error = false): void
{
    $res = ["result" => $msg, "error" => $error];
    header('Content-type: application/json');
    http_response_code(200);
    echo json_encode($res);
}

$passOk = check_password(false);
if (!$passOk) {
    send_update_result("Password check not passed!", true);
    exit;
}

try {
    $result = download_geo_bases(__DIR__);
    send_update_result($result, false);
} catch (Throwable $exception) {
    send_update_result($exception->getMessage(), true);
}
