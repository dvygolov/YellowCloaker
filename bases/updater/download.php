<?php

function downloadAndExtractMaxMindDB($accountId, $licenseKey, $directory, $editionIds)
{
    foreach ($editionIds as $editionId) {
        downloadMaxMindDB($accountId, $licenseKey, $directory, $editionId);
    }
}

function downloadMaxMindDB($accountId, $licenseKey, $directory, $editionId = 'GeoLite2-ASN')
{
    if (!file_exists($directory)) {
        mkdir($directory, 0755, true);
    }

    $url = "https://download.maxmind.com/app/geoip_download?edition_id=$editionId&license_key=$licenseKey&suffix=tar.gz";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0); // Ignore SSL host verification
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); // Ignore SSL peer verification

    $output = curl_exec($ch);
    if ($output === false) {
        echo "cURL Error: " . curl_error($ch);
        return;
    }

    $fileName = $directory . '/' . $editionId . '.tar.gz';
    file_put_contents($fileName, $output);

    echo "Downloaded $editionId database to $fileName\n";

    // Decompress the file
    $phar = new PharData($fileName);
    $phar->decompress();

    $tarName = str_replace('.gz', '', $fileName);
    $phar = new PharData($tarName);
    foreach ($phar as $folder) {
        if ($folder->isDir()) {
            $subPhar = new PharData($folder->getPathname());
            foreach ($subPhar as $file) {
                if (preg_match('/\.mmdb$/', $file->getFilename())) {
                    // Extract file manually to avoid creating directory
                    $content = file_get_contents($file->getPathname());
                    file_put_contents($directory . '/' . $file->getFilename(), $content);
                    echo "Extracted " . $file->getFilename() . " to $directory\n";
                    break; // Assuming there's only one .mmdb file
                }
            }
        }
    }

    // Delete the tar.gz and tar files
    // unlink($fileName);
    // unlink($tarName);

    curl_close($ch);
}

$accountId = ''; // Replace with your MaxMind account ID
$licenseKey = ''; // Replace with your MaxMind license key
$directory = __DIR__ . "/.."; // Replace with your desired directory path
$editionIds = ['GeoLite2-ASN', 'GeoLite2-City', 'GeoLite2-Country']; // The databases to download

downloadAndExtractMaxMindDB($accountId, $licenseKey, $directory, $editionIds);
