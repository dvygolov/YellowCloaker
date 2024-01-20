<?php
global $js_checks, $js_timeout, $js_tzstart, $js_tzend, $js_obfuscate;
require_once __DIR__.'/obfuscator.php';
    require_once __DIR__.'/../settings.php';
	require_once __DIR__.'/../requestfunc.php';
	require_once __DIR__.'/../debug.php';
    header('Content-Type: text/javascript');
    
    $detector= file_get_contents(__DIR__.'/detector.js');

    $jsCode = file_get_contents(__DIR__.'/detect.js');
    if (DebugMethods::$on)
        $jsCode = str_replace('{DEBUG}', 'true', $jsCode);
    else
        $jsCode = str_replace('{DEBUG}', 'false', $jsCode);
    $jsCode = str_replace('{DOMAIN}', get_cloaker_path(), $jsCode);
    $js_checks_str=	implode('", "', $js_checks);
    $jsCode = str_replace('{JSCHECKS}', $js_checks_str, $jsCode);
    $jsCode = str_replace('{JSTIMEOUT}', $js_timeout, $jsCode);
    $jsCode = str_replace('{JSTZSTART}', $js_tzstart, $jsCode);
    $jsCode = str_replace('{JSTZEND}', $js_tzend, $jsCode);

    $fullCode = $detector."\n".$jsCode;
    if ($js_obfuscate) {
        $hunter = new HunterObfuscator($fullCode);
        echo $hunter->Obfuscate();
    } else {
        echo $fullCode;
    }
