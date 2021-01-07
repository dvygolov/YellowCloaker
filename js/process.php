<?php
    include 'obfuscator.php';
    include '../settings.php';
    header('Content-Type: text/javascript');
	$jsCode= str_replace('{DOMAIN}', $_SERVER['SERVER_NAME'], file_get_contents(__DIR__.'/process.js'));
	$reason='nojschecks';
	if (!empty($_GET['reason'])) $reason=$_GET['reason'];
    $jsCode= str_replace('{REASON}', $reason, $jsCode); //пробрасываем js-причину того, что разрешаем переход на блэк
    if ($js_obfuscate) {
        $hunter = new HunterObfuscator($jsCode);
        echo $hunter->Obfuscate();
    } else {
        echo $jsCode;
    }
?>