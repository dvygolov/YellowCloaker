<?php
require_once __DIR__ . '/macros.php';

function redirect($url, $redirect_type = 302, $rep_macros = false)
{
    $url = urldecode($url);
    if ($rep_macros) {
        $mp = new MacrosProcessor();
        $url = $mp->replace_url_macros($url);
    }
    
    if ($redirect_type==='js') {
        return "<script type='text/javascript'>window.location='$url';</script>";
    } else {
        header('X-Robots-Tag: noindex, nofollow');
        header('Referrer-Policy: no-referrer');
        header('Location: ' . $url, true, $redirect_type);
    }
}