<?php
    include 'obfuscator.php';
    include '../settings.php';
    header('Content-Type: text/javascript');
    
    $detector= file_get_contents(__DIR__.'/detector.js');
    if ($js_obfuscate) {
        $hunter = new HunterObfuscator($detector);
        echo $hunter->Obfuscate();
        echo ';';
    } else {
        echo $detector;
    }
    
    $jsCode = file_get_contents(__DIR__.'/detect.js');
    $jsCode = str_replace('{DOMAIN}', $_SERVER['SERVER_NAME'].":".$_SERVER['SERVER_PORT'], $jsCode);
    $js_checks_str=	implode('", "', $js_checks);
    $jsCode = str_replace('{JSCHECKS}', $js_checks_str, $jsCode);
    $jsCode = str_replace('{JSTIMEOUT}', $js_timeout, $jsCode);

    if ($js_obfuscate) {
        $hunter = new HunterObfuscator($jsCode);
        echo $hunter->Obfuscate();
    } else {
        echo $jsCode;
    }
