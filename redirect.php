<?php
require_once __DIR__ . '/macros.php';

function redirect($url, $redirect_type = 302, $rep_macros = false): void
{
    if ($rep_macros) 
        $url = replace_url_macros($url);
    header('X-Robots-Tag: noindex, nofollow');
    header('Location: ' . $url, true, $redirect_type);
}

function jsredirect($url): void
{
    echo "<script type='text/javascript'> window.location='$url';</script>";
}