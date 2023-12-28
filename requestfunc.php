<?php
require_once __DIR__ . '/bases/ipcountry.php';
require_once __DIR__ . '/url.php';

function get_cloaker_path(): string
{
    $domain = $_SERVER['HTTP_HOST'];
    $server_has_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || str_contains($_SERVER['HTTP_HOST'], '127.0.0.1');
    $prefix = $server_has_https ? 'https://' : 'http://';
    $fullpath = $prefix . $domain . '/';
    $script_path = array_values(array_filter(explode("/", $_SERVER['SCRIPT_NAME']), 'strlen'));
    array_pop($script_path);

    if (count($script_path) > 0) {
        if ($script_path[count($script_path) - 1] === 'js') //Dirty hack for js-connections
            array_pop($script_path);
        if (count($script_path) > 0)
            $fullpath .= implode('/', $script_path);
    }
    if ($fullpath[strlen($fullpath) - 1] !== '/')
        $fullpath .= '/';
    return $fullpath;
}

function get_abs_from_rel($url, $add_query_string = false)
{
    $fullpath = get_cloaker_path();
    $fullpath .= $url;
    if (!str_ends_with($url, '.php')) $fullpath = $fullpath . '/';
    if ($add_query_string === true) {
        $fullpath = add_querystring($fullpath);
    }
    return $fullpath;
}

function get_request_headers($ispost = false): array
{
    $ip = getip();
    $headers = array(
        'X-YWBCLO-UIP: ' . $ip,
        //'X-FORWARDED-FOR: ' . $ip,
        //'CF-CONNECTING-IP: '.$ip,
        'FORWARDED-FOR: ' . $ip,
        'X-COMING-FROM: ' . $ip,
        'COMING-FROM: ' . $ip,
        'FORWARDED-FOR-IP: ' . $ip,
        'CLIENT-IP: ' . $ip,
        'X-REAL-IP: ' . $ip,
        'REMOTE-ADDR: ' . $ip
    );
    if ($ispost)
        $headers[] = "Content-Type: application/x-www-form-urlencoded";
    return $headers;
}

function get($url): array
{
    $curl = curl_init();
    $optArray = array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_HTTPHEADER => get_request_headers(false),
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_REFERER => $_SERVER['REQUEST_URI'],
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML like Gecko) Chrome/84.0.4147.89 Safari/537.36'
    );
    curl_setopt_array($curl, $optArray);
    $content = curl_exec($curl);
    $info = curl_getinfo($curl);
    $error = curl_error($curl);
    curl_close($curl);
    return ["html" => $content, "info" => $info, "error" => $error];
}

function post($url, $postfields): array
{
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_VERBOSE => true,
        CURLOPT_POSTFIELDS => $postfields,
        CURLOPT_REFERER => $_SERVER['REQUEST_URI'],
        CURLOPT_HTTPHEADER => get_request_headers(true),
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML like Gecko) Chrome/84.0.4147.89 Safari/537.36'
    ));

    $content = curl_exec($curl);
    $info = curl_getinfo($curl);
    $error = curl_error($curl);
    curl_close($curl);
    return ["html" => $content, "info" => $info, "error" => $error];
}

