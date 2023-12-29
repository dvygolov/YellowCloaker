<?php
require_once __DIR__ . '/url.php';

function redirect($url, $redirect_type = 302,
                  $add_querystring = false, $replace_macros = false, $replace_subs = false): void
{
    if ($add_querystring)
        $url = add_querystring($url);
    if ($replace_macros)
        $url = replace_all_macros($url);
    if ($replace_subs)
        $url = replace_subs_in_link($url);
    header('X-Robots-Tag: noindex, nofollow');
    header('Location: ' . $url, true, $redirect_type);
}

function jsredirect($url): void
{
    echo "<script type='text/javascript'> window.location='$url';</script>";
}