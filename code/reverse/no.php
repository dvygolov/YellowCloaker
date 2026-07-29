<?php

require_once dirname(__DIR__) . '/requestfunc.php';

$backend_url = "http://localhost:8000/";
$backend_info = parse_url($backend_url);
$host = $_SERVER['HTTP_HOST'];
$request_uri = $_SERVER['REQUEST_URI'];
$uri_rel = "no.php"; # URI to this file relative to public_html

$request_includes_nophp_uri = true;
if ($request_includes_nophp_uri == false) {
    $request_uri = str_replace($uri_rel, "/", $request_uri);
}

$url = $backend_url; //. $request_uri;


function getRequestHeaders($multipart_delimiter = NULL)
{
    $headers = array();
    foreach ($_SERVER as $key => $value) {
        if (preg_match("/^HTTP/", $key)) { # only keep HTTP headers
            if (
            preg_match("/^HTTP_HOST/", $key) == 0 && # let curl set the actual host/proxy
            preg_match("/^HTTP_ORIGIN/", $key) == 0 &&
            preg_match("/^HTTP_CONTENT_LEN/", $key) == 0 && # let curl set the actual content length
            preg_match("/^HTTPS/", $key) == 0
            ) {
                $key = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))));
                if ($key)
                    array_push($headers, "$key: $value");
            }
        } elseif (preg_match("/^CONTENT_TYPE/", $key)) {

            $key = "Content-Type";

            if (preg_match("/^multipart/", strtolower($value)) && $multipart_delimiter) {
                $value = "multipart/form-data; boundary=" . $multipart_delimiter;
                array_push($headers, "$key: $value");
            } else if (preg_match("/^application\/json/", strtolower($value))) {
                // Handle application/json
                array_push($headers, "$key: $value");
            }
        }
    }
    return $headers;
}

function build_domain_regex($hostname)
{
    $names = explode('.', $hostname); //assumes main domain is the TLD
    $regex = "";
    for ($i = 0; $i < count($names) - 2; $i++) {
        $regex .= '[' . $names[$i] . '.]?';
    }
    $main_domain = $names[count($names) - 2] . "." . $names[count($names) - 1];
    $regex .= $main_domain;
    return $regex;
}

function build_multipart_data_files($delimiter, $fields, $files)
{
    # Inspiration from: https://gist.github.com/maxivak/18fcac476a2f4ea02e5f80b303811d5f :)
    $data = '';
    $eol = "\r\n";

    foreach ($fields as $name => $content) {
        $data .= "--" . $delimiter . $eol
        . 'Content-Disposition: form-data; name="' . $name . "\"" . $eol . $eol
        . $content . $eol;
    }

    foreach ($files as $name => $content) {
        $data .= "--" . $delimiter . $eol
        . 'Content-Disposition: form-data; name="' . $name . '"; filename="' . $name . '"' . $eol
        . 'Content-Transfer-Encoding: binary' . $eol;
        $data .= $eol;
        $data .= $content . $eol;
    }
    $data .= "--" . $delimiter . "--" . $eol;

    return $data;
}

$method = strtolower((string)($_SERVER['REQUEST_METHOD'] ?? 'get')) === 'post' ? 'POST' : 'GET';
$requestHeaders = getRequestHeaders();
$post_data = null;
if ($method === 'POST') {
    $post_data = file_get_contents("php://input");

    if (preg_match("/^multipart/", strtolower((string)($_SERVER['CONTENT_TYPE'] ?? '')))) {
        $delimiter = '-------------' . uniqid();
        $post_data = build_multipart_data_files($delimiter, $_POST, $_FILES);
        $requestHeaders = getRequestHeaders($delimiter);
    }
}

$response = HttpClient::send(new HttpRequest(
    id: 'reverse-proxy',
    url: $url,
    method: $method,
    body: $post_data,
    headers: $requestHeaders,
    timeout: 0,
    connectTimeout: 0,
    followRedirects: true,
    userAgent: null,
    captureHeaders: true,
));
$contents = $response->content === false ? '' : $response->content;

// Propagate headers to response.
foreach ($response->headers as $headerName => $values) {
    if ($headerName === ':status') {
        if (isset($values[0])) {
            header($values[0], true);
        }
        continue;
    }
    if (in_array($headerName, ['transfer-encoding', 'connection', 'content-length'], true)) {
        continue;
    }
    foreach ($values as $value) {
        $header = $headerName . ': ' . $value;
        if ($headerName === 'location') {
            # rewrite absolute local redirects to relative ones
            $header = str_replace($backend_url, "/", $header);
        } else if ($headerName === 'set-cookie') {
            # replace original domain name in Set-Cookie headers with our server's domain
            $domain_regex = build_domain_regex($backend_info['host']);
            $header = preg_replace('/Domain=' . $domain_regex . '/', 'Domain=' . $host, $header);
        }
        header($header, false);
    }
}

print $contents; # return the proxied request result to the browser
