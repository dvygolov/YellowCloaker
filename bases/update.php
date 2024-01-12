<?php

require_once __DIR__ . "/../debug.php";
require_once __DIR__ . "/../admin/password.php";

function downloadAndExtractMaxMindDB($licenseKey, $directory, $editionIds): string
{
    $result = "";

    foreach ($editionIds as $editionId) {
        $result .= downloadMaxMindDB($licenseKey, $directory, $editionId) . "\n";
    }
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
        save_update_version($folder);
        $result .= "Folder: $folder\n";
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

function save_update_version($updatePath): void
{
    // Regular expression to extract the date in YYYYMMDD format
    preg_match("/\d{8}/", $updatePath, $matches);

    if ($matches) {
        $dateStr = $matches[0];
        // Create DateTime object from the extracted date string
        $dateObj = DateTime::createFromFormat('Ymd', $dateStr);
        // Format the date as dd.MM.yy
        $formattedDate = $dateObj->format('d.m.y');
    } else {
        $formattedDate = "No date found";
    }

    file_put_contents(__DIR__ . "/update.txt", $formattedDate);
}

function send_update_result($msg): int
{
    $res = ["result" => $msg];
    header('Content-type: application/json');
    echo json_encode($res);
    return http_response_code(200);
}
$passOk = check_password(false);
if (!$passOk) {
    return send_update_result("Error: password check not passed!");
}
$configPath = __DIR__ . "/update.json";
if (!file_exists($configPath)) {
    return send_update_result("Error: 'bases/update.json' not found!");
}
$config = json_decode(file_get_contents($configPath), true);
if (empty($config["licenseKey"])) {
    return send_update_result("Error: licenseKey not set, edit 'bases/update.json'!");
}

$editionIds = ['GeoLite2-ASN', 'GeoLite2-City', 'GeoLite2-Country'];

$result = downloadAndExtractMaxMindDB($config["licenseKey"], __DIR__, $editionIds);
return send_update_result("Geobases updated!");
