<?php

include_once __DIR__ . '/Spyc.php';
include_once __DIR__ . '/autoload.php';

use DeviceDetector\ClientHints;
use DeviceDetector\DeviceDetector;
use DeviceDetector\Parser\Device\AbstractDeviceParser;

ClientHints::requestClientHints();
AbstractDeviceParser::setVersionTruncation(AbstractDeviceParser::VERSION_TRUNCATION_NONE);

$userAgent = $_SERVER['HTTP_USER_AGENT']; // change this to the useragent you want to parse
$clientHints = ClientHints::factory($_SERVER); // client hints are optional

$dd = new DeviceDetector($userAgent, $clientHints);

$dd->parse();

if ($dd->isBot()) {
    // handle bots,spiders,crawlers,...
    $botInfo = $dd->getBot();
} else {
    $clientInfo = $dd->getClient(); // holds information about browser, feed reader, media player, ...
    echo 'Client Info: <br/>';
    foreach ($clientInfo as $key => $value) {
        echo "$key: $value <br/>";
    }

    $osInfo = $dd->getOs();
    echo 'OS Info: <br/>';
    foreach ($osInfo as $key => $value) {
        echo "$key: $value <br/>";
    }

    $device = $dd->getDeviceName();
    echo "Device: $device<br/>";
    $brand = $dd->getBrandName();
    echo "Brand: $brand<br/>";
    $model = $dd->getModel();
    echo "Model: $model<br/>";
}
