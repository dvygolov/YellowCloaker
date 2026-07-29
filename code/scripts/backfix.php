<?php
require_once __DIR__ . '/../debug.php';
require_once __DIR__ . '/../js/obfuscator.php';

$js_code = file_get_contents(__DIR__ . '/backfix.js');

if (!DebugMethods::on()) {
    $hunter = new HunterObfuscator($js_code);
    $js_code = $hunter->Obfuscate();
}
header('Content-Type: application/javascript');
echo $js_code;