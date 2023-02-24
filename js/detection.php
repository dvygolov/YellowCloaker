<?php
    require_once __DIR__.'/obfuscator.php';
    require_once __DIR__.'/../settings.php';
	require_once __DIR__.'/../requestfunc.php';
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
    $jsCode = str_replace('{DOMAIN}', get_cloaker_path(), $jsCode);
    $js_checks_str=	implode('", "', $js_checks);
    $jsCode = str_replace('{JSCHECKS}', $js_checks_str, $jsCode);
    $jsCode = str_replace('{JSTIMEOUT}', $js_timeout, $jsCode);
    $jsCode = str_replace('{JSTZSTART}', $js_tzstart, $jsCode);
    $jsCode = str_replace('{JSTZEND}', $js_tzend, $jsCode);

    if ($js_obfuscate) {
        $hunter = new HunterObfuscator($jsCode);
        echo $hunter->Obfuscate();
    } else {
        echo $jsCode;
    }
