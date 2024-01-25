<?php
function get_referer(): string
{
    $referer = '';
    if (!empty($_SERVER['HTTP_REFERER'])) {
        $referer = $_SERVER['HTTP_REFERER'];
    } else if (!empty($_COOKIE['referer'])) {
        $referer = $_COOKIE['referer'];
    }
    return $referer;
}
?>