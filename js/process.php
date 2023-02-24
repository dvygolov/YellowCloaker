<?php
require_once __DIR__.'/obfuscator.php';
require_once __DIR__.'/../settings.php';
require_once __DIR__.'/../requestfunc.php';
header('Content-Type: text/javascript');
$jsCode = str_replace('{DOMAIN}', get_cloaker_path(), file_get_contents(__DIR__ . '/process.js'));
$reason = '';
if (!empty($_GET['reason'])) $reason = $_GET['reason'];
$jsCode = str_replace('{REASON}', $reason, $jsCode); //пробрасываем js-причину того, что разрешаем переход на блэк
if ($js_obfuscate) {
    $hunter = new HunterObfuscator($jsCode);
    echo $hunter->Obfuscate();
} else {
    echo $jsCode;
}