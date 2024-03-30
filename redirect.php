<?php
require_once __DIR__ . '/macros.php';
require_once __DIR__ . '/settings.php';

function redirect($url, $redirect_type = 302, $rep_macros = false): void
{
    global $tds_api_key;
    if ($rep_macros) {
        $mp = new MacrosProcessor($tds_api_key);
        $url = $mp->replace_url_macros($url);
    }
    header('X-Robots-Tag: noindex, nofollow');
    header('Location: ' . $url, true, $redirect_type);
}

function jsredirect($url): void
{
    echo "<script type='text/javascript'> window.location='$url';</script>";
}

