<?php

require_once __DIR__ . "/../debug.php";
require_once __DIR__ . "/../admin/password.php";

function downloadAndExtractMaxMindDB($licenseKey, $directory, $editionIds): string
{
    $result = "";
    foreach ($editionIds as $editionId) {
        $result .= downloadMaxMindDB($licenseKey, $directory, $editionId) . "\n";
    }
    save_update_version();
    return $result;
}

function downloadMaxMindDB($licenseKey, $directory, $editionId): string
{
    $url = "https://download.maxmind.com/app/geoip_download?edition_id=$editionId&license_key=$licenseKey&suffix=tar.gz";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0); // Ignore SSL host verification
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); // Ignore SSL peer verification

    $output = curl_exec($ch);
    if ($output === false) {
        return "$editionId cURL Error: " . curl_error($ch);
    }

    $fileName = $directory . '/' . $editionId . '.tar.gz';
    file_put_contents($fileName, $output);

    $result = "Downloaded $editionId database to $fileName\n";

    // Decompress the file
    $phar = new PharData($fileName);
    $phar->decompress();

    $tarName = str_replace('.gz', '', $fileName);
    $phar = new PharData($tarName);
    foreach ($phar as $folder) {
        if (!$folder->isDir()) {
            continue;
        }
        $result .= "Folder: ".$folder->getPathname()."\n";
        $subPhar = new PharData($folder->getPathname());
        foreach ($subPhar as $file) {
            if (preg_match('/\.mmdb$/', $file->getFilename())) {
                // Extract file manually to avoid creating directory
                $content = file_get_contents($file->getPathname());
                file_put_contents($directory . '/' . $file->getFilename(), $content);
                $result .= "Extracted " . $file->getFilename() . " to $directory\n";
                break; // Assuming there's only one .mmdb file
            }
        }
    }

    // Delete the tar.gz and tar files
    unlink($fileName);
    unlink($tarName);

    curl_close($ch);
    return $result;
}

function save_update_version(): void
{
    $dateObj = new DateTime();
    $formattedDate = $dateObj->format('d.m.y');
    file_put_contents(__DIR__ . "/update.txt", $formattedDate);
}

function send_update_result($msg): void
{
    $res = ["result" => $msg];
    header('Content-type: application/json');
    http_response_code(200);
    echo json_encode($res);
}

$passOk = check_password(false);
if (!$passOk) {
    send_update_result("Error: password check not passed!");
    exit;
}

$cloSettings = json_decode(file_get_contents(__DIR__ . '/../settings.json'), true);
if (empty($cloSettings["maxMindKey"])) {
    send_update_result("Error: MaxMind key not set, edit 'settings.json'!");
    exit;
}

$editionIds = ['GeoLite2-ASN', 'GeoLite2-City', 'GeoLite2-Country'];
$result = downloadAndExtractMaxMindDB($cloSettings["maxMindKey"], __DIR__, $editionIds);
send_update_result("OK");