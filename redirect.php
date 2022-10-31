<?php
require_once __DIR__.'/url.php';

function redirect($url, $redirect_type = 302, $add_querystring = true)
{
    if ($add_querystring === true) {
        $url = add_querystring($url);
    }
    header('Location: ' . $url, true, $redirect_type);
}

function jsredirect($url)
{
    echo "<script type='text/javascript'> window.location='$url';</script>";
    return;
}